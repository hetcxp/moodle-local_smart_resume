@local @local_smart_resume
Feature: Smart Resume Label Injection
  In order to resume my progress quickly
  As a student
  I need to see a label next to my next incomplete activity

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | One | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1 | 1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
    And the following "activities" exist:
      | activity | name | intro | course | idnumber | completion | completionview |
      | page | Page 1 | Test Page | C1 | page1 | 2 | 1 |
      | page | Page 2 | Test Page | C1 | page2 | 2 | 1 |

  @javascript
  Scenario: See the smart resume label on the first incomplete activity
    When I am on the "C1" "Course" page logged in as "student1"
    Then I should see "Next Activity" in the "Page 1" "activity"
    And I should not see "Next Activity" in the "Page 2" "activity"

  @javascript
  Scenario: Label moves after completing an activity
    Given I am on the "C1" "Course" page logged in as "student1"
    And I follow "Page 1"
    When I am on the "C1" "Course" page
    Then I should see "Next Activity" in the "Page 2" "activity"
    And I should not see "Next Activity" in the "Page 1" "activity"
