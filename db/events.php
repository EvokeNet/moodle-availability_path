<?php

/**
 * Evoke game events observers
 *
 * @package     local_evokegame
 * @copyright   2021 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\mod_choicepath\event\option_deleted',
        'callback' => '\availability_path\observers\option::deleted',
        'internal' => false
    ],
];
