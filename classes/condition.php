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
 * Activity path condition.
 *
 * @package availability_path
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_path;

use cache;
use core_availability\info;
use core_availability\info_module;
use core_availability\info_section;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');

/**
 * Activity path condition.
 *
 * @package availability_path
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {

    /** @var int ID of module that this depends on */
    protected $cmid;

    /** @var array IDs of the current module and section */
    protected $selfids;

    /** @var int ID of option that this depends on */
    protected $optionid;

    /** @var array Array of previous cmids used to calculate relative completions */
    protected $modfastprevious = [];

    /** @var array Array of cmids previous to each course section */
    protected $sectionfastprevious = [];

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        // Get cmid.
        if (isset($structure->cm) && is_number($structure->cm)) {
            $this->cmid = (int)$structure->cm;
        } else {
            throw new \coding_exception('Missing or invalid ->cm for path condition');
        }

        // Get expected option.
        if (isset($structure->o) && is_number($structure->o)) {
            $this->optionid = $structure->o;
        } else {
            throw new \coding_exception('Missing or invalid ->o for path condition');
        }
    }

    /**
     * Saves tree data back to a structure object.
     *
     * @return stdClass Structure object (ready to be made into JSON format)
     */
    public function save(): stdClass {
        return (object) [
            'type' => 'path',
            'cm' => $this->cmid,
            'o' => $this->optionid,
        ];
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param int $cmid Course-module id of other activity
     * @param int $optionid Expected option value
     * @return stdClass Object representing condition
     */
    public static function get_json(int $cmid, int $optionid): stdClass {
        return (object) [
            'type' => 'path',
            'cm' => (int)$cmid,
            'o' => (int)$optionid,
        ];
    }

    /**
     * Determines whether a particular item is currently available
     * according to this availability condition.
     *
     * @see \core_availability\tree_node\update_after_restore
     *
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @param bool $grabthelot Performance hint: if true, caches information
     *   required for all course-modules, to make the front page and similar
     *   pages work more quickly (works only for current user)
     * @param int $userid User ID to check availability for
     * @return bool True if available
     */
    public function is_available($not, info $info, $grabthelot, $userid): bool {
        $answer = $this->get_user_answer($userid);

        if (!$answer) {
            return false;
        }

        if ($not) {
            if ($answer == $this->optionid) {
                return false;
            }

            return true;
        }

        if ($answer == $this->optionid) {
            return true;
        }

        return false;
    }

    protected function get_user_answer($userid) {
        global $DB;

        $answer = $DB->get_record('choicepath_answers', ['userid' => $userid, 'choicepathid' => $this->cmid]);

        if (!$answer) {
            return false;
        }

        return $answer->optionid;
    }

    /**
     * Obtains a string describing this restriction (whether or not
     * it actually applies).
     *
     * @param bool $full Set true if this is the 'full information' view
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @return string Information string (for admin) about all restrictions on
     *   this item
     */
    public function get_description($full, $not, info $info): string {
        global $DB;

        $option = $DB->get_record('choicepath_options', ['id' => $this->optionid], 'id, title', MUST_EXIST);

        return get_string('availability_description', 'availability_path', $option->title);
    }

    /**
     * Obtains a representation of the options of this condition as a string,
     * for debugging.
     *
     * @return string Text representation of parameters
     */
    protected function get_debug_string(): string {
        return gmdate('Y-m-d H:i:s');
    }

    /**
     * Wipes the static cache of modules used in a condition (for unit testing).
     */
    public static function wipe_static_cache() {
        self::$modsusedincondition = [];
    }
}
