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
 * Privacy provider tests for local_smart_resume.
 *
 * @package    local_smart_resume
 * @category   test
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smart_resume\privacy;

use core_privacy\local\metadata\null_provider;
use core_privacy\manager;

/**
 * Unit tests for the local_smart_resume privacy provider.
 *
 * @package    local_smart_resume
 * @category   test
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Verify that the privacy provider implements null_provider and is discoverable.
     */
    public function test_implements_null_provider(): void {
        $classname = manager::get_provider_classname_for_component('local_smart_resume');
        $this->assertSame(provider::class, $classname);
        $this->assertTrue(is_subclass_of($classname, null_provider::class));
    }

    /**
     * Verify that get_reason returns an existing language string in local_smart_resume.
     */
    public function test_get_reason_returns_existing_lang_string(): void {
        $reason = provider::get_reason();
        $this->assertIsString($reason);
        $this->assertSame('privacy:metadata', $reason);
        $this->assertTrue(get_string_manager()->string_exists($reason, 'local_smart_resume'));

        $resolved = get_string($reason, 'local_smart_resume');
        $this->assertIsString($resolved);
        $this->assertNotSame('', trim($resolved));
    }
}
