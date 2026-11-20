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
 * @covers     \local_smart_resume\hook\before_footer
 */
final class before_footer_test extends \advanced_testcase {
    #[\Override]
    protected function tearDown(): void {
        \local_smart_resume\service\resume_finder::clear_cache();
        parent::tearDown();
    }

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

    public function test_execute_evaluates_manager_capabilities_in_course_context(): void {
        global $CFG, $PAGE;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        set_config('enable', 1, 'local_smart_resume');
        set_config('preview_for_admins', 0, 'local_smart_resume');

        $course1 = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $course2 = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $page1 = $this->getDataGenerator()->create_module('page', ['course' => $course1->id, 'completion' => 1]);

        // User is editingteacher in course2, but student in course1.
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course2->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
        $this->setUser($user);

        $this->set_course_page($course1);
        // Explicitly set PAGE context to course2 to verify isolation.
        $PAGE->set_context(\context_course::instance($course2->id));

        $html = $this->execute_hook_and_get_html();
        $this->assertStringContainsString('local_smart_resume/main', $html);
        $this->assertStringContainsString((string)$page1->cmid, $html);
    }

    public function test_execute_does_not_inject_for_unenrolled_authenticated_user(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        set_config('enable', 1, 'local_smart_resume');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->set_course_page($course);

        $html = $this->execute_hook_and_get_html();
        $this->assertStringNotContainsString('local_smart_resume/main', $html);
    }

    public function test_execute_does_not_inject_for_suspended_enrolled_user(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        set_config('enable', 1, 'local_smart_resume');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user(
            $user->id,
            $course->id,
            'student',
            'manual',
            0,
            0,
            ENROL_USER_SUSPENDED
        );
        $this->setUser($user);
        $this->set_course_page($course);

        $html = $this->execute_hook_and_get_html();
        $this->assertStringNotContainsString('local_smart_resume/main', $html);
    }

    public function test_execute_does_not_inject_for_hidden_course_to_enrolled_student(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        set_config('enable', 1, 'local_smart_resume');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1, 'visible' => 0]);
        $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);

        $this->enrol_and_login($course, 'student');
        $this->set_course_page($course);

        $html = $this->execute_hook_and_get_html();
        $this->assertStringNotContainsString('local_smart_resume/main', $html);
    }

    public function test_execute_allows_preview_for_unenrolled_admin_when_enabled(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        set_config('enable', 1, 'local_smart_resume');
        set_config('preview_for_admins', 1, 'local_smart_resume');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $page1 = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);

        $manager = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign('manager', $manager->id);
        $this->setUser($manager);
        $this->set_course_page($course);

        $html = $this->execute_hook_and_get_html();
        $this->assertStringContainsString('local_smart_resume/main', $html);
        $this->assertStringContainsString((string)$page1->cmid, $html);
    }

    public function test_execute_does_not_inject_on_restricted_section_pagetype(): void {
        global $CFG, $PAGE;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        set_config('enable', 1, 'local_smart_resume');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1, 'format' => 'topics', 'numsections' => 1]);
        $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'section' => 1, 'completion' => 1]);

        $this->enrol_and_login($course, 'student');
        $this->set_course_page($course);
        $PAGE->set_pagetype('course-view-section-' . $course->format . '-restricted');

        $html = $this->execute_hook_and_get_html();
        $this->assertStringNotContainsString('local_smart_resume/main', $html);
    }

    public function test_execute_does_not_inject_on_arbitrary_course_view_subpagetype(): void {
        global $CFG, $PAGE;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        set_config('enable', 1, 'local_smart_resume');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1, 'format' => 'topics']);
        $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);

        $this->enrol_and_login($course, 'student');
        $this->set_course_page($course);
        $PAGE->set_pagetype('course-view-' . $course->format . '-extra');

        $html = $this->execute_hook_and_get_html();
        $this->assertStringNotContainsString('local_smart_resume/main', $html);
    }

    public function test_execute_injects_on_single_section_page_when_target_in_section(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        set_config('enable', 1, 'local_smart_resume');

        $course = $this->getDataGenerator()->create_course([
            'enablecompletion' => 1,
            'format' => 'topics',
            'numsections' => 2,
        ]);
        $page1 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => 1,
            'completion' => 1,
        ]);
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => 2,
            'completion' => 1,
        ]);

        $this->enrol_and_login($course, 'student');
        $section1 = get_fast_modinfo($course)->get_section_info(1);
        $this->set_single_section_page($course, (int)$section1->id);

        $html = $this->execute_hook_and_get_html();
        $this->assertStringContainsString('local_smart_resume/main', $html);
        $this->assertStringContainsString((string)$page1->cmid, $html);
    }

    public function test_execute_does_not_inject_on_single_section_page_when_target_in_other_section(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        set_config('enable', 1, 'local_smart_resume');

        $course = $this->getDataGenerator()->create_course([
            'enablecompletion' => 1,
            'format' => 'topics',
            'numsections' => 2,
        ]);
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => 1,
            'completion' => 1,
        ]);
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => 2,
            'completion' => 1,
        ]);

        $this->enrol_and_login($course, 'student');
        $section2 = get_fast_modinfo($course)->get_section_info(2);
        $this->set_single_section_page($course, (int)$section2->id);

        $html = $this->execute_hook_and_get_html();
        $this->assertStringNotContainsString('local_smart_resume/main', $html);
    }

    public function test_execute_injects_on_single_section_page_when_target_in_delegated_subsection(): void {
        global $CFG;

        if (!\core_component::get_plugin_directory('mod', 'subsection')) {
            $this->markTestSkipped('mod_subsection is not available in this Moodle version.');
        }

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        set_config('enable', 1, 'local_smart_resume');

        $modmanager = \core_plugin_manager::resolve_plugininfo_class('mod');
        $modmanager::enable_plugin('subsection', 1);

        $course = $this->getDataGenerator()->create_course([
            'enablecompletion' => 1,
            'format' => 'topics',
            'numsections' => 1,
        ]);
        $subsection = $this->getDataGenerator()->create_module('subsection', [
            'course' => $course->id,
            'section' => 1,
        ]);

        $modinfo = get_fast_modinfo($course);
        $delegatedsection = $modinfo->get_cm($subsection->cmid)->get_delegated_section_info();
        $this->assertNotNull($delegatedsection);

        $page1 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => $delegatedsection->section,
            'completion' => 1,
        ]);

        $this->enrol_and_login($course, 'student');
        $section1 = get_fast_modinfo($course)->get_section_info(1);
        $this->set_single_section_page($course, (int)$section1->id);

        $html = $this->execute_hook_and_get_html();
        $this->assertStringContainsString('local_smart_resume/main', $html);
        $this->assertStringContainsString((string)$page1->cmid, $html);
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
     * Point $PAGE at the single-section page of the given course section.
     *
     * @param \stdClass $course
     * @param int $sectionid
     */
    private function set_single_section_page(\stdClass $course, int $sectionid): void {
        global $PAGE;

        $PAGE->set_course($course);
        $PAGE->set_url(new \moodle_url('/course/section.php', ['id' => $sectionid]));
        $PAGE->set_pagetype('course-view-section-' . $course->format);
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
