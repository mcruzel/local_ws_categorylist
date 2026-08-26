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

namespace local_ws_categorylist\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\util;
use local_ws_categorylist\tools;

/**
 * Web service returning the course categories visible to the current user.
 *
 * @package    local_ws_categorylist
 * @copyright  2026 Maxime Cruzel
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_categories extends external_api {
    /** @var int Page size applied when the caller does not ask for a specific one. */
    public const DEFAULT_PERPAGE = 100;

    /** @var int Hard upper bound on the number of categories a single call may return. */
    public const MAX_PERPAGE = 500;

    /**
     * Describes the parameters accepted by the function.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'page' => new external_value(
                PARAM_INT,
                'Zero-based index of the page to return.',
                VALUE_DEFAULT,
                0
            ),
            'perpage' => new external_value(
                PARAM_INT,
                'Number of categories per page. Values above ' . self::MAX_PERPAGE . ' are capped, 0 means the default.',
                VALUE_DEFAULT,
                self::DEFAULT_PERPAGE
            ),
        ]);
    }

    /**
     * Returns the course categories the current user is allowed to see.
     *
     * @param int $page Zero-based index of the page to return.
     * @param int $perpage Number of categories per page.
     * @return array[] One entry per category, each with an id, a name and a path of category IDs.
     */
    public static function execute(int $page = 0, int $perpage = self::DEFAULT_PERPAGE): array {
        [
            'page' => $page,
            'perpage' => $perpage,
        ] = self::validate_parameters(self::execute_parameters(), [
            'page' => $page,
            'perpage' => $perpage,
        ]);

        $context = \core\context\system::instance();
        self::validate_context($context);
        require_capability('moodle/category:viewcourselist', $context);

        $page = max(0, $page);
        $perpage = $perpage <= 0 ? self::DEFAULT_PERPAGE : min($perpage, self::MAX_PERPAGE);

        $categories = array_slice(tools::get_visible_categories(), $page * $perpage, $perpage);

        $result = [];
        foreach ($categories as $category) {
            $categorycontext = \core\context\coursecat::instance($category->id);
            $result[] = [
                'id' => $category->id,
                'name' => util::format_string($category->name, $categorycontext),
                'path' => tools::format_path($category->path),
            ];
        }

        return $result;
    }

    /**
     * Describes the value returned by the function.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Category ID.'),
                'name' => new external_value(PARAM_TEXT, 'Name of the category.'),
                'path' => new external_value(
                    PARAM_TEXT,
                    'Full path of the category, as category IDs separated by " / ".'
                ),
            ])
        );
    }
}
