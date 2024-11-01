## Author

Maxime Cruzel

## License

This project is licensed under the MIT License.

# local_ws_categorylist

**Webservice function for category list platform**

## Description

The `local_ws_categorylist` plugin is a Moodle plugin that provides a web service to list all available course categories on the platform, including their unique ID (`id`), name (`name`), and full hierarchical path (`path`).

## Features

- **Category List**: Retrieves available course categories on Moodle with the following details:
  - `id`: the unique identifier for the category.
  - `name`: the name of the category.
  - `path`: the complete path of the category within Moodle’s hierarchy (displayed as category names separated by `/`).

## Requirements

- **Moodle**: Minimum version required: 3.11.
- **Permissions**: Users must have the `moodle/category:manage` capability to access this service.

## Installation

1. Download the `local_ws_categorylist` folder into the `local` directory of your Moodle installation.
2. Access the site administration to complete the plugin installation.
3. Enable web services in Moodle administration if needed and assign permissions to authorized users.

## Usage

### Web Service Endpoint

**Function**: `local_ws_categorylist_get_categories`

**Description**: Retrieves the list of available course categories on Moodle.

**Example Response**:

```json
[
  {
    "id": 1,
    "name": "Main Category",
    "path": "Main Category"
  },
  {
    "id": 2,
    "name": "Sub-category",
    "path": "Main Category / Sub-category"
  }
]
```

### Testing the Web Service

To test this web service, use a tool like `curl` or Postman with a valid API token for a user with the required permissions.

**Example cURL Request**:

```bash
curl -X POST "https://your-moodle-site.com/webservice/rest/server.php" \
     -d "wstoken=YOUR_TOKEN" \
     -d "wsfunction=local_ws_categorylist_get_categories" \
     -d "moodlewsrestformat=json"
```

## Customization

This plugin can be modified to further restrict accessible categories or include additional information as needed.

## Support and Contributions

For questions or feature requests, please open a ticket or submit a pull request on the plugin’s GitHub repository (if available).
