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
use core_external\tests\externallib_testcase;
use local_ws_categorylist\tools;

/**
 * Tests for the get_categories web service function.
 *
 * @package    local_ws_categorylist
 * @covers     \local_ws_categorylist\external\get_categories
 * @copyright  2026 Maxime Cruzel
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_categories_test extends externallib_testcase {
    /**
     * Calls the function and validates the response against its declared return structure.
     *
     * @param int $page Zero-based index of the page to return.
     * @param int $perpage Number of categories per page.
     * @return array[] The cleaned response.
     */
    private function call(int $page = 0, int $perpage = get_categories::DEFAULT_PERPAGE): array {
        return external_api::clean_returnvalue(
            get_categories::execute_returns(),
            get_categories::execute($page, $perpage)
        );
    }

    /**
     * Extracts the category IDs from a response.
     *
     * @param array[] $response The response returned by the function.
     * @return int[] The category IDs, in response order.
     */
    private function ids(array $response): array {
        return array_column($response, 'id');
    }

    /**
     * Returns the entry describing a given category, failing the test when it is absent.
     *
     * The data generator hands back database records, whose IDs are strings, while the cleaned
     * response holds integers. Callers pass the generator value and this helper normalises it.
     *
     * @param array[] $response The response returned by the function.
     * @param int|string $categoryid The category to look for.
     * @return array The matching entry.
     */
    private function entry(array $response, int|string $categoryid): array {
        $position = array_search((int) $categoryid, $this->ids($response), true);
        $this->assertIsInt($position, "Category {$categoryid} is missing from the response.");

        return $response[$position];
    }

    /**
     * A visible category is returned, and the response matches the declared structure.
     */
    public function test_execute_returns_visible_categories(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $category = $this->getDataGenerator()->create_category(['name' => 'Sciences']);

        $response = $this->call();

        $entry = $this->entry($response, $category->id);
        $this->assertSame((int) $category->id, $entry['id']);
        $this->assertSame('Sciences', $entry['name']);
    }

    /**
     * The path is made of category IDs separated by " / ", parent first.
     */
    public function test_path_contains_category_ids(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $parent = $this->getDataGenerator()->create_category();
        $child = $this->getDataGenerator()->create_category(['parent' => $parent->id]);

        $entry = $this->entry($this->call(), $child->id);

        $this->assertSame("{$parent->id} / {$child->id}", $entry['path']);
    }

    /**
     * Hidden categories are withheld from users who cannot view them, but not from an admin.
     */
    public function test_hidden_categories_are_filtered_per_user(): void {
        $this->resetAfterTest();

        $hidden = $this->getDataGenerator()->create_category(['visible' => 0]);
        $visible = $this->getDataGenerator()->create_category();

        $this->setUser($this->getDataGenerator()->create_user());
        $ids = $this->ids($this->call());
        $this->assertNotContains((int) $hidden->id, $ids);
        $this->assertContains((int) $visible->id, $ids);

        $this->setAdminUser();
        $this->assertContains((int) $hidden->id, $this->ids($this->call()));
    }

    /**
     * An empty result is a valid response, not an error.
     */
    public function test_execute_returns_empty_array_when_nothing_is_visible(): void {
        global $DB;

        $this->resetAfterTest();

        $this->getDataGenerator()->create_category();
        $DB->set_field('course_categories', 'visible', 0, []);
        $this->setUser($this->getDataGenerator()->create_user());

        $this->assertSame([], $this->call());
    }

    /**
     * The function refuses callers who lack moodle/category:viewcourselist.
     */
    public function test_execute_requires_the_viewcourselist_capability(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $userrole = $DB->get_record('role', ['shortname' => 'user'], '*', MUST_EXIST);
        assign_capability(
            'moodle/category:viewcourselist',
            CAP_PROHIBIT,
            $userrole->id,
            \core\context\system::instance()->id,
            true
        );
        accesslib_clear_all_caches_for_unit_testing();

        $this->expectException(\required_capability_exception::class);
        $this->call();
    }

    /**
     * Pagination bounds the response and never repeats a category across pages.
     */
    public function test_execute_paginates_results(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        for ($i = 0; $i < 3; $i++) {
            $this->getDataGenerator()->create_category();
        }

        $first = $this->ids($this->call(0, 2));
        $second = $this->ids($this->call(1, 2));

        $this->assertCount(2, $first);
        $this->assertCount(2, $second);
        $this->assertSame([], array_intersect($first, $second));
    }

    /**
     * A page size of zero falls back to the default, and oversized ones are capped.
     */
    public function test_perpage_is_normalised(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->getDataGenerator()->create_category();

        $this->assertNotEmpty($this->call(0, 0));
        $this->assertLessThanOrEqual(get_categories::MAX_PERPAGE, count($this->call(0, PHP_INT_MAX)));
    }

    /**
     * The function declared in db/services.php resolves and runs through the web service dispatcher.
     *
     * This is the regression test for the fatal error that made every call fail: the dispatcher
     * resolves the class from db/services.php, so it also proves the declaration is correct.
     */
    public function test_function_is_callable_through_the_dispatcher(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $category = $this->getDataGenerator()->create_category();

        $info = external_api::external_function_info('local_ws_categorylist_get_categories');
        $this->assertSame(get_categories::class, ltrim($info->classname, '\\'));
        $this->assertSame('execute', $info->methodname);

        // Outside a web service server the dispatcher enforces the session key.
        $_POST['sesskey'] = sesskey();
        $response = external_api::call_external_function('local_ws_categorylist_get_categories', []);
        unset($_POST['sesskey']);

        $this->assertFalse($response['error'], 'The web service dispatcher reported an error.');
        $this->assertContains((int) $category->id, array_column($response['data'], 'id'));
    }

    /**
     * The raw Moodle path is turned into IDs separated by " / ".
     *
     * @covers \local_ws_categorylist\tools::format_path
     */
    public function test_format_path(): void {
        $this->assertSame('1 / 12 / 34', tools::format_path('/1/12/34'));
        $this->assertSame('7', tools::format_path('/7'));
        $this->assertSame('', tools::format_path(''));
    }
}
