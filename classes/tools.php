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

namespace local_ws_categorylist;

use core_course_category;

/**
 * Helper functions shared by the category list web service.
 *
 * @package    local_ws_categorylist
 * @copyright  2026 Maxime Cruzel
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tools {
    /**
     * Returns every course category the current user is allowed to see.
     *
     * Categories come back in the order Moodle displays them. Their contexts are preloaded by the
     * query itself, so the per-category visibility check does not issue one query per row.
     *
     * Visibility depends on capabilities evaluated in each category context, which cannot be
     * expressed in SQL. The whole tree is therefore read and filtered in PHP; callers that need a
     * bounded response slice the returned array.
     *
     * @return \stdClass[] Category records, each carrying an id, a name and a raw path.
     */
    public static function get_visible_categories(): array {
        global $DB;

        $contextfields = \core\context_helper::get_preload_record_columns_sql('ctx');
        $sql = "SELECT cc.id, cc.name, cc.path, cc.visible, {$contextfields}
                  FROM {course_categories} cc
                  JOIN {context} ctx ON ctx.instanceid = cc.id AND ctx.contextlevel = :contextlevel
              ORDER BY cc.depth ASC, cc.sortorder ASC";
        $records = $DB->get_records_sql($sql, ['contextlevel' => CONTEXT_COURSECAT]);

        $categories = [];
        foreach ($records as $record) {
            \core\context_helper::preload_from_record($record);
            if (core_course_category::can_view_category($record)) {
                $categories[] = $record;
            }
        }

        return $categories;
    }

    /**
     * Converts a raw Moodle category path into the format returned by the web service.
     *
     * @param string $path Raw path as stored in course_categories, for instance "/1/12/34".
     * @return string Category IDs separated by " / ", for instance "1 / 12 / 34".
     */
    public static function format_path(string $path): string {
        return implode(' / ', array_filter(explode('/', trim($path, '/'))));
    }
}
