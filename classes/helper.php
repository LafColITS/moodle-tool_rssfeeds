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
    public static function delete_feed($feedid) {
        global $DB;

        $feed = self::get_feed($feedid);
        $DB->delete_records('block_rss_client', array('id' => $feedid));

        // Reprocess block configdata.
        if (empty($feed[$feedid]->instances)) {
            return;
        }

        foreach ($feed[$feedid]->instances as $instance) {
            $block = $DB->get_record('block_instances', array('id' => $instance));
            $configdata = unserialize(base64_decode($block->configdata));
            if (is_array($configdata->rssid) && ($key = array_search($feedid, $configdata->rssid)) !== false) {
                unset($configdata->rssid[$key]);
            }
            $block->configdata = base64_encode(serialize($configdata));
            $DB->update_record('block_instances', $block);
        }
        return;
    }

    public static function get_feed($feedid) {
        global $DB;

        $rssfeed = $DB->get_records('block_rss_client', array('id' => $feedid));
        return self::get_block_instances($rssfeed);
    }

    public static function get_feeds() {
        global $DB;

        // Load all the feeds.
        $rssfeeds = $DB->get_records('block_rss_client');
        return self::get_block_instances($rssfeeds);
    }

    /**
     * Given an array of RSS feeds, get the block instances for each feed.
     * @param Array $feeds RSS feeds.
     */
    private static function get_block_instances($feeds) {
        global $DB;

        // Prep the items in the feeds array. We need to store course ids and block instance ids.
        foreach ($feeds as $id => $feed) {
            $feeds[$id]->courses = array();
            $feeds[$id]->instances = array();
        }

        // Get all the block instances.
        $blocksql = "SELECT bi.id, c.id as courseid, c.fullname, bi.configdata
            FROM {course} c INNER JOIN {context} ctx ON c.id=ctx.instanceid INNER JOIN
            {block_instances} bi ON ctx.id=bi.parentcontextid WHERE ctx.contextlevel=50
            AND bi.blockname=?";
        $blocks = $DB->get_records_sql($blocksql, array('rss_client'));

        foreach ($blocks as $block) {
            $configdata = unserialize(base64_decode($block->configdata));
            if (!is_object($configdata) || !is_array($configdata->rssid)) {
                continue;
            }
            foreach ($configdata->rssid as $feed) {
                // Deleted rss feeds are not automatically removed from downstream
                // block instances.
                if ( ! array_key_exists($feed, $feeds)) {
                    continue;
                }
                $feeds[$feed]->courses[$block->courseid] = $block->fullname;
                $feeds[$feed]->instances[] = $block->id;
            }
        }
        return $feeds;
    }

    public static function display($feeds) {
        global $OUTPUT;

        $table = new \html_table();
        $table->head = array(
            get_string('feedurl', 'block_rss_client'),
            get_string('feedowner', 'tool_rssfeeds'),
            get_string('courses'),
            get_string('actions')
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
            $coursedisplay = empty($courses) ? get_string('unused', 'tool_rssfeeds') : \html_writer::alist($courses);
            $user = \core_user::get_user($feed->userid);
            $userprofile = new \moodle_url('/user/profile', array('id' => $feed->userid));

            // Build delete action.
            $deleteurl = new \moodle_url('/admin/tool/rssfeeds/index.php?deleterssid=' . $feed->id . '&sesskey=' . sesskey());
            $deleteicon = new \pix_icon('t/delete', get_string('delete'));
            $deleteaction = $OUTPUT->action_icon(
                $deleteurl, $deleteicon, new \confirm_action(get_string('deletefeedconfirm', 'tool_rssfeeds')));

            $table->data[] = array($feedurl, \html_writer::link($userprofile, fullname($user)), $coursedisplay, $deleteaction);
        }
        return $table;
    }
}
