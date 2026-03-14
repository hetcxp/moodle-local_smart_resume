<?php
/**
 * Smart Resume plugin settings.
 *
 * @package    local_smart_resume
 * @copyright  2025 Héctor Eduardo Terán Canelones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// MOODLE_INTERNAL is already defined at line 10. No changes needed.

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_smart_resume', get_string('pluginname', 'local_smart_resume'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_smart_resume/enable',
        get_string('enable', 'local_smart_resume'),
        get_string('enable_desc', 'local_smart_resume'),
        1 // Default to enabled
    ));
}
