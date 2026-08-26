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
     * Hide every category currently visible, so the site holds nothing a plain user may see.
     *
     * A Moodle site always carries at least one category, unlike courses, so an empty result
     * is reached through visibility rather than through an empty table.
     */
    private function hide_every_category(): void {
        foreach (\core_course_category::get_all(['returnhidden' => true]) as $category) {
            if ($category->visible) {
                $category->hide();
            }
        }
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
     * Seeing no category at all is a valid result, not an error.
     */
    public function test_execute_returns_an_empty_list_when_the_user_sees_nothing(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->getDataGenerator()->create_category(['name' => 'Sciences']);
        $this->hide_every_category();

        $this->setUser($this->getDataGenerator()->create_user());
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
        $names = array_column($this->call_service()['categories'], 'name');
        $this->assertContains('Open', $names);
        $this->assertNotContains('Closed', $names);

        $this->setAdminUser();
        $names = array_column($this->call_service()['categories'], 'name');
        $this->assertContains('Open', $names);
        $this->assertContains('Closed', $names);
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
            $this->getDataGenerator()->create_category(['name' => "Branch $i"]);
        }

        $total = $this->call_service()['total'];
        $this->assertGreaterThanOrEqual(5, $total);

        $perpage = 2;
        $seen = [];
        for ($page = 0; $page * $perpage < $total; $page++) {
            $result = $this->call_service($page, $perpage);
            $this->assertSame($total, $result['total'], 'total must not change between pages');
            $this->assertLessThanOrEqual($perpage, count($result['categories']));
            $seen = array_merge($seen, array_column($result['categories'], 'id'));
        }

        // Paging covers the whole set exactly once, with no gap and no repeat.
        $this->assertCount($total, $seen);
        $this->assertCount($total, array_unique($seen));
    }

    /**
     * Out of range paging values are clamped instead of crashing the service.
     */
    public function test_execute_clamps_out_of_range_paging(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->getDataGenerator()->create_category(['name' => 'Sciences']);
        $total = $this->call_service()['total'];

        // A page number large enough to overflow page * perpage must not raise a TypeError.
        $result = $this->call_service(PHP_INT_MAX, tools::MAX_PERPAGE);
        $this->assertSame([], $result['categories']);
        $this->assertSame($total, $result['total']);

        // Negative values fall back to the first page and to the maximum page size.
        $result = $this->call_service(-5, -10);
        $this->assertCount(min($total, tools::MAX_PERPAGE), $result['categories']);
    }

    /**
     * Category names are passed through format_string, so raw markup never reaches the client.
     */
    public function test_execute_formats_category_names(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->getDataGenerator()->create_category(['name' => 'Maths <b>advanced</b>']);

        $names = array_column($this->call_service()['categories'], 'name');
        $maths = array_values(array_filter($names, fn($name) => str_starts_with($name, 'Maths')));

        $this->assertCount(1, $maths, 'the created category must be present in the result');
        $this->assertStringNotContainsString('<b>', $maths[0]);
        $this->assertStringContainsString('advanced', $maths[0]);
    }
}
