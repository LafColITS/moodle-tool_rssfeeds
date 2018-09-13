@tool @tool_rssfeeds @tool_rssfeeds_manager
Feature: An admin can manage RSS feeds sitewide
  In order to view or delete RSS feeds
  As an admin
  I need to visit the RSS feeds report under Admin > Reports

  @javascript
  Scenario: An admin views a report
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
    And I log in as "jgrimm"
    And I am on "Grimm's Fairy Tales" course homepage with editing mode on
    And I add the "Remote RSS feeds" block
    And I configure the "Remote news feed" block
    And I follow "Add/edit feeds"
    And I press "Add a new feed"
    And I set the following fields to these values:
      | url            | http://docs.moodle.org |
      | preferredtitle | Feed 1                 |
    And I press "Add a new feed"
    And I am on "Grimm's Fairy Tales" course homepage
    And I configure the "Remote news feed" block
    And I set the following fields to these values:
      | config_title   | News from Moodle |
      | config_rssid[] | Feed 1           |
    When I press "Save changes"
    Then I should see "News from Moodle"
    Given I am on "Grimm's Dictionary" course homepage
    And I add the "Remote RSS feeds" block
    And I configure the "Remote news feed" block
    And I set the following fields to these values:
      | config_title   | News from Moodle |
      | config_rssid[] | Feed 1           |
    When I press "Save changes"
    Then I should see "News from Moodle"
    Given I log out
    And I log in as "admin"
    When I navigate to "Reports > Manage all RSS feeds" in site administration
    Then I should see "docs.moodle.org"
    And I should see "Jacob Grimm"
    And I should see "Fairy Tales"
    And I should see "Dictionary"
    When I follow "Delete"
    And I press "Yes"
    Then I should see "News feed deleted"
    But I should not see "Jacob Grimm"
    When I am on "Grimm's Fairy Tales" course homepage
    Then I should not see "News from Moodle"
    When I am on "Grimm's Dictionary" course homepage
    Then I should not see "News from Moodle"
