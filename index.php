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
 * @package   tool_rssfeeds
 * @copyright 2018 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Ensure the user can be here.
require_login();

$deleterssid = optional_param('deleterssid', 0, PARAM_INT);

admin_externalpage_setup('toolrssfeeds');

// Process requested deletion.
if ($deleterssid && confirm_sesskey()) {
    \tool_rssfeeds\helper::delete_feed($deleterssid);
    redirect($PAGE->url, get_string('feeddeleted', 'block_rss_client'));
}

$feeds = \tool_rssfeeds\helper::get_feeds();

// Page output.
echo $OUTPUT->header();
echo html_writer::table(\tool_rssfeeds\helper::display($feeds));
echo $OUTPUT->footer();
