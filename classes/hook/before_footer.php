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
        global $PAGE, $USER;

        // Security: Ensure user is logged in and not a guest.
        if (!isloggedin() || isguestuser()) {
            return;
        }

        if (!self::is_course_view_page($PAGE)) {
            return;
        }

        $course = self::resolve_active_course($PAGE);
        if ($course === null) {
            return;
        }

        // Check if the plugin is globally enabled.
        if (!get_config('local_smart_resume', 'enable')) {
            return;
        }

        if (self::is_manager_preview_blocked($course)) {
            return;
        }

        $firstincompletecmid = self::resolve_incomplete_cmid($course, (int) $USER->id);
        if ($firstincompletecmid === null) {
            return;
        }

        // Prepare localized string for client-side DOM rendering.
        $nextactivitystring = get_string('nextactivity', 'local_smart_resume');

        $strings = [
            'nextactivity' => $nextactivitystring,
        ];

        $PAGE->requires->js_call_amd('local_smart_resume/main', 'init', [$strings, $firstincompletecmid]);
    }

    /**
     * Checks if the current page represents a course view page.
     *
     * @param \moodle_page $page The current page.
     * @return bool True if the current page is a course view page.
     */
    private static function is_course_view_page(\moodle_page $page): bool {
        return self::matches_course_pagetype($page) || self::matches_course_url($page);
    }

    /**
     * Checks if the page pagetype corresponds to course view.
     *
     * @param \moodle_page $page The current page.
     * @return bool True if pagetype matches.
     */
    private static function matches_course_pagetype(\moodle_page $page): bool {
        try {
            if (!empty($page->pagetype) && str_starts_with($page->pagetype, 'course-view-')) {
                return true;
            }
        } catch (\Throwable $e) {
            debugging('local_smart_resume: unable to read page pagetype: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        return false;
    }

    /**
     * Checks if the page URL corresponds to course view.
     *
     * @param \moodle_page $page The current page.
     * @return bool True if page URL matches course view.
     */
    private static function matches_course_url(\moodle_page $page): bool {
        try {
            static $courseviewurl = null;
            $courseviewurl ??= new \moodle_url('/course/view.php');
            if ($page->has_set_url() && $page->url->compare($courseviewurl, URL_MATCH_BASE)) {
                return true;
            }
        } catch (\Throwable $e) {
            debugging('local_smart_resume: unable to compare page url: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        return false;
    }

    /**
     * Resolves the active course object from the page if valid and non-frontpage.
     *
     * @param \moodle_page $page The current page.
     * @return \stdClass|null The course object or null.
     */
    private static function resolve_active_course(\moodle_page $page): ?\stdClass {
        try {
            $course = $page->course;
            if (empty($course) || empty($course->id) || (int) $course->id === SITEID) {
                return null;
            }
            return $course;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Checks if the current user is a course manager with preview disabled.
     *
     * @param \stdClass $course The course object.
     * @return bool True if preview is blocked for this manager.
     */
    private static function is_manager_preview_blocked(\stdClass $course): bool {
        try {
            $coursecontext = \context_course::instance($course->id);
        } catch (\Throwable $e) {
            debugging('local_smart_resume: unable to resolve course context: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return true;
        }

        $ismanager = has_capability('moodle/course:update', $coursecontext) ||
            has_capability('moodle/course:manageactivities', $coursecontext);
        $previewenabled = (bool) get_config('local_smart_resume', 'preview_for_admins');

        return $ismanager && !$previewenabled;
    }

    /**
     * Resolves the first incomplete course module ID for the given user.
     *
     * @param \stdClass $course The course object.
     * @param int $userid The user ID.
     * @return int|null The cmid or null if none.
     */
    private static function resolve_incomplete_cmid(\stdClass $course, int $userid): ?int {
        try {
            $cmid = \local_smart_resume\service\resume_finder::get_first_incomplete_cmid($course, $userid);
            if (!is_int($cmid) || $cmid <= 0) {
                return null;
            }
            return $cmid;
        } catch (\Throwable $e) {
            debugging('local_smart_resume service error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }
}
