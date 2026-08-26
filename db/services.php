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
 * External function and service definitions for local_ws_categorylist.
 *
 * @package    local_ws_categorylist
 * @copyright  2026 Maxime Cruzel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_ws_categorylist_get_categories' => [
        'classname' => 'local_ws_categorylist\external\get_categories',
        'methodname' => 'execute',
        'description' => 'Return the course categories the current user is allowed to see, with their id path.',
        'type' => 'read',
        'ajax' => true,
    ],
];

$services = [
    // The array key is the service name stored in the database. Renaming it makes the upgrade
    // drop the existing service together with its tokens, so it is deliberately left unchanged.
    'List Categories Service' => [
        'shortname' => 'local_ws_categorylist',
        'functions' => ['local_ws_categorylist_get_categories'],
        'restrictedusers' => 0,
        'downloadfiles' => 0,
        'uploadfiles' => 0,
        'enabled' => 1,
    ],
];
