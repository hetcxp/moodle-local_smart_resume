<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * PHPUnit tests for the resume_finder service.
 *
 * @package    local_smart_resume
 * @category   test
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smart_resume;

use local_smart_resume\service\resume_finder;
use local_smart_resume\service\resume_finder_interface;

/**
 * Unit test case for resume_finder service and request cache.
 *
 * @package    local_smart_resume
 * @category   test
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_smart_resume\service\resume_finder::class)]
final class resume_finder_test extends \advanced_testcase {
    protected function tearDown(): void {
        resume_finder::clear_cache();
        parent::tearDown();
    }

    public function test_service_implements_interface(): void {
        $service = new resume_finder();
        $this->assertInstanceOf(resume_finder_interface::class, $service);
    }

    public function test_returns_null_when_completion_disabled_globally(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);

        $CFG->enablecompletion = 0;

        $cmid = resume_finder::get_first_incomplete_cmid($course, (int) $user->id);
        $this->assertNull($cmid);
    }

    public function test_returns_null_when_completion_disabled_in_course(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 0]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 0]);

        $cmid = resume_finder::get_first_incomplete_cmid($course, (int) $user->id);
        $this->assertNull($cmid);
    }

    public function test_resolves_first_incomplete_module(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $this->setUser($user);

        $page1 = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);
        $page2 = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);

        $cm1 = get_coursemodule_from_instance('page', $page1->id);
        $completion = new \completion_info($course);
        $completion->update_state($cm1, COMPLETION_COMPLETE, $user->id);

        $cmid = resume_finder::get_first_incomplete_cmid($course, (int) $user->id);
        $this->assertEquals((int) $page2->cmid, $cmid);
    }

    public function test_request_cache_memoization_and_clear(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $this->setUser($user);

        $page1 = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);
        $cm1 = get_coursemodule_from_instance('page', $page1->id);

        // First call populates cache with page1->cmid.
        $initialcmid = resume_finder::get_first_incomplete_cmid($course, (int) $user->id);
        $this->assertEquals((int) $page1->cmid, $initialcmid);

        // Mark page1 as complete.
        $completion = new \completion_info($course);
        $completion->update_state($cm1, COMPLETION_COMPLETE, $user->id);

        // Without clear_cache(), cached result remains page1->cmid.
        $cachedcmid = resume_finder::get_first_incomplete_cmid($course, (int) $user->id);
        $this->assertEquals((int) $page1->cmid, $cachedcmid);

        // Clear cache, next call must re-query and return null.
        resume_finder::clear_cache();
        $freshcmid = resume_finder::get_first_incomplete_cmid($course, (int) $user->id);
        $this->assertNull($freshcmid);
    }

    public function test_instance_and_static_facade_consistency(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $this->setUser($user);

        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);

        $service = new resume_finder();
        $instanceresult = $service->find_first_incomplete_cmid($course, (int) $user->id);
        resume_finder::clear_cache();
        $staticresult = resume_finder::get_first_incomplete_cmid($course, (int) $user->id);

        $this->assertEquals($instanceresult, $staticresult);
        $this->assertEquals((int) $page->cmid, $instanceresult);
    }
}
