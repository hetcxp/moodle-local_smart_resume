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
    #[\Override]
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
        $this->assertIsInt($cmid);
        $this->assertSame((int) $page2->cmid, $cmid);
    }

    public function test_resolves_incomplete_module_with_cold_completion_cache_and_db_record(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $this->setUser($user);

        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);

        $record = (object) [
            'coursemoduleid' => (int) $page->cmid,
            'userid' => (int) $user->id,
            'completionstate' => COMPLETION_INCOMPLETE,
            'viewed' => 0,
            'timemodified' => time(),
        ];

        $existing = $DB->get_record('course_modules_completion', [
            'coursemoduleid' => $page->cmid,
            'userid' => $user->id,
        ]);
        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('course_modules_completion', $record);
        } else {
            $DB->insert_record('course_modules_completion', $record);
        }

        \cache::make('core', 'completion')->purge();
        resume_finder::clear_cache();

        $cmid = resume_finder::get_first_incomplete_cmid($course, (int) $user->id);
        $this->assertIsInt($cmid);
        $this->assertSame((int) $page->cmid, $cmid);
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
        $this->assertIsInt($initialcmid);
        $this->assertSame((int) $page1->cmid, $initialcmid);

        // Mark page1 as complete.
        $completion = new \completion_info($course);
        $completion->update_state($cm1, COMPLETION_COMPLETE, $user->id);

        // Without clear_cache(), cached result remains page1->cmid.
        $cachedcmid = resume_finder::get_first_incomplete_cmid($course, (int) $user->id);
        $this->assertIsInt($cachedcmid);
        $this->assertSame((int) $page1->cmid, $cachedcmid);

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

        $this->assertIsInt($instanceresult);
        $this->assertIsInt($staticresult);
        $this->assertSame($instanceresult, $staticresult);
        $this->assertSame((int) $page->cmid, $instanceresult);
    }

    public function test_returns_null_for_non_positive_userid(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);

        $this->assertNull(resume_finder::get_first_incomplete_cmid($course, 0));
        $this->assertNull(resume_finder::get_first_incomplete_cmid($course, -5));

        $cmid = resume_finder::get_first_incomplete_cmid($course, (int) $student->id);
        $this->assertIsInt($cmid);
        $this->assertSame((int) $page->cmid, $cmid);
    }

    public function test_evaluates_visibility_for_target_userid_not_global_user(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        // Hidden activity is visible to global admin $USER, but not to target student.
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'completion' => 1,
            'visible' => 0,
        ]);
        $visiblepage = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'completion' => 1,
            'visible' => 1,
        ]);

        $this->setAdminUser();

        $cmid = resume_finder::get_first_incomplete_cmid($course, (int) $student->id);
        $this->assertIsInt($cmid);
        $this->assertSame((int) $visiblepage->cmid, $cmid);
    }

    public function test_ignores_activities_inside_hidden_parent_section_subsection(): void {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');

        $this->resetAfterTest();
        $this->enable_subsection_module();
        $CFG->enablecompletion = 1;

        $course = $this->getDataGenerator()->create_course([
            'format' => 'topics',
            'numsections' => 2,
            'enablecompletion' => 1,
        ]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $subsection = $this->getDataGenerator()->create_module('subsection', [
            'course' => $course->id,
            'section' => 1,
        ]);
        $delegatednum = $this->get_delegated_section_number($course, (int) $subsection->cmid);

        // Activity inside delegated subsection remains visible=1 in DB while parent section 1 is hidden.
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => $delegatednum,
            'completion' => 1,
            'visible' => 1,
        ]);
        $visiblepage = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => 2,
            'completion' => 1,
            'visible' => 1,
        ]);

        $parentsection = get_fast_modinfo($course)->get_section_info(1);
        course_update_section($course, $parentsection, ['visible' => 0]);

        $this->setUser($student);
        $cmid = resume_finder::get_first_incomplete_cmid($course, (int) $student->id);
        $this->assertIsInt($cmid);
        $this->assertSame((int) $visiblepage->cmid, $cmid);

        // Completing the visible section 2 activity must yield null, never exposing the hidden subsection child.
        $completion = new \completion_info($course);
        $cmvisible = get_coursemodule_from_instance('page', $visiblepage->id);
        $completion->update_state($cmvisible, COMPLETION_COMPLETE, $student->id);
        resume_finder::clear_cache();

        $this->assertNull(resume_finder::get_first_incomplete_cmid($course, (int) $student->id));
    }

    public function test_ignores_activities_inside_hidden_subsection(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->enable_subsection_module();
        $CFG->enablecompletion = 1;

        $course = $this->getDataGenerator()->create_course([
            'format' => 'topics',
            'numsections' => 1,
            'enablecompletion' => 1,
        ]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $subsection = $this->getDataGenerator()->create_module(
            'subsection',
            ['course' => $course->id, 'section' => 1],
            ['visible' => 0]
        );
        $delegatednum = $this->get_delegated_section_number($course, (int) $subsection->cmid);

        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => $delegatednum,
            'completion' => 1,
        ]);
        $aftersubsection = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => 1,
            'completion' => 1,
        ]);

        $this->setUser($student);
        $cmid = resume_finder::get_first_incomplete_cmid($course, (int) $student->id);
        $this->assertIsInt($cmid);
        $this->assertSame((int) $aftersubsection->cmid, $cmid);
    }

    public function test_resolves_subsection_activity_in_visual_order(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->enable_subsection_module();
        $CFG->enablecompletion = 1;

        $course = $this->getDataGenerator()->create_course([
            'format' => 'topics',
            'numsections' => 2,
            'enablecompletion' => 1,
        ]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        // Visual order: section 1 [page1 -> subsection(subpage) -> page2], then section 2 [page3].
        $page1 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => 1,
            'completion' => 1,
        ]);
        $subsection = $this->getDataGenerator()->create_module('subsection', [
            'course' => $course->id,
            'section' => 1,
        ]);
        $delegatednum = $this->get_delegated_section_number($course, (int) $subsection->cmid);
        $subpage = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => $delegatednum,
            'completion' => 1,
        ]);
        $page2 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => 1,
            'completion' => 1,
        ]);
        $page3 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => 2,
            'completion' => 1,
        ]);

        $completion = new \completion_info($course);

        // Step 1: page1 comes first.
        $this->assertSame((int) $page1->cmid, resume_finder::get_first_incomplete_cmid($course, (int) $student->id));

        // Step 2: after completing page1, subpage inside subsection must precede page2 and page3.
        $completion->update_state(get_coursemodule_from_instance('page', $page1->id), COMPLETION_COMPLETE, $student->id);
        resume_finder::clear_cache();
        $this->assertSame((int) $subpage->cmid, resume_finder::get_first_incomplete_cmid($course, (int) $student->id));

        // Step 3: after completing subpage, page2 in section 1 must precede page3 in section 2.
        $completion->update_state(get_coursemodule_from_instance('page', $subpage->id), COMPLETION_COMPLETE, $student->id);
        resume_finder::clear_cache();
        $this->assertSame((int) $page2->cmid, resume_finder::get_first_incomplete_cmid($course, (int) $student->id));

        // Step 4: after completing page2, page3 in section 2 is resolved.
        $completion->update_state(get_coursemodule_from_instance('page', $page2->id), COMPLETION_COMPLETE, $student->id);
        resume_finder::clear_cache();
        $this->assertSame((int) $page3->cmid, resume_finder::get_first_incomplete_cmid($course, (int) $student->id));
    }

    public function test_compatibility_with_and_without_delegated_section_apis(): void {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');

        $this->resetAfterTest();
        $CFG->enablecompletion = 1;

        // Branch A: Standard course without delegated sections (Moodle 4.5-compatible structure).
        $course = $this->getDataGenerator()->create_course([
            'format' => 'topics',
            'numsections' => 2,
            'enablecompletion' => 1,
        ]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $page1 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => 1,
            'completion' => 1,
        ]);
        $page2 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => 2,
            'completion' => 1,
        ]);
        $page3 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => 2,
            'completion' => 1,
        ]);

        $section1 = get_fast_modinfo($course)->get_section_info(1);
        course_update_section($course, $section1, ['visible' => 0]);

        $completion = new \completion_info($course);
        $completion->update_state(get_coursemodule_from_instance('page', $page2->id), COMPLETION_COMPLETE, $student->id);

        $this->setUser($student);
        $standardcmid = resume_finder::get_first_incomplete_cmid($course, (int) $student->id);
        $this->assertIsInt($standardcmid);
        $this->assertSame((int) $page3->cmid, $standardcmid);

        // Branch B: Legacy modinfo/section doubles lacking Moodle 4.5+/5.0+ delegated section methods.
        resume_finder::clear_cache();
        $realmodinfo = get_fast_modinfo($course, (int) $student->id);
        $realcms = $realmodinfo->get_cms();
        $realsection1 = $realmodinfo->get_section_info(1);
        $realsection2 = $realmodinfo->get_section_info(2);

        $legacymodinfo = $this->build_legacy_modinfo_stub(
            [
                1 => (object) ['id' => (int) $realsection1->id, 'section' => 1, 'uservisible' => false],
                2 => (object) ['id' => (int) $realsection2->id, 'section' => 2, 'uservisible' => true],
            ],
            [
                1 => [(int) $page1->cmid],
                2 => [(int) $page2->cmid, 999999, (int) $page3->cmid],
            ],
            $realcms + [999999 => (object) ['id' => 999999, 'uservisible' => true]]
        );

        $legacyfinder = new class ($legacymodinfo) extends resume_finder {
            /** @var object Legacy modinfo stub. */
            private object $legacymodinfo;

            /**
             * Create a test finder returning the provided legacy modinfo double.
             *
             * @param object $legacymodinfo Legacy modinfo stub.
             */
            public function __construct(object $legacymodinfo) {
                $this->legacymodinfo = $legacymodinfo;
            }

            #[\Override]
            protected function fetch_modinfo(\stdClass $course, int $userid): object {
                return $this->legacymodinfo;
            }
        };

        $legacycmid = $legacyfinder->find_first_incomplete_cmid($course, (int) $student->id);
        $this->assertIsInt($legacycmid);
        $this->assertSame((int) $page3->cmid, $legacycmid);
    }

    /**
     * Ensures the mod_subsection activity plugin is enabled for subsection tests (disabled by default in Moodle 4.5).
     */
    private function enable_subsection_module(): void {
        if (!\core_component::get_plugin_directory('mod', 'subsection')) {
            $this->markTestSkipped('mod_subsection is not available in this Moodle version.');
        }

        $modmanager = \core_plugin_manager::resolve_plugininfo_class('mod');
        $modmanager::enable_plugin('subsection', 1);
    }

    /**
     * Resolves the delegated section number created by a mod_subsection course module.
     *
     * @param \stdClass $course Course record.
     * @param int $subsectioncmid Course module ID of the subsection module.
     * @return int Delegated section number.
     */
    private function get_delegated_section_number(\stdClass $course, int $subsectioncmid): int {
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($subsectioncmid);
        $delegated = $cm->get_delegated_section_info();
        $this->assertInstanceOf(\section_info::class, $delegated);

        return (int) $delegated->section;
    }

    /**
     * Builds a minimal legacy modinfo double without Moodle 5.0+ delegated section methods.
     *
     * @param array<int, object> $sections Legacy section objects indexed by section number.
     * @param array<int, int[]> $sectionsequences Map of section number to cmid list.
     * @param array<int, object> $cms Course module objects indexed by cmid.
     * @return object Legacy modinfo stub.
     */
    private function build_legacy_modinfo_stub(array $sections, array $sectionsequences, array $cms): object {
        return new class ($sections, $sectionsequences, $cms) {
            /** @var array<int, object> */
            private array $sections;

            /** @var array<int, int[]> */
            private array $sectionsequences;

            /** @var array<int, object> */
            private array $cms;

            /**
             * Constructor.
             *
             * @param array<int, object> $sections Legacy section objects.
             * @param array<int, int[]> $sectionsequences Section sequence map.
             * @param array<int, object> $cms Course module objects.
             */
            public function __construct(array $sections, array $sectionsequences, array $cms) {
                $this->sections = $sections;
                $this->sectionsequences = $sectionsequences;
                $this->cms = $cms;
            }

            /**
             * Returns section info list.
             *
             * @return array<int, object>
             */
            public function get_section_info_all(): array {
                return $this->sections;
            }

            /**
             * Returns section sequence map.
             *
             * @return array<int, int[]>
             */
            public function get_sections(): array {
                return $this->sectionsequences;
            }

            /**
             * Returns course modules map.
             *
             * @return array<int, object>
             */
            public function get_cms(): array {
                return $this->cms;
            }
        };
    }
}
