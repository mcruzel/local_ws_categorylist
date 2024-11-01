<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use local_ws_categorylist\tools;
use local_ws_categorylist\exceptions\category_not_found_exception;

class local_ws_categorylist_external extends external_api {

    public static function get_categories_parameters() {
        return new external_function_parameters([]);
    }

    public static function get_categories() {
        global $DB;

        // Vérification des permissions
        $context = context_system::instance();
        tools::validate_category_access($context);

        // Récupérer les catégories
        $categories = tools::get_categories_with_path();

        if (!$categories) {
            throw new category_not_found_exception();
        }

        $result = [];
        foreach ($categories as $category) {
            $result[] = [
                'id' => $category->id,
                'name' => $category->name,
                'path' => $category->path,
            ];
        }
        return $result;
    }

    public static function get_categories_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'id' => new external_value(PARAM_INT, 'Category ID'),
                    'name' => new external_value(PARAM_TEXT, 'Name of the category'),
                    'path' => new external_value(PARAM_TEXT, 'Full path of the category'),
                ]
            )
        );
    }
}
