<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Handles AJAX requests to get the list of options in a choice path activity.
 *
 * @package     availability_path
 * @copyright   2024 Willian Mano <willianmanoaraujo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require(__DIR__ . '/../../../config.php');

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('choicepath', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record('choicepath', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = core\context\module::instance($cm->id);
require_capability('mod/choicepath:addinstance', $context);

$records = $DB->get_records('choicepath_options', ['choicepathid' => $moduleinstance->id]);

if (!$records) {
    echo json_encode([]);
} else {
    $options = [];
    foreach ($records as $record) {
        $options[] = (object) [
            'id' => $record->id,
            'title' => $record->title,
        ];
    }

    echo json_encode($options);
}
