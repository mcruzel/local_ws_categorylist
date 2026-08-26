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

namespace local_ws_categorylist;

use core_course_category;

/**
 * Category lookups shared by the plugin web service functions.
 *
 * @package    local_ws_categorylist
 * @copyright  2026 Maxime Cruzel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tools {
    /** @var int Hard cap on the number of categories a single web service call may return. */
    public const MAX_PERPAGE = 100;

    /**
     * Return every category the current user is allowed to see, parents before children.
     *
     * Visibility is delegated to core_course_category::get_all(), which keeps only the
     * categories passing core_course_category::can_view_category(): that requires
     * moodle/category:viewcourselist on the category context, plus
     * moodle/category:viewhiddencategories for a hidden category. The list is ordered by
     * depth then sort order, so a parent always precedes its children.
     *
     * @return core_course_category[] Categories indexed by category id.
     */
    public static function get_visible_categories(): array {
        return core_course_category::get_all();
    }

    /**
     * Turn a stored category path into the id path published by this web service.
     *
     * course_categories.path already holds a slash separated list of ancestor ids ending
     * with the category itself, for example "/3/7". Only the leading slash is dropped, so
     * no extra database lookup is needed to build the value.
     *
     * @param string $path The path as stored by Moodle.
     * @return string The id path, for example "3/7".
     */
    public static function format_path(string $path): string {
        return trim($path, '/');
    }
}
