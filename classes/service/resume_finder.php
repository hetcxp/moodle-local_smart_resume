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
    public function find_first_incomplete_cmid(\stdClass $course, int $userid): ?int {
        global $CFG;

        if (empty($course->id) || empty($CFG->enablecompletion)) {
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

        $modinfo = get_fast_modinfo($course);
        $cmid = $this->scan_incomplete_cmid($modinfo->get_cms(), $completion, $userid);
        self::$requestcache[$cachekey] = $cmid;
        return $cmid;
    }

    /**
     * Scans course modules for the first eligible incomplete module.
     *
     * @param array $cms The course modules list.
     * @param \completion_info $completion The completion info object.
     * @param int $userid The user ID.
     * @return int|null The cmid or null if none.
     */
    private function scan_incomplete_cmid(array $cms, \completion_info $completion, int $userid): ?int {
        foreach ($cms as $cm) {
            if ($this->is_module_eligible($cm, $completion)) {
                $completiondata = $completion->get_data($cm, true, $userid);
                if ($completiondata->completionstate === COMPLETION_INCOMPLETE) {
                    return (int) $cm->id;
                }
            }
        }
        return null;
    }

    /**
     * Checks if a course module is visible and eligible for completion tracking.
     *
     * @param \cm_info $cm The course module.
     * @param \completion_info $completion The completion info object.
     * @return bool True if the module is eligible.
     */
    private function is_module_eligible(\cm_info $cm, \completion_info $completion): bool {
        return $cm->uservisible && $cm->is_visible_on_course_page() && !$cm->is_stealth() && $completion->is_enabled($cm);
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
