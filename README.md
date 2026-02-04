# Parish FAQ

A WordPress plugin for managing and displaying FAQs with accordion UI and Meilisearch integration.

## Features

- **Custom Post Type** - FAQs managed in WordPress admin
- **Categories** - Organize FAQs by topic using the FAQ Category taxonomy
- **Priority Ordering** - Control display order with priority field (1-100, lower = first)
- **Date Filtering** - Optional start/end dates to show time-limited FAQs
- **Accordion Display** - Clean, accessible accordion UI
- **Meilisearch Integration** - FAQs indexed for site-wide search

## Installation

1. Upload the `parish-faq` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress Plugins menu
3. Configure Meilisearch settings in the Parish Search plugin (shared configuration)

## Usage

### Adding FAQs

1. Go to **FAQs > Add New** in WordPress admin
2. Enter the question as the title
3. Enter the answer in the content editor
4. Set optional fields:
   - **Priority** - Lower numbers appear first (default: 10)
   - **Start Date** - FAQ hidden before this date
   - **End Date** - FAQ hidden after this date
   - **Category** - Assign to one or more FAQ categories

### Shortcode

Display FAQs using the `[parish_faq]` shortcode:

```
[parish_faq]                        # All FAQs
[parish_faq category="planning"]    # Filter by category slug
[parish_faq limit="5"]              # Limit number shown
[parish_faq collapsed="false"]      # Start with all expanded
```

#### Attributes

| Attribute | Default | Description |
|-----------|---------|-------------|
| `category` | (none) | Filter by category slug |
| `limit` | -1 (all) | Maximum FAQs to display |
| `collapsed` | `true` | Start with answers collapsed |

### Example

```html
<h2>Planning FAQs</h2>
[parish_faq category="planning" limit="10"]

<h2>All FAQs</h2>
[parish_faq]
```

## Meilisearch Integration

FAQs are automatically indexed to Meilisearch when published. The plugin reuses settings from the Parish Search plugin:

- `parish_search_api_url` - Meilisearch server URL
- `parish_search_admin_key` - Admin API key for indexing

Each FAQ is indexed with:
- `id` - `faq_{post_id}`
- `type` - `faq`
- `title` - The question
- `content` - The answer (text only)
- `categories` - Array of category names

Search for FAQs using `type:faq` in the search grammar.

## Styling

The plugin includes default CSS. Override styles by targeting:

- `.parish-faq-container` - Main wrapper
- `.parish-faq-accordion` - Accordion container
- `.parish-faq-item` - Individual FAQ
- `.parish-faq-question` - Question button
- `.parish-faq-answer` - Answer content

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Parish Search plugin (for Meilisearch settings)

## License

GPL v2 or later
