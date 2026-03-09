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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Smart Resume plugin before_footer hook.
 *
 * @package    local_smart_resume
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smart_resume\hook;

use core\hook\output\before_footer_html_generation;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook to inject Smart Resume AMD module on course view pages.
 */
class before_footer {
    /**
     * Callback for the before_footer_html_generation hook.
     *
     * @param before_footer_html_generation $hook
     */
    public static function execute(before_footer_html_generation $hook): void {
        global $PAGE, $USER, $CFG;

        // Check if plugin is enabled globally
        if (!get_config('local_smart_resume', 'enable')) {
            return;
        }

        // Check if we are on the main course page
        if ($PAGE->pagelayout === 'course' && strpos($PAGE->url->get_path(), '/course/view.php') !== false) {
            // Prepare strings for JS
            $strings = [
                'nextactivity' => get_string('nextactivity', 'local_smart_resume')
            ];

            // Determine the first incomplete activity
            $first_incomplete_cmid = null;
            $course = $PAGE->course;

            // Ensure completion lib is loaded
            require_once($CFG->libdir.'/completionlib.php');
            $completion = new \completion_info($course);

            if ($completion->is_enabled()) {
                $modinfo = get_fast_modinfo($course);
                $cms = $modinfo->get_cms();
                foreach ($cms as $cm) {
                    // Skip if not visible
                    if (!$cm->uservisible) {
                        continue;
                    }
                    // Skip if completion is not tracked for this activity
                    if ($cm->completion == COMPLETION_TRACKING_NONE) {
                        continue;
                    }
                    // Get completion data for current user
                    $completiondata = $completion->get_data($cm, true, $USER->id);
                    // Check if incomplete (0)
                    if ($completiondata->completionstate == COMPLETION_INCOMPLETE) {
                        $first_incomplete_cmid = $cm->id;
                        break;
                    }
                }
            }
            // Initialize the AMD module with the calculated ID
            $PAGE->requires->js_call_amd('local_smart_resume/main', 'init', [$strings, $first_incomplete_cmid]);
        }
    }
}