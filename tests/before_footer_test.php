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
 * PHPUnit tests for the before_footer hook.
 *
 * @package    local_smart_resume
 * @category   test
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smart_resume;

/**
 * Unit test case for before_footer hook.
 *
 * @package    local_smart_resume
 * @category   test
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_smart_resume\hook\before_footer::class)]
final class before_footer_test extends \advanced_testcase {
    public function test_execute_does_not_inject_on_frontpage(): void {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();

        $PAGE->set_course(get_site());
        $html = $this->execute_hook_and_get_html();
        $this->assertStringNotContainsString('local_smart_resume/main', $html);
    }

    public function test_execute_does_not_inject_when_disabled(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        set_config('enable', 0, 'local_smart_resume');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);

        $this->enrol_and_login($course, 'student');
        $this->set_course_page($course);
        $html = $this->execute_hook_and_get_html();
        $this->assertStringNotContainsString('local_smart_resume/main', $html);
    }

    public function test_execute_injects_for_enrolled_student_with_incomplete_activity(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        set_config('enable', 1, 'local_smart_resume');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $page1 = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);
        $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);

        $this->enrol_and_login($course, 'student');
        $this->set_course_page($course);
        $html = $this->execute_hook_and_get_html();
        $this->assertStringContainsString('local_smart_resume/main', $html);
        $this->assertStringContainsString((string)$page1->cmid, $html);
    }

    public function test_execute_does_not_inject_when_all_activities_completed(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        set_config('enable', 1, 'local_smart_resume');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $page1 = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);

        $student = $this->enrol_and_login($course, 'student');

        // Mark page1 as completed.
        $completion = new \completion_info($course);
        $cm1 = get_coursemodule_from_id('page', $page1->cmid);
        $completion->update_state($cm1, COMPLETION_COMPLETE, $student->id);

        $this->set_course_page($course);
        $html = $this->execute_hook_and_get_html();
        $this->assertStringNotContainsString('local_smart_resume/main', $html);
    }

    public function test_execute_respects_preview_for_admins_disabled(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        set_config('enable', 1, 'local_smart_resume');
        set_config('preview_for_admins', 0, 'local_smart_resume');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);

        $this->enrol_and_login($course, 'editingteacher');
        $this->set_course_page($course);
        $html = $this->execute_hook_and_get_html();
        $this->assertStringNotContainsString('local_smart_resume/main', $html);
    }

    public function test_execute_allows_admin_preview_when_enabled(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        set_config('enable', 1, 'local_smart_resume');
        set_config('preview_for_admins', 1, 'local_smart_resume');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $page1 = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);

        $this->enrol_and_login($course, 'editingteacher');
        $this->set_course_page($course);
        $html = $this->execute_hook_and_get_html();
        $this->assertStringContainsString('local_smart_resume/main', $html);
        $this->assertStringContainsString((string)$page1->cmid, $html);
    }

    public function test_execute_ignores_stealth_activities(): void {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        $CFG->allowstealth = 1;
        set_config('enable', 1, 'local_smart_resume');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $page1 = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);
        $page2 = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);

        // Make page1 stealth (visibleoncoursepage = 0).
        set_coursemodule_visible($page1->cmid, 1, 0);

        $this->enrol_and_login($course, 'student');
        $this->set_course_page($course);
        $html = $this->execute_hook_and_get_html();
        $this->assertStringContainsString('local_smart_resume/main', $html);
        // It must target page2, not the stealth page1.
        $this->assertStringContainsString((string)$page2->cmid, $html);
    }

    public function test_execute_does_not_inject_on_non_course_pages(): void {
        global $PAGE, $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        set_config('enable', 1, 'local_smart_resume');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $page1 = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);

        $this->enrol_and_login($course, 'student');

        $PAGE->set_course($course);
        $PAGE->set_url(new \moodle_url('/mod/page/view.php', ['id' => $page1->cmid]));
        $PAGE->set_pagetype('mod-page-view');
        $PAGE->set_context(\context_module::instance($page1->cmid));

        $html = $this->execute_hook_and_get_html();
        $this->assertStringNotContainsString('local_smart_resume/main', $html);
    }

    /**
     * Test that execute does not throw and does not inject when course is not set on the page.
     */
    public function test_execute_handles_unset_course_gracefully(): void {
        global $PAGE, $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        set_config('enable', 1, 'local_smart_resume');

        $student = $this->getDataGenerator()->create_user();
        $this->setUser($student);

        // Reset course on page to simulate context without course.
        $reflection = new \ReflectionClass($PAGE);
        if ($reflection->hasProperty('_course')) {
            $property = $reflection->getProperty('_course');
            $property->setAccessible(true);
            $property->setValue($PAGE, null);
        }

        $html = $this->execute_hook_and_get_html();
        $this->assertStringNotContainsString('local_smart_resume/main', $html);
    }

    /**
     * Test that execute handles service exceptions gracefully without throwing or injecting.
     */
    public function test_execute_handles_service_exception_gracefully(): void {
        global $PAGE, $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        set_config('enable', 1, 'local_smart_resume');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->enrol_and_login($course, 'student');
        $this->set_course_page($course);

        // Provoke an invalid state for completion to simulate service failure.
        $corruptedcourse = clone $course;
        $corruptedcourse->id = 999999;
        $PAGE->set_course($corruptedcourse);

        // Execute must not throw exception and must log debugging.
        $html = $this->execute_hook_and_get_html();
        $this->assertDebuggingCalled();
        $this->assertStringNotContainsString('local_smart_resume/main', $html);
    }

    /**
     * Point $PAGE at the course view page of the given course.
     *
     * @param \stdClass $course
     */
    private function set_course_page(\stdClass $course): void {
        global $PAGE;

        $PAGE->set_course($course);
        $PAGE->set_url(new \moodle_url('/course/view.php', ['id' => $course->id]));
        $PAGE->set_pagetype('course-view-' . $course->format);
        $PAGE->set_context(\context_course::instance($course->id));
    }

    /**
     * Create a user, enrol it in the course with the given role and log it in.
     *
     * @param \stdClass $course
     * @param string $role Role shortname.
     * @return \stdClass The enrolled user.
     */
    private function enrol_and_login(\stdClass $course, string $role): \stdClass {
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $role);
        $this->setUser($user);

        return $user;
    }

    /**
     * Execute the before_footer callback and return the end-of-body HTML of $PAGE.
     *
     * @return string
     */
    private function execute_hook_and_get_html(): string {
        global $PAGE;

        $renderer = $PAGE->get_renderer('core');
        $hook = new \core\hook\output\before_footer_html_generation($renderer);

        \local_smart_resume\hook\before_footer::execute($hook);

        return $PAGE->requires->get_end_code();
    }
}
