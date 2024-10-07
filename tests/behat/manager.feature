@tool @tool_rssfeeds @tool_rssfeeds_manager
Feature: An admin can manage RSS feeds sitewide
  In order to view or delete RSS feeds
  As an admin
  I need to visit the RSS feeds report under Admin > Reports

  Background:
    Given the following "courses" exist:
      | fullname            | shortname  |
      | Grimm's Fairy Tales | fairytales |
      | Grimm's Dictionary  | dictionary |
    And the following "users" exist:
      | username | password | firstname | lastname | email              |
      | jgrimm   | jgrimm   | Jacob     | Grimm    | jgrimm@example.com |
    And the following "course enrolments" exist:
      | course     | user   | role           |
      | fairytales | jgrimm | editingteacher |
      | dictionary | jgrimm | editingteacher |
    And I enable "rss_client" "block" plugin

  @javascript
  Scenario: An admin views a report
    And I log in as "jgrimm"
    And I am on "Grimm's Fairy Tales" course homepage with editing mode on
    And I add the "RSS feeds..." block
    And I click on "Add new RSS feed" "radio"
    And I set the following fields to these values:
      | config_feedurl | https://docs.moodle.org/401/en/Main_page  |
      | config_title   | News from Moodle |
      | config_block_rss_client_show_channel_link | Yes |
    And I press "Save changes"
    And I press "Manage RSS feeds"
    And I click on "Edit" "link"
    And I set the following fields to these values:
      | preferredtitle | Feed 1                 |
    And I press "Save changes"
    And I am on "Grimm's Fairy Tales" course homepage
    And I configure the "News from Moodle" block
    And I set the following fields to these values:
      | config_block_rss_client_show_channel_link | Yes |
    And I press "Save changes"
    And I should see "Source site..."
    Given I am on "Grimm's Dictionary" course homepage
    And I add the "RSS feeds..." block
    And I set the following fields to these values:
      | config_title   | News from Moodle |
      | config_rssid[] | Feed 1           |
      | config_block_rss_client_show_channel_link | Yes |
    When I press "Save changes"
    Then I should see "News from Moodle"
    And I should see "Source site..."
    And I log out
    Given I log in as "admin"
    And I navigate to "Reports > Manage all RSS feeds" in site administration
    Then I should see "docs.moodle.org"
    And I should see "Jacob Grimm"
    And I should see "Fairy Tales"
    And I should see "Dictionary"
    When I follow "Delete"
    And I press "Yes"
    Then I should see "RSS feed deleted"
    But I should not see "Jacob Grimm"
    When I am on "Grimm's Fairy Tales" course homepage
    Then I should see "News from Moodle"
    And I should not see "Source site..."
    When I am on "Grimm's Dictionary" course homepage
    Then I should see "News from Moodle"
    And I should not see "Source site..."
