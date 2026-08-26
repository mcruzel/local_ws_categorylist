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

use core_external\external_api;
use local_ws_categorylist\tools;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for the local_ws_categorylist_get_categories web service.
 *
 * @package    local_ws_categorylist
 * @category   test
 * @copyright  2026 Maxime Cruzel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(get_categories::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(tools::class)]
final class get_categories_test extends \externallib_advanced_testcase {
    /**
     * Call the web service and validate the result against its declared return structure.
     *
     * @param int $page Zero based page number.
     * @param int $perpage Categories per page.
     * @return array The cleaned web service result.
     */
    private function call_service(int $page = 0, int $perpage = 0): array {
        $result = get_categories::execute($page, $perpage);
        return external_api::clean_returnvalue(get_categories::execute_returns(), $result);
    }

    /**
     * Index the returned categories by name for readable assertions.
     *
     * @param array $result The cleaned web service result.
     * @return array Category paths indexed by category name.
     */
    private function paths_by_name(array $result): array {
        return array_column($result['categories'], 'path', 'name');
    }

    /**
     * The service returns visible categories with an id path and no leading slash.
     */
    public function test_execute_returns_id_paths(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $parent = $generator->create_category(['name' => 'Sciences']);
        $child = $generator->create_category(['name' => 'Physics', 'parent' => $parent->id]);

        $paths = $this->paths_by_name($this->call_service());

        $this->assertSame((string) $parent->id, $paths['Sciences']);
        $this->assertSame("{$parent->id}/{$child->id}", $paths['Physics']);
    }

    /**
     * The published path carries ids, never category names, and no spaces.
     */
    public function test_execute_path_contains_no_names_and_no_spaces(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $parent = $generator->create_category(['name' => 'Sciences']);
        $generator->create_category(['name' => 'Physics', 'parent' => $parent->id]);

        foreach ($this->call_service()['categories'] as $category) {
            $this->assertMatchesRegularExpression('/^\d+(\/\d+)*$/', $category['path']);
            $this->assertStringNotContainsString(' ', $category['path']);
            $this->assertStringNotContainsString('Sciences', $category['path']);
        }
    }

    /**
     * A site without any category is a valid result, not an error.
     */
    public function test_execute_returns_an_empty_list_when_no_category_exists(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $result = $this->call_service();

        $this->assertSame([], $result['categories']);
        $this->assertSame(0, $result['total']);
        $this->assertSame([], $result['warnings']);
    }

    /**
     * A hidden category is visible to a manager but not to a regular user.
     */
    public function test_execute_hides_categories_the_user_may_not_see(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $generator->create_category(['name' => 'Open', 'visible' => 1]);
        $generator->create_category(['name' => 'Closed', 'visible' => 0]);

        $this->setUser($generator->create_user());
        $result = $this->call_service();
        $this->assertSame(['Open'], array_column($result['categories'], 'name'));
        $this->assertSame(1, $result['total']);

        $this->setAdminUser();
        $result = $this->call_service();
        $this->assertCount(2, $result['categories']);
        $this->assertSame(2, $result['total']);
    }

    /**
     * Parents are always returned before their children.
     */
    public function test_execute_returns_parents_before_children(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $parent = $generator->create_category(['name' => 'Sciences']);
        $child = $generator->create_category(['name' => 'Physics', 'parent' => $parent->id]);
        $grandchild = $generator->create_category(['name' => 'Optics', 'parent' => $child->id]);

        $ids = array_column($this->call_service()['categories'], 'id');
        $position = fn($category) => array_search((int) $category->id, $ids, true);

        $this->assertLessThan($position($child), $position($parent));
        $this->assertLessThan($position($grandchild), $position($child));
    }

    /**
     * Pagination slices the list while total keeps reporting the full count.
     */
    public function test_execute_paginates_the_category_list(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        for ($i = 1; $i <= 5; $i++) {
            $this->getDataGenerator()->create_category(['name' => "Category $i"]);
        }

        $first = $this->call_service(0, 2);
        $second = $this->call_service(1, 2);
        $third = $this->call_service(2, 2);

        $this->assertCount(2, $first['categories']);
        $this->assertCount(2, $second['categories']);
        $this->assertCount(1, $third['categories']);
        $this->assertSame(5, $first['total']);

        $seen = array_merge(
            array_column($first['categories'], 'id'),
            array_column($second['categories'], 'id'),
            array_column($third['categories'], 'id'),
        );
        $this->assertCount(5, array_unique($seen));
    }

    /**
     * Out of range paging values are clamped instead of crashing the service.
     */
    public function test_execute_clamps_out_of_range_paging(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->getDataGenerator()->create_category(['name' => 'Sciences']);

        $result = $this->call_service(PHP_INT_MAX, tools::MAX_PERPAGE);
        $this->assertSame([], $result['categories']);
        $this->assertSame(1, $result['total']);

        $result = $this->call_service(-5, -10);
        $this->assertCount(1, $result['categories']);
    }

    /**
     * Category names are passed through format_string, so raw markup never reaches the client.
     */
    public function test_execute_formats_category_names(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->getDataGenerator()->create_category(['name' => 'Maths <b>advanced</b>']);

        $names = array_column($this->call_service()['categories'], 'name');

        $this->assertStringNotContainsString('<b>', $names[0]);
    }
}
