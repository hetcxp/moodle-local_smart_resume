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

namespace local_smart_resume\service;

/**
 * Service to resolve the first incomplete course module for a user.
 *
 * @package    local_smart_resume
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resume_finder implements resume_finder_interface {
    /**
     * @var array<string, int|null> Request-level cache indexed by courseid:userid for request lifetime.
     */
    private static array $requestcache = [];

    /**
     * Resolves the first visible and incomplete course module with completion enabled.
     *
     * @param \stdClass $course The course object.
     * @param int $userid The user ID.
     * @return int|null The cmid of the first incomplete module or null if none.
     */
    #[\Override]
    public function find_first_incomplete_cmid(\stdClass $course, int $userid): ?int {
        global $CFG;

        if (empty($course->id) || $userid <= 0 || empty($CFG->enablecompletion)) {
            return null;
        }

        $cachekey = (int) $course->id . ':' . $userid;
        if (array_key_exists($cachekey, self::$requestcache)) {
            return self::$requestcache[$cachekey];
        }

        require_once($CFG->libdir . '/completionlib.php');
        $completion = new \completion_info($course);

        if (!$completion->is_enabled()) {
            self::$requestcache[$cachekey] = null;
            return null;
        }

        $modinfo = $this->fetch_modinfo($course, $userid);
        $cmid = $this->scan_incomplete_cmid($modinfo, $completion, $userid);
        self::$requestcache[$cachekey] = $cmid;
        return $cmid;
    }

    /**
     * Resolves the course modinfo object for a target user.
     *
     * @param \stdClass $course The course object.
     * @param int $userid The user ID.
     * @return object The course modinfo instance.
     */
    protected function fetch_modinfo(\stdClass $course, int $userid): object {
        return get_fast_modinfo($course, $userid);
    }

    /**
     * Scans non-delegated sections in visual order, expanding delegated subsections inline.
     *
     * @param object $modinfo The course modinfo instance.
     * @param \completion_info $completion The completion info object.
     * @param int $userid The user ID.
     * @return int|null The cmid or null if none.
     */
    private function scan_incomplete_cmid(object $modinfo, \completion_info $completion, int $userid): ?int {
        if (!method_exists($modinfo, 'get_section_info_all')) {
            return null;
        }

        $visitedsectionids = [];
        foreach ($modinfo->get_section_info_all() as $section) {
            if ($this->is_delegated_section($section)) {
                continue;
            }
            $cmid = $this->scan_section($section, $modinfo, $completion, $userid, $visitedsectionids);
            if ($cmid !== null) {
                return $cmid;
            }
        }
        return null;
    }

    /**
     * Scans a section in sequence order and descends into delegated subsections when encountered.
     *
     * @param object $section Section info instance.
     * @param object $modinfo Course modinfo instance.
     * @param \completion_info $completion Completion info object.
     * @param int $userid Target user ID.
     * @param array<string, bool> $visitedsectionids Guard map preventing cyclic section traversal.
     * @return int|null The first incomplete cmid in visual order or null.
     */
    private function scan_section(
        object $section,
        object $modinfo,
        \completion_info $completion,
        int $userid,
        array &$visitedsectionids
    ): ?int {
        $sectionkey = $this->get_section_key($section);
        if (isset($visitedsectionids[$sectionkey])) {
            return null;
        }
        $visitedsectionids[$sectionkey] = true;

        if (!$this->is_section_accessible($section)) {
            return null;
        }

        foreach ($this->get_section_cms($section, $modinfo) as $cm) {
            $cmid = $this->resolve_cm_or_delegated($cm, $modinfo, $completion, $userid, $visitedsectionids);
            if ($cmid !== null) {
                return $cmid;
            }
        }
        return null;
    }

    /**
     * Checks a module for incomplete completion state or expands its delegated section inline.
     *
     * @param object $cm Course module instance.
     * @param object $modinfo Course modinfo instance.
     * @param \completion_info $completion Completion info object.
     * @param int $userid Target user ID.
     * @param array<string, bool> $visitedsectionids Guard map preventing cyclic section traversal.
     * @return int|null The matching cmid or null.
     */
    private function resolve_cm_or_delegated(
        object $cm,
        object $modinfo,
        \completion_info $completion,
        int $userid,
        array &$visitedsectionids
    ): ?int {
        if ($cm instanceof \cm_info && $this->is_module_eligible($cm, $completion)) {
            $completiondata = $completion->get_data($cm, true, $userid);
            if ((int) $completiondata->completionstate === COMPLETION_INCOMPLETE) {
                return (int) $cm->id;
            }
        }

        $delegated = $this->get_delegated_section($cm, $modinfo);
        if ($delegated !== null && $this->can_expand_delegated_section($cm)) {
            return $this->scan_section($delegated, $modinfo, $completion, $userid, $visitedsectionids);
        }

        return null;
    }

    /**
     * Checks whether a section is delegated to a component such as mod_subsection.
     *
     * @param object $section Section info instance.
     * @return bool True if delegated.
     */
    private function is_delegated_section(object $section): bool {
        if (method_exists($section, 'is_delegated')) {
            return (bool) $section->is_delegated();
        }
        if (method_exists($section, 'get_component_instance')) {
            return $section->get_component_instance() !== null;
        }
        return isset($section->component) && !empty($section->component);
    }

    /**
     * Checks whether a section is visible to the target user and not orphaned.
     *
     * @param object $section Section info instance.
     * @return bool True if accessible.
     */
    private function is_section_accessible(object $section): bool {
        if (method_exists($section, 'is_orphan') && $section->is_orphan()) {
            return false;
        }
        if (
            $this->is_delegated_section($section)
            && method_exists($section, 'get_component_instance')
            && $section->get_component_instance() === null
        ) {
            return false;
        }
        return !empty($section->uservisible);
    }

    /**
     * Returns a stable traversal key for a section info object.
     *
     * @param object $section Section info instance.
     * @return string Unique section key.
     */
    private function get_section_key(object $section): string {
        if (isset($section->id)) {
            return 'id:' . (int) $section->id;
        }
        return 'num:' . (int) $section->section;
    }

    /**
     * Returns course modules belonging to a section in visual sequence order.
     *
     * @param object $section Section info instance.
     * @param object $modinfo Course modinfo instance.
     * @return array List of course module instances.
     */
    private function get_section_cms(object $section, object $modinfo): array {
        if (method_exists($section, 'get_sequence_cm_infos')) {
            return $section->get_sequence_cm_infos();
        }

        $sections = method_exists($modinfo, 'get_sections') ? $modinfo->get_sections() : [];
        $cms = method_exists($modinfo, 'get_cms') ? $modinfo->get_cms() : [];
        $sectionnum = (int) $section->section;
        if (empty($sections[$sectionnum])) {
            return [];
        }

        $result = [];
        foreach ($sections[$sectionnum] as $cmid) {
            if (isset($cms[$cmid])) {
                $result[] = $cms[$cmid];
            }
        }
        return $result;
    }

    /**
     * Resolves the delegated section owned by a course module, if any.
     *
     * @param object $cm Course module instance.
     * @param object $modinfo Course modinfo instance.
     * @return object|null Delegated section info or null.
     */
    private function get_delegated_section(object $cm, object $modinfo): ?object {
        if (method_exists($cm, 'get_delegated_section_info')) {
            return $cm->get_delegated_section_info();
        }
        if (method_exists($modinfo, 'get_sections_delegated_by_cm')) {
            $delegated = $modinfo->get_sections_delegated_by_cm();
            return $delegated[$cm->id] ?? null;
        }
        return null;
    }

    /**
     * Checks whether a delegating module is visible on the course page for expansion.
     *
     * @param object $cm Course module instance.
     * @return bool True if the delegated subsection may be traversed.
     */
    private function can_expand_delegated_section(object $cm): bool {
        if (empty($cm->uservisible)) {
            return false;
        }
        if (method_exists($cm, 'is_visible_on_course_page') && !$cm->is_visible_on_course_page()) {
            return false;
        }
        if (method_exists($cm, 'is_stealth') && $cm->is_stealth()) {
            return false;
        }
        return true;
    }

    /**
     * Checks if a course module is visible and eligible for completion tracking.
     *
     * @param \cm_info $cm The course module.
     * @param \completion_info $completion The completion info object.
     * @return bool True if the module is eligible.
     */
    private function is_module_eligible(\cm_info $cm, \completion_info $completion): bool {
        if (method_exists($cm, 'get_section_info')) {
            $section = $cm->get_section_info();
            if (!$section || !$this->is_section_accessible($section)) {
                return false;
            }
        }

        return $cm->uservisible
            && $cm->is_visible_on_course_page()
            && !$cm->is_stealth()
            && $completion->is_enabled($cm);
    }

    /**
     * Backward-compatible static façade for resolving the first incomplete course module.
     *
     * @param \stdClass $course The course object.
     * @param int $userid The user ID.
     * @return int|null The cmid of the first incomplete module or null if none.
     */
    public static function get_first_incomplete_cmid(\stdClass $course, int $userid): ?int {
        return (new static())->find_first_incomplete_cmid($course, $userid);
    }

    /**
     * Clears the request-level cache. Intended for test isolation and mutation scenarios.
     *
     * @return void
     */
    public static function clear_cache(): void {
        self::$requestcache = [];
    }
}
