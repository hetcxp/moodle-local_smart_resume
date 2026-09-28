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
class resume_finder {
    /**
     * Resolves the first visible and incomplete course module with completion enabled.
     *
     * @param \stdClass $course The course object.
     * @param int $userid The user ID.
     * @return int|null The cmid of the first incomplete module or null if none.
     */
    public static function get_first_incomplete_cmid(\stdClass $course, int $userid): ?int {
        global $CFG;

        if (empty($CFG->enablecompletion)) {
            return null;
        }

        require_once($CFG->libdir . '/completionlib.php');
        $completion = new \completion_info($course);

        if (!$completion->is_enabled()) {
            return null;
        }

        $modinfo = get_fast_modinfo($course);
        $cms = $modinfo->get_cms();

        foreach ($cms as $cm) {
            // Check visibility on course page, stealth status, user visibility, and completion tracking.
            if (!$cm->uservisible || !$cm->is_visible_on_course_page() || $cm->is_stealth() || !$completion->is_enabled($cm)) {
                continue;
            }

            $completiondata = $completion->get_data($cm, true, $userid);

            if ($completiondata->completionstate == COMPLETION_INCOMPLETE) {
                return (int) $cm->id;
            }
        }

        return null;
    }
}
