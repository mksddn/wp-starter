---
description: Update ACF JSON file with modified timestamp
---

When modifying any ACF JSON file in `acf-json/*.json`:

1. **Update modified field**: Set `"modified"` to current Unix timestamp
2. **Get timestamp**: Use `date +%s` command or PHP `time()` function
3. **Preserve structure**: Keep all other fields intact

**Example:**
```json
{
  "key": "group_xxx",
  "title": "Group Title",
  "fields": [...],
  "location": [...],
  "modified": 1738800000
}
```

**When updating:**
- Read the JSON file
- Update only the `modified` field
- Preserve JSON formatting (indentation, etc.)
- Use Unix timestamp (seconds since epoch)

**Command to get timestamp:**
```bash
date +%s
```

**In PHP:**
```php
$timestamp = time();
```
