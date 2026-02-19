<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_smartresume', get_string('pluginname', 'local_smartresume'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_smartresume/enable',
        'Enable Smart Resume', // Temporary hardcoded string to bypass cache error
        'If enabled, the plugin will automatically highlight and scroll to the first incomplete activity in courses.', // Temporary hardcoded string
        1 // Default to enabled
    ));
}
