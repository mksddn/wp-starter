---
name: acf-management
description: ACF field management, JSON synchronization, and best practices. Use when working with Advanced Custom Fields, JSON files, or field groups.
---

# ACF (Advanced Custom Fields) Management

## ACF JSON Sync

This project uses ACF Local JSON for version control of field groups.

### JSON File Locations

ACF JSON files are stored in:
1. Child theme: `wp-content/themes/child-theme/acf-json/`
2. Parent theme: `wp-content/themes/wp-theme/acf-json/`
3. Fallback: `wp-content/acf-json/`

Priority: Child theme → Parent theme → Fallback

### Modified Timestamp

**CRITICAL:** When modifying any ACF JSON file, update the `modified` field:

```json
{
  "key": "group_xxx",
  "title": "Group Title",
  "modified": 1738800000
}
```

**How to update:**
- Use Unix timestamp (seconds since epoch)
- Command: `date +%s`
- PHP: `time()`

### ACF JSON Structure

```json
{
  "key": "group_unique_key",
  "title": "Field Group Title",
  "fields": [
    {
      "key": "field_unique_key",
      "label": "Field Label",
      "name": "field_name",
      "type": "text"
    }
  ],
  "location": [
    [
      {
        "param": "post_type",
        "operator": "==",
        "value": "page"
      }
    ]
  ],
  "menu_order": 0,
  "position": "normal",
  "style": "default",
  "label_placement": "top",
  "instruction_placement": "label",
  "hide_on_screen": "",
  "active": true,
  "description": "",
  "modified": 1738800000
}
```

### Working with ACF Fields

**Get field value:**
```php
$value = get_field( 'field_name' );
$value = get_field( 'field_name', $post_id );
```

**Get all fields:**
```php
$fields = get_fields( $post_id );
```

**Update field:**
```php
update_field( 'field_name', $value, $post_id );
```

**Check if field exists:**
```php
if ( function_exists( 'get_field' ) ) {
    $value = get_field( 'field_name' );
}
```

### ACF Options Pages

**Get options page data:**
```php
$options = get_fields( 'option' );
$specific_option = get_field( 'field_name', 'option' );
```

**Update options:**
```php
update_field( 'field_name', $value, 'option' );
```

### ACF in REST API

To expose ACF fields in REST API:
- Set `show_in_rest` to `true` in field group settings
- Or use filter: `acf/rest_api/field_settings/show_in_rest`

### Best Practices

1. **Always update modified timestamp** when editing JSON files
2. **Use unique keys** - Never duplicate field/group keys
3. **Version control** - Commit JSON files to git
4. **Sync strategy** - Edit in admin → JSON auto-updates → Commit JSON
5. **Field names** - Use lowercase with underscores (snake_case)
6. **Check ACF exists** - Always check `function_exists( 'get_field' )` before using ACF functions

### Common Issues

**Fields not showing:**
- Check location rules
- Verify field group is active
- Check if ACF plugin is active

**JSON not syncing:**
- Check file permissions
- Verify directory exists and is writable
- Check ACF settings for JSON save path
