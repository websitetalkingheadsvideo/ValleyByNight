# Admin Table Responsive System

## Overview

This system implements a strict no-horizontal-scroll policy for admin data tables. Columns are **hidden** (not compressed) when viewport width is insufficient, ensuring tables always fit within the visible window.

## Core Principle

**NEVER allow horizontal scrollbars at any viewport width.**

## Implementation Files

- `css/admin_table_responsive.css` - Reusable responsive system for all admin tables
- `css/admin_locations.css` - Locations-specific styles (includes responsive rules)
- `css/admin_items.css` - Items-specific styles (includes responsive rules)

## Column Priority System

### Locations Table (`#locationsTable`)

**Column Order:**
1. **ID** (lowest priority - hides first)
2. **Name** (ALWAYS VISIBLE - highest priority)
3. Type
4. Status
5. District
6. Owner Type
7. PC Earnable
8. **Actions** (ALWAYS VISIBLE - highest priority)

**Breakpoint Logic:**

| Viewport Width | Visible Columns | Hidden Columns |
|----------------|-----------------|----------------|
| > 1400px | All 8 columns | None |
| ≤ 1400px | Name, Type, Status, District, Owner Type, PC Earnable, Actions | ID |
| ≤ 1200px | Name, Type, Status, District, Owner Type, Actions | ID, PC Earnable |
| ≤ 1000px | Name, Type, Status, District, Actions | ID, PC Earnable, Owner Type |
| ≤ 800px | Name, Type, Status, Actions | ID, PC Earnable, Owner Type, District |
| ≤ 650px | Name, Type, Actions | ID, PC Earnable, Owner Type, District, Status |
| ≤ 500px | **Name, Actions** | All others |
| ≤ 400px | **Name, Actions** (reduced padding) | All others |

### Items Table (`#itemsTable`)

**Column Order:**
1. **ID** (lowest priority - hides first)
2. **Name** (ALWAYS VISIBLE - highest priority)
3. Type
4. Category
5. Damage
6. Range
7. Rarity
8. Price
9. **Actions** (ALWAYS VISIBLE - highest priority)

**Breakpoint Logic:**

| Viewport Width | Visible Columns | Hidden Columns |
|----------------|-----------------|----------------|
| > 1600px | All 9 columns | None |
| ≤ 1600px | Name, Type, Category, Damage, Range, Rarity, Price, Actions | ID |
| ≤ 1400px | Name, Type, Category, Damage, Range, Rarity, Actions | ID, Price |
| ≤ 1200px | Name, Type, Category, Damage, Range, Actions | ID, Price, Rarity |
| ≤ 1000px | Name, Type, Category, Damage, Actions | ID, Price, Rarity, Range |
| ≤ 850px | Name, Type, Category, Actions | ID, Price, Rarity, Range, Damage |
| ≤ 700px | Name, Type, Actions | ID, Price, Rarity, Range, Damage, Category |
| ≤ 550px | **Name, Actions** | All others |

## CSS Implementation Details

### Global Rules

```css
.table-responsive {
    overflow-x: hidden !important; /* ABSOLUTE: No horizontal scrollbars ever */
    overflow-y: auto;
    position: relative;
}
```

### Column Hiding Mechanism

Columns are hidden using `display: none` on both `<th>` and `<td>` elements:

```css
@media (max-width: 1399.98px) {
    #locationsTable th:nth-child(1),
    #locationsTable td:nth-child(1) {
        display: none;
    }
}
```

### Always-Visible Columns

Name (2nd column) and Actions (last column) are forced to remain visible:

```css
.admin-table-responsive table th:nth-child(2),
.admin-table-responsive table td:nth-child(2),
.admin-table-responsive table th:last-child,
.admin-table-responsive table td:last-child {
    display: table-cell !important;
}
```

## Usage

### For New Admin Tables

1. Include `admin_table_responsive.css` in your page's `$extra_css` array
2. Ensure table structure:
   - Name is the **2nd column** (`nth-child(2)`)
   - Actions is the **last column** (`:last-child`)
3. Add table-specific breakpoints to `admin_table_responsive.css` following the pattern
4. Use `table-responsive` class on table wrapper
5. Remove any `min-width` constraints from table CSS

### Example

```php
// In admin_your_table.php
$extra_css = ['css/admin_table_responsive.css', 'css/admin_your_table.css'];
```

```html
<div class="table-responsive rounded-3">
    <table class="table table-dark table-hover" id="yourTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th> <!-- 2nd column - always visible -->
                <th>Column 3</th>
                <th>Column 4</th>
                <th>Actions</th> <!-- Last column - always visible -->
            </tr>
        </thead>
        <tbody>
            <!-- Table rows -->
        </tbody>
    </table>
</div>
```

## Testing Checklist

- [ ] No horizontal scrollbar appears at any viewport width
- [ ] Name column remains visible at all widths
- [ ] Actions column remains visible at all widths
- [ ] Columns hide in correct priority order as viewport narrows
- [ ] Columns reappear in reverse order as viewport widens
- [ ] Table fits entirely within visible window width
- [ ] Mobile view (≤500px) shows only Name + Actions

## Browser Compatibility

- Modern browsers (Chrome, Firefox, Safari, Edge)
- CSS Grid and Flexbox support required
- Media queries with `max-width` breakpoints

## Notes

- Breakpoints use `.98px` suffix to avoid conflicts with Bootstrap's breakpoints
- `table-layout: auto` allows flexible column widths (changed from `fixed`)
- Sticky positioning removed from Actions column (not needed without horizontal scroll)
- All `min-width` constraints removed from tables to prevent forced overflow
