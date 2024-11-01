<?php
namespace local_ws_categorylist;

defined('MOODLE_INTERNAL') || die();

class tools {

    public static function validate_category_access($context) {
        self::validate_context($context);
        require_capability('moodle/category:manage', $context);
    }

    public static function get_categories_with_path() {
        global $DB;

        // Retrieve categories with their path
        $categories = $DB->get_records('course_categories', null, '', 'id, name, path');

        // Process the path of each category to include IDs
        foreach ($categories as $category) {
            $category->path = self::convert_path_to_ids($category->path);
        }

        return $categories;
    }

    // Convert the path string to a series of category IDs
    private static function convert_path_to_ids($path) {
        return implode(' / ', array_filter(explode('/', trim($path, '/'))));
    }
}
