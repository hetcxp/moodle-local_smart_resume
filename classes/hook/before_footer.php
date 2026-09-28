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

        // Security: Ensure user is logged in and not a guest.
        if (!isloggedin() || isguestuser()) {
            return;
        }

        // Only execute on course view pages.
        $ispagetypecourse = false;
        try {
            if (!empty($PAGE->pagetype) && str_starts_with($PAGE->pagetype, 'course-view-')) {
                $ispagetypecourse = true;
            }
        } catch (\Throwable $e) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
            // Pagetype might not be set in some contexts.
        }

        $isurlcourse = false;
        try {
            if ($PAGE->has_set_url() && $PAGE->url->compare(new \moodle_url('/course/view.php'), URL_MATCH_BASE)) {
                $isurlcourse = true;
            }
        } catch (\Throwable $e) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
            // URL might not be set.
        }

        if (!$ispagetypecourse && !$isurlcourse) {
            return;
        }

        // Ensure course is set and not frontpage.
        try {
            $course = $PAGE->course;
            if (empty($course) || empty($course->id) || $course->id == SITEID) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        // Check if the plugin is globally enabled.
        if (!get_config('local_smart_resume', 'enable')) {
            return;
        }

        $ismanager = has_capability('moodle/course:update', $PAGE->context) ||
            has_capability('moodle/course:manageactivities', $PAGE->context);
        $previewenabled = (bool) get_config('local_smart_resume', 'preview_for_admins');
        if ($ismanager && !$previewenabled) {
            return;
        }

        // Resolve first incomplete module via decoupled service.
        $firstincompletecmid = \local_smart_resume\service\resume_finder::get_first_incomplete_cmid(
            $course,
            (int) $USER->id
        );

        if ($firstincompletecmid === null) {
            return;
        }

        // Prepare strings for JS.
        $nextactivitystring = get_string('nextactivity', 'local_smart_resume');

        // Output API (Renderable & Templatable).
        $renderable = new \local_smart_resume\output\resume_label($nextactivitystring, $firstincompletecmid);
        $renderer = $PAGE->get_renderer('local_smart_resume');

        try {
            $labelhtml = $renderer->render($renderable);
        } catch (\Exception $e) {
            debugging($e->getMessage(), DEBUG_DEVELOPER);
            return;
        }

        // ESM (JavaScript Modules).
        $strings = [
            'nextactivity' => $labelhtml,
        ];

        $PAGE->requires->js_call_amd('local_smart_resume/main', 'init', [$strings, $firstincompletecmid]);
    }
}
