<?php
namespace local_ws_categorylist\exceptions;

defined('MOODLE_INTERNAL') || die();

class category_not_found_exception extends \moodle_exception {
    public function __construct() {
        parent::__construct('categorynotfound', 'local_ws_categorylist', '', null, 'Category not found');
    }
}
