<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Helper functions.
 *
 * @package   tool_rssfeeds
 * @copyright 2018 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_rssfeeds;

defined('MOODLE_INTERNAL') || die();

class helper {
    public static function get_feeds() {
        global $DB;

        // Load all the feeds.
        $rssfeeds = $DB->get_records('block_rss_client');
        foreach ($rssfeeds as $id => $feeds) {
            $rssfeeds[$id]->courses = array();
        }

        // Get all the block instances.
        $blocksql = "SELECT bi.id, c.id as courseid, c.fullname, bi.configdata
            FROM {course} c INNER JOIN {context} ctx ON c.id=ctx.instanceid INNER JOIN
            {block_instances} bi ON ctx.id=bi.parentcontextid WHERE ctx.contextlevel=50
            AND bi.blockname=?";
        $blocks = $DB->get_records_sql($blocksql, array('rss_client'));
        foreach ($blocks as $block) {
            $configdata = unserialize(base64_decode($block->configdata));
            if (!is_array($configdata->rssid)) {
                continue;
            }
            foreach ($configdata->rssid as $feed) {
                // Deleted rss feeds are not automatically removed from downstream
                // block instances.
                if ( ! array_key_exists($feed, $rssfeeds)) {
                    continue;
                }
                $rssfeeds[$feed]->courses[$block->courseid] = $block->fullname;
            }
        }
        return $rssfeeds;
    }

    public static function display($feeds) {
        $table = new \html_table();
        $table->head = array(
            get_string('feedurl', 'block_rss_client'),
            get_string('feedowner', 'tool_rssfeeds'),
            get_string('courses')
        );
        foreach ($feeds as $feed) {
            $feedurl = \html_writer::link(
                new \moodle_url($feed->url),
                $feed->url
            );
            $courses = array();
            foreach ($feed->courses as $id => $fullname) {
                $courses[] = \html_writer::link(
                    new \moodle_url('/course/view.php', array('id' => $id)),
                    $fullname
                );
            }
            $user = \core_user::get_user($feed->userid);
            $userprofile = new \moodle_url('/user/profile', array('id' => $feed->userid));
            $table->data[] = array($feedurl, \html_writer::link($userprofile, fullname($user)), \html_writer::alist($courses));
        }
        return $table;
    }
}
