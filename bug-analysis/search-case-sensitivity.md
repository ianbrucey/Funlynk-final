# Bug Analysis: Case-Sensitive Group Search

## Bug Description
The search bar on the `/groups` page is case-sensitive. Searching for 'hiking' does not match groups named 'Hiking', 'HIKING', or 'HiKiNg'.

## Expected Behavior
Search should be case-insensitive so users can find groups regardless of how they type the search term.

## 1. Root Cause Identification

The root cause of the case-sensitive search bug is the use of the `LIKE` operator in the database query within the `GroupsIndex` Livewire component. In PostgreSQL, which FunLynk utilizes, the `LIKE` operator performs a case-sensitive pattern match by default.

## 2. Current Implementation Examination

The relevant code is located in `app/Livewire/Groups/GroupsIndex.php`, specifically within the `render()` method where the search filter is applied.

**File:** `app/Livewire/Groups/GroupsIndex.php`
**Lines:** 101-103

```php
101:         if ($this->search) {
102:             $query->where('name', 'like', '%'.$this->search.'%')
103:                 ->orWhere('description', 'like', '%'.$this->search.'%');
104:         }
```

-   **Search Filtering:** The `where()` clauses use the `like` operator to filter `name` and `description` columns based on the `$this->search` property.
-   **SQL Query:** For a search term like 'hiking', the generated SQL would resemble:
    `SELECT * FROM groups WHERE name LIKE '%hiking%' OR description LIKE '%hiking%'`
-   **Case-Sensitive Operators:** The `LIKE` operator in PostgreSQL is inherently case-sensitive, leading to the observed bug.

## 3. Proposed Fix

To resolve the case sensitivity, the `ILIKE` operator should be used instead of `LIKE`. `ILIKE` is a PostgreSQL-specific operator that performs a case-insensitive pattern match.

**Proposed Code Change:**

```diff
--- a/app/Livewire/Groups/GroupsIndex.php
+++ b/app/Livewire/Groups/GroupsIndex.php
@@ -99,8 +99,8 @@
 // Apply search filter
 if ($this->search) {
-            $query->where('name', 'like', '%'.$this->search.'%')
-                ->orWhere('description', 'like', '%'.$this->search.'%');
+            $query->where('name', 'ilike', '%'.$this->search.'%')
+                ->orWhere('description', 'ilike', '%'.$this->search.'%');
         }

         // Apply privacy filter
```

**Explanation:**
By changing `like` to `ilike`, the database will perform a case-insensitive comparison. This means a search for 'hiking' will successfully match 'Hiking', 'HIKING', 'HiKiNg', and any other casing variations in the `name` or `description` columns.

## 4. Potential Side Effects and Considerations

1.  **Performance:**
    *   Using `ILIKE` can sometimes be slower than `LIKE` on very large datasets because standard B-tree indexes are not always effectively utilized for case-insensitive matching.
    *   **Recommendation:** For future optimization, if performance becomes an issue with very large group tables, consider adding a GIN or GiST index with the `pg_trgm` extension to the `name` and `description` columns. This type of index is designed to accelerate `ILIKE` and fuzzy matching queries. However, for the immediate bug fix, the change to `ILIKE` is sufficient.
2.  **Database Specificity:**
    *   `ILIKE` is a PostgreSQL-specific operator. If FunLynk were to ever migrate to a different database system (e.g., MySQL, SQL Server), this operator would need to be replaced with a database-agnostic solution (e.g., converting both the column and the search term to lowercase using `LOWER()` before comparison). Given FunLynk's established PostgreSQL stack, this is not an immediate concern.
3.  **Edge Cases:**
    *   The existing wildcard (`%`) behavior for partial matches will remain, but now with case-insensitivity. No new edge cases are introduced by this change regarding partial matching.

## 5. Testing Recommendations

### Unit/Feature Test (Pest)
A new test case should be added to ensure the case-insensitive search functions correctly.

1.  **Create Test File (if not exists):** `tests/Feature/Livewire/Groups/GroupsIndexTest.php`
2.  **Add Test Case:**
    *   Create several `Group` records with varying casing in their `name` and `description` (e.g., "Hiking Club", "HIKING ADVENTURES", "casual hiking").
    *   Simulate a Livewire component search with a case-insensitive term (e.g., 'hiking').
    *   Assert that all relevant groups, regardless of their original casing, are returned in the paginated results.

### Manual Testing
1.  Navigate to the `/groups` page in the browser.
2.  Use the search bar to search for existing group names or descriptions, intentionally using different casing than the actual data (e.g., if a group is named "Photography Enthusiasts", search for "photography", "PHOTOGRAPHY", or "pHotOgRapHy").
3.  Verify that the search results correctly display the groups that match the term, irrespective of casing.
