<?php

// ============================================================================
// 04 - N+1 Problem - Full Mastery
// ============================================================================

// =============================================================================
// 1. DEMONSTRATING THE N+1 PROBLEM
// =============================================================================

/**
 * THE PROBLEM: N+1 Query Issue
 * 
 * Scenario: Display 100 posts with their authors
 * Without optimization: 101 queries (1 for posts + 100 for authors)
 */
class BlogPostsController
{
  // ❌ BAD: Creates N+1 problem
  public function indexBad()
  {
    DB::enableQueryLog();

    // Query 1: Fetch all posts
    $posts = Post::all();  // 1 query

    // Queries 2-101: For each post, fetch its author
    foreach ($posts as $post) {
      $author = $post->author;  // 100 additional queries!
      echo $post->title . ' by ' . $author->name;
    }

    // Total: 101 queries ❌
    echo "Total Queries: " . count(DB::getQueryLog());  // Output: 101

    return $posts;
  }

  // ✅ GOOD: Use eager loading
  public function indexGood()
  {
    DB::enableQueryLog();

    // Query 1: Fetch posts
    // Query 2: Fetch all related authors in one query
    $posts = Post::with('author')->get();  // 2 queries total!

    foreach ($posts as $post) {
      echo $post->title . ' by ' . $post->author->name;
    }

    // Total: 2 queries ✓
    echo "Total Queries: " . count(DB::getQueryLog());  // Output: 2

    return $posts;
  }
}

// Generated SQL Comparison:
/*
❌ BAD (N+1):
Query 1: SELECT * FROM posts;
Query 2: SELECT * FROM users WHERE id = 1;
Query 3: SELECT * FROM users WHERE id = 2;
Query 4: SELECT * FROM users WHERE id = 1;  // Duplicate!
...
Query 101: SELECT * FROM users WHERE id = 100;

✅ GOOD (Eager Loading):
Query 1: SELECT * FROM posts;
Query 2: SELECT * FROM users WHERE id IN (1, 2, 3, 4, ...);
*/

// =============================================================================
// 2. EAGER LOADING WITH with()
// =============================================================================

class EagerLoadingExamples
{
  /**
   * Single relationship eager loading
   */
  public function singleRelationship()
  {
    // Load posts with their authors
    $posts = Post::with('author')->get();

    // Safe to access - no additional queries
    foreach ($posts as $post) {
      echo $post->author->name;
    }
  }

  /**
   * Multiple relationships
   */
  public function multipleRelationships()
  {
    // Load posts with multiple relationships
    $posts = Post::with('author', 'category', 'comments')->get();

    // 3 queries total:
    // Query 1: SELECT * FROM posts
    // Query 2: SELECT * FROM users WHERE id IN (...)
    // Query 3: SELECT * FROM categories WHERE id IN (...)
    // Query 4: SELECT * FROM comments WHERE post_id IN (...)

    return $posts;
  }

  /**
   * Array syntax (more readable for multiple)
   */
  public function arrayRelationships()
  {
    $posts = Post::with([
      'author',
      'category',
      'comments',
      'tags'
    ])->get();

    return $posts;
  }

  /**
   * Nested eager loading (relationships of relationships)
   */
  public function nestedRelationships()
  {
    // Load: Post -> Author -> Company
    $posts = Post::with('author.company')->get();

    // 3 queries total:
    // Query 1: SELECT * FROM posts
    // Query 2: SELECT * FROM users WHERE id IN (...)
    // Query 3: SELECT * FROM companies WHERE id IN (...)

    foreach ($posts as $post) {
      echo $post->author->company->name;  // No N+1!
    }
  }

  /**
   * Deep nested relationships
   */
  public function deepNesting()
  {
    $posts = Post::with([
      'author.company.department.manager',
      'comments.author.profile.avatar',
      'category.subcategories'
    ])->get();

    // This creates multiple queries, but still optimized
    // Better than N+1 because all data fetched at once
  }

  /**
   * Filtering related data during eager load
   */
  public function filteredEagerLoad()
  {
    // Only eager load approved comments
    $posts = Post::with(['comments' => function ($query) {
      $query->where('approved', true)
        ->orderBy('created_at', 'desc');
    }])->get();

    // Also works with nested relationships
    $posts = Post::with(['author.company' => function ($query) {
      $query->where('status', 'active');
    }])->get();
  }
}

// =============================================================================
// 3. withCount() - COUNT WITHOUT LOADING DATA
// =============================================================================

class CountingExamples
{
  /**
   * ❌ BAD: Loading all data just to count
   */
  public function countBad()
  {
    // This loads ALL comments into memory!
    $posts = Post::with('comments')->get();

    foreach ($posts as $post) {
      // Uses PHP count, not database count
      $count = $post->comments->count();  // Inefficient
      echo "Post has $count comments";
    }

    // Problem: 10,000 comments loaded into memory = slow!
  }

  /**
   * ✅ GOOD: Count in database, don't load data
   */
  public function countGood()
  {
    // Uses COUNT query - no loading
    $posts = Post::withCount('comments')->get();

    foreach ($posts as $post) {
      // Accesses database-calculated count
      $count = $post->comments_count;  // Efficient!
      echo "Post has $count comments";
    }
  }

  /**
   * Count multiple relationships
   */
  public function countMultiple()
  {
    $posts = Post::withCount([
      'comments',
      'likes',
      'shares'
    ])->get();

    foreach ($posts as $post) {
      echo "Comments: " . $post->comments_count;
      echo "Likes: " . $post->likes_count;
      echo "Shares: " . $post->shares_count;
    }
  }

  /**
   * Count with conditions (filtered count)
   */
  public function countWithConditions()
  {
    $posts = Post::withCount([
      'comments' => function ($query) {
        $query->where('approved', true);
      },
      'likes' => function ($query) {
        $query->where('type', 'love');
      }
    ])->get();

    foreach ($posts as $post) {
      echo "Approved comments: " . $post->comments_count;
      echo "Love likes: " . $post->likes_count;
    }
  }

  /**
   * Combine with eager loading
   */
  public function combineLoadAndCount()
  {
    $posts = Post::with('author', 'category')
      ->withCount(['comments', 'likes'])
      ->get();

    // Efficient! Loads all relationships + counts
    foreach ($posts as $post) {
      echo $post->title;
      echo "by " . $post->author->name;
      echo " in " . $post->category->name;
      echo " - " . $post->comments_count . " comments";
    }
  }

  /**
   * Count aliases (rename the count column)
   */
  public function countAliases()
  {
    $posts = Post::withCount([
      'comments as approved_comments_count' => function ($query) {
        $query->where('approved', true);
      },
      'comments as pending_comments_count' => function ($query) {
        $query->where('approved', false);
      }
    ])->get();

    foreach ($posts as $post) {
      echo "Approved: " . $post->approved_comments_count;
      echo "Pending: " . $post->pending_comments_count;
    }
  }
}

// =============================================================================
// 4. LAZY EAGER LOADING - load() & loadMissing()
// =============================================================================

class LazyEagerLoadingExamples
{
  /**
   * Use load() when data already fetched
   * ⚠️ Use sparingly - primarily for conditional loading
   */
  public function loadAfterFetch()
  {
    // Data already fetched
    $posts = Post::all();

    // Later, load relationships
    $posts->load('author', 'comments');

    // Now safe to access
    foreach ($posts as $post) {
      echo $post->author->name;
    }
  }

  /**
   * loadMissing() - Safer alternative
   * Only loads if not already loaded
   */
  public function loadMissing()
  {
    $posts = Post::with('author')->get();

    // Checks if 'comments' already loaded
    // Only loads if needed
    $posts->loadMissing('comments');

    // Safe - won't create duplicate query
  }

  /**
   * ❌ DON'T: Load in loops (creates N+1!)
   */
  public function badLoadInLoop()
  {
    $posts = Post::all();

    foreach ($posts as $post) {
      // ❌ WRONG: Creates 100 queries!
      $post->load('comments');
    }
  }

  /**
   * ✅ DO: Load once before loop
   */
  public function goodLoadBeforeLoop()
  {
    $posts = Post::all();

    // Load all relationships once
    $posts->load('comments', 'author');

    // Now iterate safely
    foreach ($posts as $post) {
      echo $post->comments->count();
    }
  }

  /**
   * Conditional loading in service
   */
  public function conditionalLoading($includeComments = false)
  {
    $posts = Post::with('author')->get();

    // Conditionally load more data
    if ($includeComments) {
      $posts->loadMissing('comments');
    }

    return $posts;
  }
}

// =============================================================================
// 5. COLUMN SELECTION - select()
// =============================================================================

class ColumnSelectionExamples
{
  /**
   * ❌ BAD: Load unnecessary columns
   */
  public function selectBad()
  {
    // Loads all 50 columns from posts table
    $posts = Post::all();

    // Only using 3 fields
    foreach ($posts as $post) {
      echo $post->id . ' ' . $post->title . ' ' . $post->slug;
    }

    // Wasted memory and bandwidth!
  }

  /**
   * ✅ GOOD: Select only needed columns
   */
  public function selectGood()
  {
    // Load only required columns
    $posts = Post::select('id', 'title', 'slug')->get();

    foreach ($posts as $post) {
      echo $post->id . ' ' . $post->title . ' ' . $post->slug;
    }

    // More efficient!
  }

  /**
   * Select with relationships
   * ⚠️ Important: Include foreign key!
   */
  public function selectWithRelationships()
  {
    // Must include user_id to establish relationship!
    $posts = Post::select('id', 'title', 'user_id')
      ->with('author:id,name,email')
      ->get();

    foreach ($posts as $post) {
      echo $post->title . ' by ' . $post->author->name;
    }
  }

  /**
   * Exclude certain columns
   */
  public function selectExclude()
  {
    // Get all columns except heavy ones
    $posts = Post::select(DB::raw('`id`, `title`, `slug`, `user_id`'))
      ->get();

    // Or use selectRaw for complex queries
    $posts = Post::selectRaw('id, title, slug, user_id')
      ->get();
  }

  /**
   * Complex selection with multiple relationships
   */
  public function complexSelection()
  {
    $posts = Post::select('id', 'title', 'content', 'user_id', 'category_id', 'created_at')
      ->with([
        'author:id,name,email,avatar',
        'category:id,name,slug',
        'comments:id,post_id,content,user_id,created_at'
      ])
      ->withCount([
        'likes',
        'shares'
      ])
      ->orderBy('created_at', 'desc')
      ->paginate(20);

    return $posts;
  }

  /**
   * Select with database functions
   */
  public function selectWithFunctions()
  {
    $posts = Post::select('id', 'title')
      ->selectRaw('SUBSTRING(content, 1, 100) as preview')
      ->selectRaw('DATE(created_at) as posted_date')
      ->with('author:id,name')
      ->get();
  }
}

// =============================================================================
// 6. DETECTING N+1 PROBLEMS
// =============================================================================

class DetectingN1Examples
{
  /**
   * Enable query logging (for development/testing)
   */
  public function enableQueryLogging()
  {
    DB::enableQueryLog();

    // Your code here
    $posts = Post::all();

    // Get all executed queries
    $queries = DB::getQueryLog();

    foreach ($queries as $query) {
      echo "Query: " . $query['query'];
      echo "Bindings: " . json_encode($query['bindings']);
      echo "Time: " . $query['time'] . "ms";
    }

    echo "Total Queries: " . count($queries);
  }

  /**
   * Using Laravel Debugbar (Best for development)
   * Install: composer require barryvdh/laravel-debugbar --dev
   */
  public function withDebugbar()
  {
    // Automatically shows:
    // - Number of queries
    // - Query time
    // - Duplicate queries (N+1 indicator)
    // - Slow queries
    // - Full SQL and bindings

    $posts = Post::all();  // Shows in debugbar
  }

  /**
   * Unit test to verify no N+1
   */
  public function testNoPlusOneInTests()
  {
    // In your test file
    DB::enableQueryLog();

    $posts = Post::with('author')->get();

    // Should be 2 queries (posts + authors)
    $this->assertEquals(2, count(DB::getQueryLog()));
  }

  /**
   * Helper function to detect N+1
   */
  public function detectN1Helper()
  {
    $initialCount = count(DB::getQueryLog());

    // Your code
    $posts = Post::all();

    $newQueries = count(DB::getQueryLog()) - $initialCount;

    echo "Queries executed: " . $newQueries;

    return $newQueries;
  }
}

// =============================================================================
// 7. REAL-WORLD SCENARIOS & BEST PRACTICES
// =============================================================================

/**
 * SCENARIO 1: Blog API - Get Posts with Author and Comments
 */
class BlogApiController
{
  public function getPosts()
  {
    // Optimized query for API
    return Post::select('id', 'title', 'slug', 'content', 'user_id', 'created_at')
      ->with([
        'author:id,name,email,avatar',
        'comments:id,post_id,content,user_id,created_at',
        'tags:id,name,slug'
      ])
      ->withCount(['likes', 'comments'])
      ->orderBy('created_at', 'desc')
      ->paginate(15);
  }
}

/**
 * SCENARIO 2: Dashboard - Multiple Statistics
 */
class DashboardController
{
  public function getDashboard()
  {
    return [
      'users' => User::withCount(['posts', 'comments'])->get(),
      'posts' => Post::with('author')
        ->withCount('comments')
        ->latest()
        ->limit(10)
        ->get(),
      'recentComments' => Comment::with('post', 'author')
        ->latest()
        ->limit(5)
        ->get()
    ];
  }
}

/**
 * SCENARIO 3: Repository Pattern (Best Practice)
 */
class PostRepository
{
  /**
   * Get posts for listing
   */
  public function listPosts()
  {
    return Post::select('id', 'title', 'slug', 'user_id', 'created_at')
      ->with('author:id,name')
      ->withCount('comments')
      ->orderBy('created_at', 'desc')
      ->paginate(20);
  }

  /**
   * Get single post with all details
   */
  public function getPostDetail($id)
  {
    return Post::with([
      'author:id,name,email,avatar',
      'comments.author:id,name,avatar',
      'tags:id,name,slug'
    ])
      ->withCount(['likes', 'shares'])
      ->findOrFail($id);
  }

  /**
   * Get posts with statistics
   */
  public function getPostsWithStats()
  {
    return Post::select('id', 'title', 'user_id')
      ->with('author:id,name')
      ->withCount([
        'comments',
        'likes',
        'views',
        'shares'
      ])
      ->orderByDesc('likes_count')
      ->limit(10)
      ->get();
  }
}

/**
 * SCENARIO 4: Query Builder Helper
 */
class OptimizedQueryBuilder
{
  /**
   * For API responses
   */
  public static function forApi()
  {
    return Post::select('id', 'title', 'slug', 'user_id', 'created_at')
      ->with([
        'author:id,name,avatar',
        'category:id,name'
      ])
      ->withCount('comments');
  }

  /**
   * For admin dashboard
   */
  public static function forAdmin()
  {
    return Post::with(['author:id,name,email', 'category:id,name'])
      ->withCount(['comments', 'views'])
      ->orderBy('created_at', 'desc');
  }

  /**
   * For search results
   */
  public static function forSearch()
  {
    return Post::select('id', 'title', 'slug', 'content', 'user_id')
      ->with('author:id,name')
      ->withCount('comments');
  }
}

// =============================================================================
// 8. TESTING N+1 PROBLEMS
// =============================================================================

class PostTest
{
  /**
   * Test: Ensure list endpoint doesn't have N+1
   */
  public function testListPostsNoNPlusOne()
  {
    DB::enableQueryLog();

    // Create 50 posts with authors
    $posts = factory(Post::class, 50)
      ->create()
      ->each(function ($post) {
        $post->author;  // Ensure authors exist
      });

    // Clear query log
    DB::flushQueryLog();

    // Call the endpoint
    $response = $this->get('/api/posts');

    // Should have minimal queries (2-3, not 50+)
    $queries = DB::getQueryLog();

    $this->assertLessThan(
      4,
      count($queries),
      'Too many queries! Possible N+1 problem.'
    );
  }

  /**
   * Test: Ensure detail endpoint uses eager loading
   */
  public function testPostDetailUsesEagerLoading()
  {
    $post = factory(Post::class)->create();

    DB::enableQueryLog();

    $response = $this->get("/api/posts/{$post->id}");

    // Should be minimal queries
    $queries = DB::getQueryLog();

    $this->assertLessThan(
      5,
      count($queries),
      'Post detail endpoint has N+1 problem'
    );
  }
}

// =============================================================================
// SUMMARY: N+1 Problem Solutions
// =============================================================================

/*
❌ PROBLEMS TO AVOID:
1. $posts = Post::all(); // Then access $post->author in loop
2. Loading relationships in loops
3. Using count() on loaded relationships instead of withCount()
4. Loading unnecessary columns
5. Not testing for N+1 in development

✅ SOLUTIONS TO USE:
1. with() - Eager load relationships
2. withCount() - Count without loading
3. select() - Load only needed columns
4. Nested loading with dot notation
5. Repository pattern for reusable queries
6. Test with Laravel Debugbar
7. Write unit tests for query counts

🎯 PERFORMANCE IMPROVEMENTS:
- Reduce queries from 101 to 2-3
- Decrease response time 50-100x
- Reduce server load
- Lower hosting costs
- Better user experience
*/
