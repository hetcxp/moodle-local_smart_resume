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
 * Interface for services resolving the resume course module for a user.
 *
 * @package    local_smart_resume
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface resume_finder_interface {
    /**
     * Resolves the first visible and incomplete course module with completion enabled.
     *
     * @param \stdClass $course The course object.
     * @param int $userid The user ID.
     * @return int|null The cmid of the first incomplete module or null if none.
     */
    public function find_first_incomplete_cmid(\stdClass $course, int $userid): ?int;
}
