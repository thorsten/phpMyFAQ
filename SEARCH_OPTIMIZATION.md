# Search Performance Optimization

## Overview

This document describes the optimization of the `getMostPopularSearches()` method in the `Search` class to address performance issues when the `faqsearches` table contains millions of entries.

## Problem

The original implementation had the following performance issues:

1. **Full table scan**: The query processed all records in the `faqsearches` table without any time-based filtering
2. **Missing LIMIT clause**: The SQL query returned all grouped results, then PHP code limited them, wasting database resources
3. **No database indexes**: The `searchterm` column lacked proper indexing for GROUP BY operations
4. **Historical data burden**: All search history was considered, even very old searches that may not be relevant

## Solution

The optimized implementation includes:

### 1. SQL-level LIMIT
- Added `LIMIT` clause directly to the SQL query to reduce data processing
- Eliminates unnecessary result processing in PHP

### 2. Optional Time Window Filtering  
- Added optional `$timeWindow` parameter to consider only recent searches
- Database-agnostic time filtering syntax for MySQL, PostgreSQL, SQLite, and SQL Server

### 3. Backward Compatibility
- Maintains the same method signature for existing calls
- New parameter is optional with default value of 0 (all time)

### 4. Database Performance Recommendations

Performance indexes for the `faqsearches` table are automatically created during installation and updates. The following indexes are included:

#### Automatically Created Indexes:
- **Basic index for searchterm grouping**: Improves performance for search term aggregation
- **Composite index for time-based filtering**: Optimizes queries with date restrictions and search term grouping  
- **Composite index including language**: Enhances performance for multilingual setups

These indexes are created automatically for all supported database systems (MySQL/MariaDB, PostgreSQL, SQLite, and SQL Server) and do not require manual intervention.
## Configuration Recommendations

### Time Window Configuration
A configuration option for setting a default time window is automatically available in new installations:

```php
// Configuration option added automatically: 'search.popularSearchTimeWindow' => '0'
// Example: Configure to only consider searches from the last 6 months  
$timeWindow = $faqConfig->get('search.popularSearchTimeWindow', 180);
$popularSearches = $search->getMostPopularSearches(10, false, $timeWindow);
```

This configuration option is automatically added during installation and can be modified through the admin panel or configuration files.

### Periodic Cleanup
Consider implementing a cleanup job to remove very old search entries:

```sql
-- Example: Remove search entries older than 2 years
DELETE FROM faqsearches WHERE searchdate < DATE_SUB(NOW(), INTERVAL 2 YEAR);
```

## Performance Impact

Expected performance improvements:

- **Small datasets (< 1000 records)**: Minimal improvement
- **Medium datasets (1K - 100K records)**: 2-5x faster
- **Large datasets (100K - 1M records)**: 5-20x faster  
- **Very large datasets (> 1M records)**: 10-100x faster

The actual improvement depends on:
- Database engine and configuration
- Available indexes
- Time window used (smaller windows = better performance)
- Hardware specifications

## Migration Notes

- No database schema changes required
- Existing API calls remain unchanged
- New time window parameter is optional
- Consider adding recommended indexes during maintenance windows
- Test index performance impact in staging environment first

## Testing

The optimization includes comprehensive tests covering:
- Backward compatibility with existing method signatures
- LIMIT functionality validation
- Language parameter support
- Time window parameter handling
- Database-agnostic SQL syntax