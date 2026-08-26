# local_ws_categorylist

**Web service: platform category list**

## Description

`local_ws_categorylist` is a Moodle local plugin exposing a single web service function that
returns the course categories the calling user is allowed to see, with each category's id
(`id`), name (`name`) and hierarchical path (`path`).

Category visibility is delegated to Moodle's own `core_course_category` API. The plugin
therefore honours `moodle/category:viewcourselist` on every category context and requires
`moodle/category:viewhiddencategories` before returning a hidden category. Categories are
returned parents first, ordered by depth then sort order.

## Requirements

- **Moodle**: 5.2 (2026042000) or later.
- **PHP**: 8.3 or later, as required by Moodle 5.2.

## Installation

1. Copy the plugin into your Moodle installation so that it lives at `local/ws_categorylist`.
   On Moodle 5.x the web root is the `public` directory, so the full path is
   `public/local/ws_categorylist`. The directory name must be `ws_categorylist`, not
   `local_ws_categorylist`, otherwise Moodle will reject the plugin.

   ```bash
   git clone https://github.com/mcruzel/local_ws_categorylist.git public/local/ws_categorylist
   ```

2. Visit *Site administration → Notifications* to complete the installation.
3. Enable web services and at least one protocol under
   *Site administration → Server → Web services*.
4. Create a token for an authorised user against the **List Categories Service**
   (shortname `local_ws_categorylist`).

## Usage

### Function

`local_ws_categorylist_get_categories`

### Parameters

| Name      | Type | Default | Description                                                    |
|-----------|------|---------|----------------------------------------------------------------|
| `page`    | int  | `0`     | Zero based page number.                                         |
| `perpage` | int  | `0`     | Categories per page. Values below 1 or above 100 fall back to 100. |

The response is capped at 100 categories per call. Iterate with `page` until you have
collected `total` categories.

### The `path` field

`path` lists the ancestor ids down to the category itself, separated by `/`, with no spaces
and no leading or trailing slash:

- a top level category with id `3` has the path `3`;
- its child with id `7` has the path `3/7`.

Ids are used rather than names because they are stable across renames, unique across the
whole tree, and directly joinable with the `categoryid` returned by
[`local_ws_courselist`](https://github.com/mcruzel/local_ws_courselist).

### Response

```json
{
  "categories": [
    { "id": 3, "name": "Sciences", "path": "3" },
    { "id": 7, "name": "Physics",  "path": "3/7" }
  ],
  "total": 2,
  "warnings": []
}
```

A site with no category returns `"categories": []` with `"total": 0`. It is not an error.

### Example request

```bash
curl -X POST "https://your-moodle-site.example/webservice/rest/server.php" \
     -d "wstoken=YOUR_TOKEN" \
     -d "wsfunction=local_ws_categorylist_get_categories" \
     -d "moodlewsrestformat=json" \
     -d "page=0" \
     -d "perpage=100"
```

## Testing

The plugin ships with a PHPUnit suite. From your Moodle root:

```bash
php public/admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --filter local_ws_categorylist
```

## Privacy

The plugin stores no personal data. It implements
`\core_privacy\local\metadata\null_provider`.

## Author

Maxime Cruzel

## License

Licensed under the GNU General Public License v3 or later, the same licence as Moodle.
See [LICENSE](LICENSE).
