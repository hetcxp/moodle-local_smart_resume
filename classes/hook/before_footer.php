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

        if (!self::is_eligible_user() || !get_config('local_smart_resume', 'enable')) {
            return;
        }

        $pagetype = self::read_pagetype($PAGE);
        $course = self::resolve_accessible_course($PAGE, $pagetype, $USER);
        if ($course === null) {
            return;
        }

        $firstincompletecmid = self::resolve_visible_incomplete_cmid($PAGE, $pagetype, $course, (int) $USER->id);
        if ($firstincompletecmid === null) {
            return;
        }

        // Prepare localized string for client-side DOM rendering.
        $strings = [
            'nextactivity' => get_string('nextactivity', 'local_smart_resume'),
        ];

        $PAGE->requires->js_call_amd('local_smart_resume/main', 'init', [$strings, $firstincompletecmid]);
    }

    /**
     * Checks whether the current user is logged in and not a guest.
     *
     * @return bool True if user is authenticated and non-guest.
     */
    private static function is_eligible_user(): bool {
        return isloggedin() && !isguestuser();
    }

    /**
     * Resolves the active course if the page is an eligible view and the user can access it.
     *
     * @param \moodle_page $page The current page.
     * @param string|null $pagetype The resolved page pagetype.
     * @param \stdClass $user The current user object.
     * @return \stdClass|null The accessible course object or null.
     */
    private static function resolve_accessible_course(
        \moodle_page $page,
        ?string $pagetype,
        \stdClass $user
    ): ?\stdClass {
        if (!self::is_course_view_page($page, $pagetype)) {
            return null;
        }

        $course = self::resolve_active_course($page);
        if ($course === null) {
            return null;
        }

        $coursecontext = self::resolve_course_context($course);
        if ($coursecontext === null) {
            return null;
        }

        if (!self::can_user_access_course($course, $user) || self::is_manager_preview_blocked($coursecontext)) {
            return null;
        }

        return $course;
    }

    /**
     * Resolves the first incomplete cmid if it is visible in the current course/section view.
     *
     * @param \moodle_page $page The current page.
     * @param string|null $pagetype The resolved page pagetype.
     * @param \stdClass $course The course object.
     * @param int $userid The user ID.
     * @return int|null The target cmid or null.
     */
    private static function resolve_visible_incomplete_cmid(
        \moodle_page $page,
        ?string $pagetype,
        \stdClass $course,
        int $userid
    ): ?int {
        $cmid = self::resolve_incomplete_cmid($course, $userid);
        if ($cmid === null) {
            return null;
        }

        if (!self::is_target_visible_on_current_view($page, $pagetype, $course, $userid, $cmid)) {
            return null;
        }

        return $cmid;
    }

    /**
     * Reads the current page pagetype safely.
     *
     * @param \moodle_page $page The current page.
     * @return string|null The pagetype string (empty if unset), or null on error.
     */
    private static function read_pagetype(\moodle_page $page): ?string {
        try {
            $pagetype = $page->pagetype;
            return is_string($pagetype) ? trim($pagetype) : '';
        } catch (\Throwable $e) {
            debugging('local_smart_resume: unable to read page pagetype: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }

    /**
     * Checks if the current page represents an eligible course or section view page.
     *
     * @param \moodle_page $page The current page.
     * @param string|null $pagetype The resolved page pagetype.
     * @return bool True if the current page is eligible.
     */
    private static function is_course_view_page(\moodle_page $page, ?string $pagetype): bool {
        if ($pagetype === null) {
            return false;
        }
        if ($pagetype === '') {
            return self::matches_course_url($page);
        }
        if (str_ends_with($pagetype, '-restricted')) {
            return false;
        }
        if (self::is_main_course_pagetype($pagetype)) {
            return true;
        }
        if (self::is_single_section_pagetype($pagetype)) {
            return self::extract_positive_int_url_param($page, '/course/section.php', 'id') !== null;
        }
        if ($pagetype === 'course-view') {
            return self::matches_course_url($page);
        }
        return false;
    }

    /**
     * Checks if the pagetype matches the exact main course view allowlist (course-view-{format}).
     *
     * @param string $pagetype The page pagetype.
     * @return bool True if pagetype is a main course view pagetype.
     */
    private static function is_main_course_pagetype(string $pagetype): bool {
        return preg_match('/^course-view-[a-z][a-z0-9_]*$/', $pagetype) === 1;
    }

    /**
     * Checks if the pagetype matches the exact single-section view allowlist (course-view-section-{format}).
     *
     * @param string $pagetype The page pagetype.
     * @return bool True if pagetype is a single-section view pagetype.
     */
    private static function is_single_section_pagetype(string $pagetype): bool {
        return preg_match('/^course-view-section-[a-z][a-z0-9_]*$/', $pagetype) === 1;
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
     * Extracts a positive integer parameter from the page URL when its base path matches.
     *
     * @param \moodle_page $page The current page.
     * @param string $basepath Expected Moodle URL base path.
     * @param string $paramname Query parameter name.
     * @return int|null Positive integer value or null if absent/invalid.
     */
    private static function extract_positive_int_url_param(
        \moodle_page $page,
        string $basepath,
        string $paramname
    ): ?int {
        try {
            $baseurl = new \moodle_url($basepath);
            if (!$page->has_set_url() || !$page->url->compare($baseurl, URL_MATCH_BASE)) {
                return null;
            }
            $rawvalue = $page->url->get_param($paramname);
            if ($rawvalue === null || !ctype_digit((string) $rawvalue)) {
                return null;
            }
            $value = (int) $rawvalue;
            return $value > 0 ? $value : null;
        } catch (\Throwable $e) {
            debugging('local_smart_resume: unable to read url parameter: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
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
     * Resolves the course context safely.
     *
     * @param \stdClass $course The course object.
     * @return \context_course|null The course context or null on failure.
     */
    private static function resolve_course_context(\stdClass $course): ?\context_course {
        try {
            return \context_course::instance((int) $course->id);
        } catch (\Throwable $e) {
            debugging('local_smart_resume: unable to resolve course context: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }

    /**
     * Checks whether the user is permitted to access the course.
     *
     * @param \stdClass $course The course object.
     * @param \stdClass $user The user object.
     * @return bool True if the user can access the course.
     */
    private static function can_user_access_course(\stdClass $course, \stdClass $user): bool {
        try {
            return can_access_course($course, $user, '', true);
        } catch (\Throwable $e) {
            debugging('local_smart_resume: unable to verify course access: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Checks if the current user is a course manager with preview disabled.
     *
     * @param \context_course $coursecontext The course context.
     * @return bool True if preview is blocked for this manager.
     */
    private static function is_manager_preview_blocked(\context_course $coursecontext): bool {
        $ismanager = has_capability('moodle/course:update', $coursecontext) ||
            has_capability('moodle/course:manageactivities', $coursecontext);
        $previewenabled = (bool) get_config('local_smart_resume', 'preview_for_admins');

        return $ismanager && !$previewenabled;
    }

    /**
     * Checks whether the resolved target cmid is present in the currently viewed section when scoped.
     *
     * @param \moodle_page $page The current page.
     * @param string|null $pagetype The resolved pagetype.
     * @param \stdClass $course The course object.
     * @param int $userid The user ID.
     * @param int $cmid The target course module ID.
     * @return bool True if the target cmid belongs to the current view.
     */
    private static function is_target_visible_on_current_view(
        \moodle_page $page,
        ?string $pagetype,
        \stdClass $course,
        int $userid,
        int $cmid
    ): bool {
        if ($pagetype !== null && self::is_single_section_pagetype($pagetype)) {
            $sectionid = self::extract_positive_int_url_param($page, '/course/section.php', 'id');
            return $sectionid !== null && self::is_cmid_in_section($course, $userid, $cmid, $sectionid, null);
        }

        $sectionnum = self::extract_positive_int_url_param($page, '/course/view.php', 'section');
        if ($sectionnum !== null) {
            return self::is_cmid_in_section($course, $userid, $cmid, null, $sectionnum);
        }

        return true;
    }

    /**
     * Checks whether a course module belongs to the specified section or its delegated subsections.
     *
     * @param \stdClass $course The course object.
     * @param int $userid The user ID.
     * @param int $cmid The course module ID.
     * @param int|null $sectionid Section record ID, if scoping by ID.
     * @param int|null $sectionnum Section number, if scoping by number.
     * @return bool True if the cmid resides in the section or a delegated subsection.
     */
    private static function is_cmid_in_section(
        \stdClass $course,
        int $userid,
        int $cmid,
        ?int $sectionid,
        ?int $sectionnum
    ): bool {
        try {
            $modinfo = get_fast_modinfo($course, $userid);
            if (!isset($modinfo->cms[$cmid])) {
                return false;
            }
            $targetcm = $modinfo->get_cm($cmid);
            $sectioninfo = $sectionid !== null
                ? $modinfo->get_section_info_by_id($sectionid, IGNORE_MISSING)
                : $modinfo->get_section_info($sectionnum, IGNORE_MISSING);
            if (!$sectioninfo) {
                return false;
            }
            if ((int) $targetcm->section === (int) $sectioninfo->id) {
                return true;
            }
            return self::is_cm_in_delegated_subsection($targetcm, $sectioninfo);
        } catch (\Throwable $e) {
            debugging('local_smart_resume: unable to verify section visibility: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Recursively checks if the target course module belongs to a delegated subsection of the section.
     *
     * @param \cm_info $targetcm Target course module.
     * @param \section_info $sectioninfo Parent section info.
     * @param array<int, bool> $visited Visited section IDs to prevent cycles.
     * @return bool True if found inside a delegated subsection.
     */
    private static function is_cm_in_delegated_subsection(
        \cm_info $targetcm,
        \section_info $sectioninfo,
        array &$visited = []
    ): bool {
        $sectionid = (int) $sectioninfo->id;
        if (isset($visited[$sectionid]) || !method_exists($sectioninfo, 'get_sequence_cm_infos')) {
            return false;
        }
        $visited[$sectionid] = true;

        foreach ($sectioninfo->get_sequence_cm_infos() as $cm) {
            if (!method_exists($cm, 'get_delegated_section_info')) {
                continue;
            }
            $delegated = $cm->get_delegated_section_info();
            if (!$delegated) {
                continue;
            }
            if ((int) $delegated->id === (int) $targetcm->section) {
                return true;
            }
            if (self::is_cm_in_delegated_subsection($targetcm, $delegated, $visited)) {
                return true;
            }
        }
        return false;
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
