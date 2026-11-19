# Changelog

All notable changes to `local_smart_resume` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.6.0] - 2026-11-19

### Added
- Privacy API unit tests in `tests/privacy/provider_test.php` extending `\core_privacy\tests\provider_testcase` to verify `null_provider` compliance and `privacy:metadata` string resolution (F-06).
- PHPUnit coverage for `mod_subsection` visual ordering, hidden parent sections, strict `$userid` visibility evaluation, non-enrolled access prevention, admin preview bypass, and restricted section pagetypes.
- Official GNU GPL v3.0 `LICENSE` file and `CHANGES.md` release history (F-07).
- Expanded GitHub Actions CI matrix using `moodlehq/moodle-plugin-ci` v4 across `MOODLE_405_STABLE` (Moodle 4.5 LTS) and `MOODLE_501_STABLE` / `main` (Moodle 5.x) on PHP 8.3, executing `phplint`, `phpmd`, `codechecker`, `validate`, `savepoints`, `mustache`, `grunt`, `phpunit`, and `behat` (F-10).

### Changed
- Bumped `$plugin->version` to `2026111900`, `$plugin->release` to `'1.6.0'`, and `$plugin->requires` to `2024100700` (Moodle 4.5.0 LTS) with `$plugin->supported = [405, 503]` (F-01, F-11).
- Updated `resume_finder::find_first_incomplete_cmid()` to traverse top-level sections in visual order (`get_section_info_all()`) and recursively descend into `mod_subsection` delegated sections (`get_delegated_section_info()`), verifying `$section->uservisible` and `!$section->is_orphan()` on both parent and delegated sections (F-02).
- Updated `resume_finder` to guard against `$userid <= 0` and pass `$userid` explicitly to `get_fast_modinfo($course, $userid)` (F-04).
- Added strict PHP 8.3 `#[\Override]` attributes across implemented/overridden methods in `provider`, `resume_finder`, and PHPUnit `tearDown()` methods (F-05).
- Hardened `before_footer` hook to enforce `can_access_course()` checks, exclude `*-restricted` course section pagetypes, and scope injection on single-section pages (`/course/section.php`) to the viewed section (F-03).
- Restricted AMD fallback DOM selector in `amd/src/main.js` to `.activity[data-cmid], li[data-cmid]` to prevent matching inner action menus, and recompiled `amd/build/main.min.js` + source map (F-08).
- Updated `README.md` with accurate repository URL, Moodle 4.5 LTS – 5.3 requirements (`public/` layout note), updated PHPUnit/Behat test counts, and English summary; updated `.gitattributes` to exclude `MARKETPLACE_LISTING.md` and `pix/marketplace_icon_*.png` from release archives (F-07).

## [1.5.0] - 2026-10-16

### Added
- Decoupled `\local_smart_resume\service\resume_finder_interface` contract and per-request static caching in `resume_finder`.
- Release archive exclusions via `.gitattributes` and `.gitignore`.

### Changed
- Replaced server-side Mustache/renderer output with client-side DOM badge construction (`textContent`) in `amd/src/main.js` and hardened CSS badge styling in `styles.css`.
- Refactored `before_footer` hook and expanded PHPUnit test suites (`tests/before_footer_test.php`, `tests/resume_finder_test.php`).
