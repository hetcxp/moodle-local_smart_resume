<?php
namespace local_smart_resume\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\null_provider;
use core_privacy\local\metadata\provider as metadata_provider;

/**
 * Privacy provider for the local_smart_resume plugin.
 *
 * This plugin does not store any personal data. It only reads existing completion data
 * to enhance the UI.
 */
class provider implements metadata_provider, null_provider {

    /**
     * Get the language string identifier with the component's language
     * file to explain why this plugin stores no data.
     *
     * @return  string
     */
    public static function get_reason() : string {
        return 'privacy:metadata';
    }
}