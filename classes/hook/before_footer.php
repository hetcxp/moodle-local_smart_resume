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
 * Smart Resume plugin before_footer hook.
 *
 * @package    local_smart_resume
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smart_resume\hook;

defined('MOODLE_INTERNAL') || die();

use core\hook\output\before_footer_html_generation;

/**
 * Hook to inject Smart Resume AMD module on course view pages.
 *
 * @package    local_smart_resume
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class before_footer {
    /**
     * Callback for the before_footer_html_generation hook.
     *
     * @param before_footer_html_generation $hook
     */
    public static function execute(before_footer_html_generation $hook): void {
        global $PAGE, $USER, $CFG;

        // Security: Ensure it's a real course page (not frontpage ID 1) and user is logged in.
        if ($PAGE->course->id == SITEID || !isloggedin() || isguestuser()) {
            return;
        }

        // Check if the plugin is globally enabled.
        if (!get_config('local_smart_resume', 'enable')) {
            return;
        }

        // Feature: Only target students (exclude users with editing/management capabilities).
        if (has_capability('moodle/course:update', $PAGE->context) || has_capability('moodle/course:manageactivities', $PAGE->context)) {
            return;
        }

        // Check if completion is enabled.
        if (empty($CFG->enablecompletion)) {
            return;
        }

        $course = $PAGE->course;
        require_once($CFG->libdir.'/completionlib.php');
        $completion = new \completion_info($course);

        if (!$completion->is_enabled()) {
            return;
        }

        $modinfo = get_fast_modinfo($course);
        $cms = $modinfo->get_cms();
        
        $first_incomplete_cmid = null;

        foreach ($cms as $cm) {
            // Check if this activity IS trackable according to the completion API.
            if (!$cm->uservisible || !$completion->is_enabled($cm)) {
                continue;
            }
            
            $completion_data = $completion->get_data($cm, true, $USER->id);
            
            if ($completion_data->completionstate == COMPLETION_INCOMPLETE) {
                $first_incomplete_cmid = $cm->id;
                break;
            }
        }

        if ($first_incomplete_cmid === null) {
            return;
        }

        // Preparar strings para el JS.
        $nextactivitystring = get_string('nextactivity', 'local_smart_resume');

        // Pillar 2: Output API (Renderable & Templatable).
        $renderable = new \local_smart_resume\output\resume_label($nextactivitystring, $first_incomplete_cmid);
        $renderer = $PAGE->get_renderer('local_smart_resume');
        
        try {
            $labelhtml = $renderer->render($renderable);
        } catch (\Exception $e) {
            debugging($e->getMessage(), DEBUG_DEVELOPER);
            return;
        }

        // Pillar 3: ESM (JavaScript Modules).
        $strings = [
            'nextactivity' => $labelhtml
        ];

        $PAGE->requires->js_call_amd('local_smart_resume/main', 'init', [$strings, $first_incomplete_cmid]);
    }
}