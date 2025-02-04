<?php

namespace availability_path\observers;

use mod_choicepath\event\option_deleted;

class option {
    public static function deleted(option_deleted $event) {
        $course_module_updated = static::delete_modules_references($event->courseid, $event->objectid);

        $section_updated = static::delete_sections_references($event->courseid, $event->objectid);

        if ($course_module_updated || $section_updated) {
            rebuild_course_cache($event->courseid, false, true);
        }
    }

    private static function delete_modules_references($courseid, $optionid) {
        global $DB;

        $sql = "SELECT * FROM {course_modules} WHERE availability LIKE '%path%' AND course = :course";

        $modules = $DB->get_records_sql($sql, ['course' => $courseid]);

        if (!$modules) {
            return;
        }

        $updated = false;

        foreach ($modules as $module) {
            $structure = json_decode($module->availability, true);

            $needs_update = false;
            foreach ($structure['c'] as $key => $item) {
                if ($item['type'] == 'path' && $item['o'] == $optionid) {
                    // Proceed with deletion of the restriction.
                    unset($structure['c'][$key]);
                    unset($structure['showc'][$key]);

                    $needs_update = true;

                    $updated = true;
                }
            }

            if ($needs_update) {
                // Reset array indexes.
                $structure['c'] = array_values($structure['c']);
                $structure['showc'] = array_values($structure['showc']);

                $module->availability = json_encode($structure);

                $DB->update_record('course_modules', $module);
            }
        }

        return $updated;
    }

    private static function delete_sections_references($courseid, $optionid) {
        global $DB;

        $sql = "SELECT * FROM {course_sections} WHERE availability LIKE '%path%' AND course = :course";

        $sections = $DB->get_records_sql($sql, ['course' => $courseid]);

        if (!$sections) {
            return;
        }

        $updated = false;

        foreach ($sections as $section) {
            $structure = json_decode($section->availability, true);

            $needs_update = false;
            foreach ($structure['c'] as $key => $item) {
                if ($item['type'] == 'path' && $item['o'] == $optionid) {
                    // Proceed with deletion of the restriction.
                    unset($structure['c'][$key]);
                    unset($structure['showc'][$key]);

                    $needs_update = true;

                    $updated = true;
                }
            }

            if ($needs_update) {
                // Reset array indexes.
                $structure['c'] = array_values($structure['c']);
                $structure['showc'] = array_values($structure['showc']);

                $section->availability = json_encode($structure);

                $DB->update_record('course_sections', $section);
            }
        }

        return $updated;
    }
}