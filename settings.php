<?php
defined('MOODLE_INTERNAL') || die();

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
