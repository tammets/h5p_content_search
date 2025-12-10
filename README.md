## INTRODUCTION

The H5P Content Search module provides Search API integration for H5P content. It extracts text from H5P JSON parameters and makes it searchable through Search API indexes.

The primary use case for this module is:

- Index H5P interactive content text for site-wide search
- Enable users to find H5P content based on embedded text
- Support multilingual H5P content searching

## REQUIREMENTS

This module requires the following modules:

- Search API (https://www.drupal.org/project/search_api)
- H5P (https://www.drupal.org/project/h5p)

## INSTALLATION

1. Install the module and its dependencies:
   ```bash
   composer require drupal/search_api
   drush en search_api search_api_db h5p h5p_content_search -y
   ```

2. If Search API is not installed, install Search API Database module as well:
   ```bash
   drush en search_api_db -y
   ```

## CONFIGURATION (Step-by-Step for Drupal 11)

### Step 1: Create a Search API Server

1. Navigate to **Configuration** → **Search and metadata** → **Search API** → **Add server**
   - URL: `/admin/config/search/search-api/add-server`
2. Fill in the form:
   - **Server name**: `Database Server` (or any name you prefer)
   - **Machine name**: `database_server` (auto-generated)
   - **Backend**: Select **Database**
   - **Database**: Leave as default (your Drupal database)
3. Click **Save**

### Step 2: Create a Search API Index

1. Navigate to **Configuration** → **Search and metadata** → **Search API** → **Add index**
   - URL: `/admin/config/search/search-api/add-index`
2. Fill in the form:
   - **Index name**: `Content Index` (or any name you prefer)
   - **Machine name**: `content_index` (auto-generated)
   - **Datasources**: Check **Content** (this indexes nodes)
     - Expand "Content" and select the content types that have H5P fields
   - **Server**: Select **Database Server** (the server you just created)
3. Click **Save**

### Step 3: Enable the H5P Text Extractor Processor

1. After saving the index, you'll be on the index edit page
2. Click the **Processors** tab
   - URL: `/admin/config/search/search-api/index/content_index/processors`
3. Scroll down and find **H5P Text Extractor**
4. Check the box to **enable** it
5. Scroll to the bottom and click **Save**

### Step 4: Add the H5P Extracted Text Field

1. Click the **Fields** tab on your index
   - URL: `/admin/config/search/search-api/index/content_index/fields`
2. Click **Add fields** button
3. In the field list, find and check:
   - **H5P Extracted Text** (under "General" or processor-added fields)
4. Click **Add fields** button at the bottom
5. Back on the Fields page, configure the field:
   - Find **H5P Extracted Text** in the list
   - Set **Type** to **Fulltext**
   - Set **Boost** to `1.0` (or higher if you want H5P content prioritized)
6. Click **Save changes**

### Step 5: Index Your Content

1. Click the **View** tab on your index
   - URL: `/admin/config/search/search-api/index/content_index`
2. You'll see indexing status (e.g., "0 of 10 items indexed")
3. Click **Index now** button
4. Wait for indexing to complete
5. Refresh the page to verify all items are indexed

### Step 6: Test Your Search

1. Create a **Search view** or use the Search API's built-in search page
2. Search for text that appears inside your H5P content
3. The content should now be discoverable

### Troubleshooting

- **Field name**: The processor looks for `field_h5p` by default. If your H5P field has a different machine name, update the field reference in `H5PTextExtractor.php`.
- **Clear cache**: Run `drush cr` after any code changes
- **Re-index**: Click "Clear all indexed data" then "Index now" to force a fresh index

## NOTES

- The module extracts text from the H5P `parameters` JSON stored in the database
- HTML tags are stripped from extracted text
- Very short strings (< 3 characters) are filtered out to avoid indexing IDs
- Certain metadata keys are skipped to focus on actual content

## MAINTAINERS

Current maintainers:

- Priit Tammets - https://github.com/tammets

