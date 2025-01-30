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
 * PHPUnit tests for tool_rssfeeds.
 *
 * @package   tool_rssfeeds
 * @copyright 2018 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/rss_client/block_rss_client.php');

/**
 * Class for the PHPunit tests for RSS feed manager.
 *
 * @package    tool_rssfeeds
 * @copyright 2018 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_rssfeeds_rss_manager_test extends advanced_testcase {

    /** @var int The id for feed1 */
    private $guestfeedid;
    /** @var stdClass The enduser */
    private $enduser;
    /** @var int The id for feed1 */
    private $enduserfeedid;
    /** @var stdClass The first course */
    private $firstcourse;
    /** @var stdClass The second course */
    private $secondcourse;

    /**
     * General setup for PHPUnit testing
     */
    protected function setUp(): void {
        global $DB;

        $this->resetAfterTest(true);

        // Create a test user.
        $this->enduser = $this->getDataGenerator()->create_user(array(
            'username' => 'enduser',
            'firstname' => 'End',
            'lastname' => 'User',
            'email' => 'enduser@example.com'
        ));

        // Create an RSS feed for the guest user.
        $record = (object) array(
            'userid' => 1,
            'title' => 'Feed 1',
            'preferredtitle' => '',
            'description' => 'Feed 1 belongs to the guest user.',
            'shared' => 0,
            'url' => 'http://example.com/feed',
        );
        $this->guestfeedid = $DB->insert_record('block_rss_client', $record);

        // Create an RSS feed for enduser.
        $record = (object) array(
            'userid' => $this->enduser->id,
            'title' => 'Enduser Feed',
            'preferredtitle' => '',
            'description' => 'End User owns this feed.',
            'shared' => 0,
            'url' => 'http://example.com/atom',
        );
        $this->enduserfeedid = $DB->insert_record('block_rss_client', $record);

        // Create a couple courses.
        $this->firstcourse = $this->getDataGenerator()->create_course(array(
            'fullname' => "A first course",
            'shortname' => "first",
        ));
        $this->secondcourse = $this->getDataGenerator()->create_course(array(
            'fullname' => "A second course",
            'shortname' => "second",
        ));

        // Add a couple RSS blocks.
        $configdata = new stdClass;
        $configdata->displaydescription = 0;
        $configdata->shownumentries = 2;
        $configdata->rssid = array($this->enduserfeedid);
        $configdata->title = '';
        $configdata->block_rss_client_show_channel_link = 0;
        $configdata->block_rss_client_show_channel_image = 0;
        $config = base64_encode(serialize($configdata));

        $instance = (object)[
            'blockname' => 'rss_client',
            'parentcontextid' => \context_course::instance($this->firstcourse->id)->id,
            'showinsubcontexts' => 0,
            'pagetypepattern' => 'course-view-*',
            'defaultweight' => 0,
            'timecreated' => 1,
            'timemodified' => 1,
            'configdata' => $config,
        ];
        $DB->insert_record('block_instances', $instance);
        $instance->parentcontextid = \context_course::instance($this->secondcourse->id)->id;
        $DB->insert_record('block_instances', $instance);

        // Add a non-RSS block.
        $instance = (object)[
            'blockname' => 'course_summary',
            'parentcontextid' => \context_course::instance($this->firstcourse->id)->id,
            'showinsubcontexts' => 0,
            'pagetypepattern' => 'course-view-*',
            'defaultweight' => 0,
            'timecreated' => 1,
            'timemodified' => 1,
        ];
        $DB->insert_record('block_instances', $instance);
    }

    /**
     * Test the get_feed helper function.
     */
    public function test_get_feed() {
        // Confirm that we can get just the feed we want.
        $feed = \tool_rssfeeds\helper::get_feed($this->guestfeedid);
        $this->assertEquals(1, count($feed));
        $this->assertEquals(1, $feed[$this->guestfeedid]->userid);
        $this->assertEquals('Feed 1', $feed[$this->guestfeedid]->title);
        $this->assertEquals('http://example.com/feed', $feed[$this->guestfeedid]->url);
    }

    /**
     * Test the get_feeds helper function.
     */
    public function test_get_feeds() {
        // Confirm that we can get all feeds.
        $feeds = \tool_rssfeeds\helper::get_feeds();
        $this->assertEquals(2, count($feeds));
        $this->assertEquals('http://example.com/atom', $feeds[$this->enduserfeedid]->url);
    }

    /**
     * Test the display helper function.
     */
    public function test_display() {
        $feeds = \tool_rssfeeds\helper::get_feeds();
        $table = \tool_rssfeeds\helper::display($feeds);
        $this->assertInstanceOf('html_table', $table);
        $this->assertEquals(2, count($table->data));
        $this->assertEquals('Not used in any courses', $table->data[0][2]);
        $this->assertStringContainsString('End User', $table->data[1][1]);
        $this->assertStringContainsString('first course', $table->data[1][2]);
        $this->assertStringContainsString('second course', $table->data[1][2]);
    }

    /**
     * Test the delete_feed helper function.
     */
    public function test_delete_feed() {
        global $DB;

        \tool_rssfeeds\helper::delete_feed($this->enduserfeedid);
        $this->assertEquals(1, $DB->count_records('block_rss_client'));
        $feeds = \tool_rssfeeds\helper::get_feeds();
        $table = \tool_rssfeeds\helper::display($feeds);
        $this->assertEquals(1, count($table->data));
    }
}
