# 04 - N+1 Problem - Full Mastery

## Understanding the N+1 Problem

### What is N+1?

The N+1 problem occurs when you make **1 query to fetch N records**, then **N additional queries** to fetch related data for each record. This results in **N+1 total database queries** instead of the optimal 1-2 queries.

### Real-World Impact on Performance

**Poor Performance (N+1 Problem):**

```
// Fetching 100 posts
Query 1: SELECT * FROM posts LIMIT 100;  -- 1 query
Query 2-101: SELECT * FROM users WHERE id = ?;  -- 100 queries (one per post)

Total: 101 queries ❌
Network round trips: 101
Response time: SLOW
Database load: HIGH
```

**Optimal Performance (Eager Loading):**

```
Query 1: SELECT * FROM posts LIMIT 100;  -- 1 query
Query 2: SELECT * FROM users WHERE id IN (1, 2, 3, ...);  -- 1 query

Total: 2 queries ✓
Network round trips: 2
Response time: FAST
Database load: LOW
```

### Why This Matters at Scale

- **API Endpoints**: 1000 posts × 100 users = 100,100 queries! 😱
- **Page Loads**: Each N+1 adds seconds to response time
- **Database Limits**: MySQL connection pools get exhausted
- **User Experience**: Timeouts and slow pages
- **Hosting Costs**: More CPU/Memory = higher bills

---

## Key Concepts

### 1. **Eager Loading with `with()`**

Load related data in one query instead of multiple queries.

**Syntax:**

```php
// Single relationship
$posts = Post::with('author')->get();

// Multiple relationships
$posts = Post::with('author', 'category', 'comments')->get();

// Array syntax (alternative)
$posts = Post::with(['author', 'category'])->get();
```

**How it works:**

- Laravel fetches posts
- Laravel fetches all related users in ONE query
- Uses `IN` clause: `WHERE id IN (1, 2, 3, ...)`

---

### 2. **Nested Eager Loading with Dot Notation**

Load relationships of relationships.

**Syntax:**

```php
// Load post -> author -> company
$posts = Post::with('author.company')->get();

// Multiple nested levels
$posts = Post::with([
    'author.company.department',
    'comments.author.profile'
])->get();
```

**Query Count:**

- Load posts (1 query)
- Load users via posts (1 query)
- Load companies via users (1 query)
- Total: 3 queries (instead of 1000+)

---

### 3. **`withCount()` - Count Without Loading**

Count related records without loading all the data.

**The Problem:**

```php
// WRONG: Loads all 10,000 comments into memory!
$posts = Post::with('comments')->get();
foreach ($posts as $post) {
    echo $post->comments->count();  // Uses PHP count, wastes memory
}
```

**The Solution:**

```php
// RIGHT: Adds COUNT query, no loading
$posts = Post::withCount('comments')->get();
foreach ($posts as $post) {
    echo $post->comments_count;  // Database-calculated count
}
```

**Advanced Usage:**

```php
// Count with conditions
$posts = Post::withCount(['comments' => function ($query) {
    $query->where('approved', true);
}])->get();

// Multiple counts
$posts = Post::withCount('comments', 'likes', 'shares')->get();

// Combine with eager loading
$posts = Post::with('author')
    ->withCount(['comments', 'likes'])
    ->get();
```

---

### 4. **Lazy Eager Loading (After Fetch)**

Load relations AFTER you've already fetched data. ⚠️ **Use sparingly!**

**Syntax:**

```php
$posts = Post::all();  // Already fetched

// Load relationships after the fact
$posts->load('author', 'comments');
// OR
$posts->loadCount('comments');
```

**When to use:**

- When eager loading wasn't possible initially
- Conditional loading based on runtime logic
- In controllers/services after validation

**When NOT to use:**

- Avoid in loops! (creates N+1)
- Use `loadMissing()` for safety (only loads if not already loaded)

```php
// SAFE: Only loads if not already loaded
$posts->loadMissing('author');
```

---

### 5. **Column Selection (`select()`)**

Avoid loading unnecessary columns.

**Problem:**

```php
// Loads 50 columns when you only need 2
$posts = Post::all();  // Memory waste!
```

**Solution:**

```php
// Only load what you need
$posts = Post::select('id', 'title', 'slug')->get();

// With relationships - must include foreign key
$posts = Post::select('id', 'title', 'user_id')
    ->with('author:id,name,email')
    ->get();

// Multiple columns with relationships
$posts = Post::select('id', 'title', 'user_id', 'created_at')
    ->with([
        'author:id,name,email',
        'comments:id,post_id,content,user_id'
    ])
    ->get();
```

**Important:** Always include the foreign key in related model selection!

---

### 6. **Detecting N+1 Problems**

#### Laravel Debugbar (Development)

```php
// Install debugbar first:
// composer require barryvdh/laravel-debugbar --dev

// Enable in .env
APP_DEBUG=true

// It automatically shows:
// - Query count
// - Duplicate queries
// - Slow queries
// - Full query details
```

#### Query Logging

```php
// Manually log queries
DB::enableQueryLog();

$posts = Post::with('author')->get();

$queries = DB::getQueryLog();
dd($queries);
// Shows exact SQL and bind parameters

// Count queries
echo count(DB::getQueryLog());  // Should be 2, not 101!
```

#### Helper Function for Testing

```php
// Check query count in tests
public function testPostsWithoutNPlusOne()
{
    DB::enableQueryLog();

    $posts = Post::with('author')->get();

    // Should be 2 queries (posts + users)
    $this->assertEquals(2, count(DB::getQueryLog()));
}
```

---

### 7. **Advanced Techniques**

#### Conditional Eager Loading

```php
$posts = Post::when($includeComments, function ($query) {
    return $query->with('comments');
})
->when($includeAuthor, function ($query) {
    return $query->with('author');
})
->get();
```

#### Relationship Exists Check (Without Loading)

```php
// Check if relationship exists without loading data
$postsWithComments = Post::has('comments')->get();

// Multiple relationships
$posts = Post::has('comments')
    ->has('author')
    ->get();
```

#### Optimized Query Builder

```php
class PostRepository
{
    public function getPostsForAPI()
    {
        return Post::select('id', 'title', 'user_id', 'created_at')
            ->with([
                'author:id,name,avatar',
                'comments:id,post_id,content',
            ])
            ->withCount('likes')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }
}
```

---

## Performance Comparison Chart

| Approach        | Queries | Time   | Memory | Network Calls |
| --------------- | ------- | ------ | ------ | ------------- |
| N+1 (100 posts) | 101     | 5000ms | HIGH   | 101           |
| Eager Loading   | 2       | 50ms   | LOW    | 2             |
| Eager + Select  | 2       | 45ms   | LOWER  | 2             |
| Eager + Count   | 3       | 60ms   | LOW    | 3             |

---

## Best Practices Checklist

✅ **DO:**

- Always use `with()` for relationships
- Use `withCount()` instead of loading relations for counting
- Use `select()` to limit columns
- Test with Laravel Debugbar
- Use nested loading for complex data structures
- Use `loadMissing()` for conditional lazy loading

❌ **DON'T:**

- Load relationships in loops
- Load unused columns
- Count by loading entire relationships
- Ignore N+1 in development
- Use lazy loading as default strategy
- Forget foreign keys in `select()`

---

## Checkpoint Questions

1. What is the difference between `with()` and `load()`?
2. When would you use `withCount()` over `with()`?
3. How do you detect N+1 problems?
4. Why must you include foreign keys in `select()`?
5. What's the query count for: `Post::with('author.company')->get()` on 100 posts?
6. How would you optimize loading 50 posts with comments, likes, and author info?

---

## Resources

- [Laravel Documentation - Eager Loading](https://laravel.com/docs/eloquent-relationships#eager-loading)
- [Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar)
- [Query Optimization Tips](https://laravel.com/docs/eloquent#querying-relationship-existence)
