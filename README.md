# local_ws_categorylist

**Web service function for the platform category list**

## Description

`local_ws_categorylist` adds a single Moodle web service function that lists the course categories
the calling user is allowed to see, with their unique ID (`id`), their name (`name`) and their full
hierarchical path (`path`), expressed as category IDs separated by ` / `.

Category names are passed through Moodle's filters, so multilingual content is resolved in the
caller's language. Hidden categories are returned only to users who hold
`moodle/category:viewhiddencategories` in the relevant category context.

## Requirements

- **Moodle**: 4.5 LTS to 5.2.
- **PHP**: as required by your Moodle version — 8.1+ on Moodle 4.5, 8.3+ on Moodle 5.2.
- **Capability**: callers need `moodle/category:viewcourselist`, which is granted by default to
  authenticated users and guests.

## Installation

Copy the plugin into the `local` directory of your Moodle installation, under the name
`ws_categorylist` — **not** `local_ws_categorylist`, which would produce the wrong component name:

| Moodle version | Destination |
| --- | --- |
| 4.5 to 5.0 | `local/ws_categorylist/` |
| 5.1 and later | `public/local/ws_categorylist/` |

Moodle 5.1 moved the served code into a `public/` directory, so `$CFG->dirroot` now points at
`<installation root>/public`. Then visit *Site administration → Notifications* to complete the
installation, enable web services if needed, and grant a token to the authorised users.

## Usage

### Web service function

**Function**: `local_ws_categorylist_get_categories`

**Service short name**: `local_ws_categorylist`

**Parameters**: both are optional.

| Name | Type | Default | Description |
| --- | --- | --- | --- |
| `page` | int | `0` | Zero-based index of the page to return. |
| `perpage` | int | `100` | Number of categories per page. Capped at 500; `0` means the default. |

**Example response**:

```json
[
  {
    "id": 1,
    "name": "Main category",
    "path": "1"
  },
  {
    "id": 2,
    "name": "Sub-category",
    "path": "1 / 2"
  }
]
```

An empty list is a valid response: a user who can see no category receives `[]`, not an error.

### Example request

```bash
curl -X POST "https://your-moodle-site.example/webservice/rest/server.php" \
     -d "wstoken=YOUR_TOKEN" \
     -d "wsfunction=local_ws_categorylist_get_categories" \
     -d "moodlewsrestformat=json"
```

## Notes for developers

Visibility depends on capabilities evaluated in each category context, which cannot be expressed in
SQL. The plugin therefore reads the category tree in a single query — contexts preloaded — filters
it in PHP, and slices the requested page out of the result. `page` and `perpage` bound the
*response*, not the query.

Run the checks locally the way CI does:

```bash
moodle-plugin-ci phpcs --max-warnings 0
moodle-plugin-ci phpdoc --max-warnings 0
moodle-plugin-ci phpunit
```

## Author

Maxime Cruzel

## License

Licensed under the GNU General Public License v3 or later. See [LICENSE](LICENSE).
