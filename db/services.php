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

/**
 * Web service function and service definitions.
 *
 * @package    local_ws_categorylist
 * @copyright  2026 Maxime Cruzel
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_ws_categorylist_get_categories' => [
        'classname' => 'local_ws_categorylist\external\get_categories',
        'methodname' => 'execute',
        'description' => 'Get the course categories visible to the current user, including their full path of category IDs.',
        'type' => 'read',
        'capabilities' => 'moodle/category:viewcourselist',
        'ajax' => true,
    ],
];

$services = [
    'Category list service' => [
        'functions' => ['local_ws_categorylist_get_categories'],
        'shortname' => 'local_ws_categorylist',
        'restrictedusers' => 0,
        'enabled' => 1,
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],
];
