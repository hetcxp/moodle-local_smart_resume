<?php
defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core\hook\output\before_footer_html_generation::class,
        'callback' => 'local_smart_resume\hook\before_footer::execute',
        'priority' => 100,
    ],
];