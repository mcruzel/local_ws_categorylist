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

        // Récupérer les catégories avec leur chemin
        $categories = $DB->get_records('course_categories', null, '', 'id, name, path');

        // Traiter le chemin de chaque catégorie
        foreach ($categories as $category) {
            $category->path = self::convert_path_to_names($category->path);
        }

        return $categories;
    }

    // Convertir le chemin d'ID en noms de catégories
    private static function convert_path_to_names($path) {
        global $DB;
        $categorynames = [];
        foreach (explode('/', trim($path, '/')) as $catid) {
            $category = $DB->get_record('course_categories', ['id' => $catid], 'name', MUST_EXIST);
            $categorynames[] = $category->name;
        }
        return implode(' / ', $categorynames);
    }
}
