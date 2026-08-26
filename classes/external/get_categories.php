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

namespace local_ws_categorylist\external;

use core\context\system as context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;
use core_external\util;
use local_ws_categorylist\tools;

/**
 * Web service returning the platform course category list.
 *
 * @package    local_ws_categorylist
 * @copyright  2026 Maxime Cruzel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_categories extends external_api {
    /**
     * Webservice parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'page' => new external_value(
                PARAM_INT,
                'Zero based page number.',
                VALUE_DEFAULT,
                0,
            ),
            'perpage' => new external_value(
                PARAM_INT,
                'Categories per page. Values below 1 or above the maximum fall back to the maximum.',
                VALUE_DEFAULT,
                0,
            ),
        ]);
    }

    /**
     * Return the course categories the current user is allowed to see, with their id path.
     *
     * An empty category list is a valid result and is returned as an empty array, not as an error.
     *
     * @param int $page Zero based page number.
     * @param int $perpage Categories per page, capped at tools::MAX_PERPAGE.
     * @return array The paginated category list, the total category count and any warnings.
     */
    public static function execute(int $page = 0, int $perpage = 0): array {
        [
            'page' => $page,
            'perpage' => $perpage,
        ] = external_api::validate_parameters(
            self::execute_parameters(),
            [
                'page' => $page,
                'perpage' => $perpage,
            ],
        );

        self::validate_context(context_system::instance());

        $perpage = $perpage > 0 ? min($perpage, tools::MAX_PERPAGE) : tools::MAX_PERPAGE;
        // Clamp the page so that page * perpage can never overflow into a float.
        $page = min(max(0, $page), intdiv(PHP_INT_MAX, $perpage));

        $visible = tools::get_visible_categories();

        $categories = [];
        foreach (array_slice($visible, $page * $perpage, $perpage) as $category) {
            $categories[] = [
                'id' => $category->id,
                'name' => util::format_string($category->name, $category->get_context()),
                'path' => tools::format_path($category->path),
            ];
        }

        return [
            'categories' => $categories,
            'total' => count($visible),
            'warnings' => [],
        ];
    }

    /**
     * Webservice returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'categories' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Category id.'),
                    'name' => new external_value(PARAM_RAW, 'Category name.'),
                    'path' => new external_value(
                        PARAM_TEXT,
                        'Ancestor ids down to the category itself, separated by slashes, for example "3/7".',
                    ),
                ]),
                'Categories visible to the current user, for the requested page.',
            ),
            'total' => new external_value(PARAM_INT, 'Total number of categories visible to the current user.'),
            'warnings' => new external_warnings(),
        ]);
    }
}
