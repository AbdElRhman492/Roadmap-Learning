<?php

// 02 - Eloquent ORM - Advanced
// Comprehensive code examples for all advanced Eloquent topics

// ============================================================================
// 1. ALL 6 RELATIONSHIP TYPES
// ============================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

// --- 1. ONE-TO-ONE ---
class User extends Model {
    // One user has one profile
    public function profile(): BelongsTo {
        return $this->hasOne(Profile::class);
    }
}

class Profile extends Model {
    // One profile belongs to one user
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}

// Usage:
// $user->profile; // Get user's profile
// $profile->user; // Get profile's user


// --- 2. ONE-TO-MANY ---
class Post extends Model {
    // One post has many comments
    public function comments(): HasMany {
        return $this->hasMany(Comment::class);
    }
}

class Comment extends Model {
    // Many comments belong to one post
    public function post(): BelongsTo {
        return $this->belongsTo(Post::class);
    }
}

// Usage:
// $post->comments; // Get all comments on post
// $post->comments()->where('approved', true)->get();
// $comment->post; // Get the post this comment belongs to


// --- 3. MANY-TO-MANY ---
class Post extends Model {
    // One post has many tags, one tag has many posts
    public function tags(): BelongsToMany {
        return $this->belongsToMany(Tag::class);
        // Assumes table: post_tag (alphabetically ordered models)
    }
}

class Tag extends Model {
    public function posts(): BelongsToMany {
        return $this->belongsToMany(Post::class);
    }
}

// Usage:
// $post->tags()->attach(1, 2, 3); // Add tags
// $post->tags()->detach([1, 2]); // Remove tags
// $post->tags()->sync([1, 2, 3]); // Replace tags
// $post->tags; // Get all tags


// --- 4. HAS-MANY-THROUGH (Country → Posts through Users) ---
class Country extends Model {
    // A country has many posts through users
    // Country hasMany Users, User hasMany Posts
    public function posts(): HasManyThrough {
        return $this->hasManyThrough(
            Post::class,      // Target model
            User::class,      // Through model
            'country_id',     // Foreign key in users table
            'user_id',        // Foreign key in posts table
            'id',             // Local key in countries
            'id'              // Local key in users
        );
    }
}

class User extends Model {
    public function country(): BelongsTo {
        return $this->belongsTo(Country::class);
    }
    
    public function posts(): HasMany {
        return $this->hasMany(Post::class);
    }
}

class Post extends Model {
    // Access related user
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}

// Usage:
// $country = Country::find(1);
// $country->posts; // Get all posts from users in this country
// $country->posts()->where('published', true)->get();


// --- 5. POLYMORPHIC RELATIONS ---
// One Image can belong to Post, User, or Comment

class Image extends Model {
    // Image morphs to any model
    public function imageable(): MorphTo {
        return $this->morphTo();
    }
}

class Post extends Model {
    // Post has many images
    public function images(): MorphMany {
        return $this->morphMany(Image::class, 'imageable');
    }
}

class User extends Model {
    public function images(): MorphMany {
        return $this->morphMany(Image::class, 'imageable');
    }
}

class Comment extends Model {
    public function images(): MorphMany {
        return $this->morphMany(Image::class, 'imageable');
    }
}

// Database setup:
// images table: id, path, imageable_id, imageable_type, created_at, updated_at
// imageable_type stores: 'App\Models\Post', 'App\Models\User', 'App\Models\Comment'

// Usage:
// $post->images; // All images for post
// $post->images()->create(['path' => 'image.jpg']);
// $image->imageable; // Get the post/user/comment this image belongs to


// ============================================================================
// 2. ADVANCED QUERYING - whereHas(), withCount(), withSum(), etc.
// ============================================================================

class QueryingExamples {
    public function examples() {
        // whereHas() - Filter models by relationship condition
        $posts = Post::whereHas('comments', function($query) {
            $query->where('approved', true);
        })->get();
        // Only posts that have at least one approved comment

        // doesntHave() - Opposite of has()
        $postsWithoutComments = Post::doesntHave('comments')->get();
        // Only posts with no comments at all

        // whereDoesntHave() - Complex inverse conditions
        $postsWithoutSpam = Post::whereDoesntHave('comments', function($query) {
            $query->where('spam', true);
        })->get();
        // Posts without any spam comments

        // with() - Eager loading (prevent N+1)
        $posts = Post::with('author', 'comments', 'comments.author')->get();
        // Load posts with all their comments and comment authors in one query

        // withCount() - Get count without loading all records
        $posts = Post::withCount('comments')->get();
        // Access: $post->comments_count

        // withSum() - Get sum of a column
        $posts = Post::withSum('comments', 'likes')->get();
        // Access: $post->comments_sum_likes

        // withAvg(), withMin(), withMax()
        $posts = Post::withAvg('comments', 'rating')
                     ->withMin('comments', 'created_at')
                     ->withMax('comments', 'updated_at')
                     ->get();

        // Counting related models
        $userCount = Post::find(1)->comments()->count();
        // vs
        $userCount = Post::withCount('comments')->find(1)->comments_count;
        // Second is more efficient if counting multiple posts

        // Chaining conditions with relationships
        $posts = Post::with('comments')
                    ->whereHas('author', function($query) {
                        $query->where('status', 'active');
                    })
                    ->whereHas('comments', function($query) {
                        $query->where('approved', true)
                              ->where('created_at', '>', now()->subDays(7));
                    })
                    ->get();
    }
}


// ============================================================================
// 3. PIVOT TABLES - Many-To-Many Advanced
// ============================================================================

class PivotTableExamples {
    public function examples() {
        $user = User::find(1);

        // Define pivot columns in relationship
        class User extends Model {
            public function roles(): BelongsToMany {
                return $this->belongsToMany(Role::class)
                           ->withPivot('assigned_at', 'expires_at');
            }
        }

        // withPivot() - Include extra columns from pivot table
        $roles = $user->roles()->withPivot('assigned_at', 'expires_at')->get();
        // Access: $role->pivot->assigned_at

        // wherePivot() - Filter by pivot column
        $activeRoles = $user->roles()
                           ->wherePivot('expires_at', '>', now())
                           ->get();

        // Getting pivot attributes
        $pivot = $role->pivot;
        $assignedAt = $role->pivot->assigned_at;
        $expiresAt = $role->pivot->expires_at;

        // Updating pivot data
        $user->roles()->updateExistingPivot(1, ['expires_at' => now()->addMonth()]);

        // sync() - Replace all relationships
        $user->roles()->sync([1, 2, 3]); // Keep only roles 1, 2, 3

        // sync with pivot data
        $user->roles()->sync([
            1 => ['assigned_at' => now(), 'expires_at' => now()->addMonth()],
            2 => ['assigned_at' => now()],
            3,
        ]);

        // attach() - Add relationships
        $user->roles()->attach(4, ['assigned_at' => now()]);
        $user->roles()->attach([4, 5, 6]);
        $user->roles()->attach([
            4 => ['assigned_at' => now()],
            5 => ['assigned_at' => now()],
        ]);

        // detach() - Remove relationships
        $user->roles()->detach(4);
        $user->roles()->detach([4, 5]);
        $user->roles()->detach(); // Remove all

        // toggle() - Attach if missing, detach if exists
        $user->roles()->toggle([1, 2, 3]);

        // Querying through pivot
        $user->roles()
             ->wherePivot('assigned_at', '>', now()->subMonth())
             ->orderByPivot('assigned_at', 'desc')
             ->get();
    }
}


// ============================================================================
// 4. ACCESSORS & MUTATORS - Data Transformation
// ============================================================================

// NEW SYNTAX (PHP 8 Attributes) - RECOMMENDED
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Model {
    // Accessor - Transform data when retrieving
    #[Attribute]
    protected function firstName(): Attribute {
        return Attribute::make(
            get: fn($value) => ucfirst($value),
        );
    }

    // Mutator - Transform data when saving
    #[Attribute]
    protected function email(): Attribute {
        return Attribute::make(
            set: fn($value) => strtolower($value),
        );
    }

    // Computed property - Computed on the fly
    #[Attribute]
    protected function fullName(): Attribute {
        return Attribute::make(
            get: fn() => $this->first_name . ' ' . $this->last_name,
        );
    }

    // Hash password before saving
    #[Attribute]
    protected function password(): Attribute {
        return Attribute::make(
            set: fn($value) => Hash::make($value),
        );
    }

    // Format dates
    #[Attribute]
    protected function birthDate(): Attribute {
        return Attribute::make(
            get: fn($value) => Carbon::parse($value)->format('Y-m-d'),
            set: fn($value) => Carbon::parse($value)->format('Y-m-d H:i:s'),
        );
    }

    // Format currency
    #[Attribute]
    protected function accountBalance(): Attribute {
        return Attribute::make(
            get: fn($value) => '$' . number_format($value / 100, 2),
            set: fn($value) => (int) str_replace('$', '', $value) * 100,
        );
    }
}

// Usage:
// $user = User::create([
//     'first_name' => 'john',
//     'email' => 'JOHN@EXAMPLE.COM',
//     'password' => 'password123',
// ]);
// 
// echo $user->first_name;   // "John" (accessors transform output)
// echo $user->email;         // "john@example.com" (mutator transformed input)
// echo $user->full_name;     // "John Doe" (computed property)

// OLD SYNTAX (still works but deprecated)
class UserLegacy extends Model {
    public function getFirstNameAttribute($value) {
        return ucfirst($value);
    }

    public function setEmailAttribute($value) {
        $this->attributes['email'] = strtolower($value);
    }
}


// ============================================================================
// 5. MODEL EVENTS - Lifecycle Hooks
// ============================================================================

class Post extends Model {
    // Implement business logic using events
    protected static function booted() {
        // Before insert
        static::creating(function ($model) {
            $model->slug = Str::slug($model->title);
            $model->excerpt = Str::limit($model->content, 100);
            Log::info("About to create post: {$model->title}");
        });

        // After insert
        static::created(function ($model) {
            Log::info("Post created with ID: {$model->id}");
            // Trigger notifications, webhooks, etc.
            event(new PostCreated($model));
        });

        // Before update
        static::updating(function ($model) {
            if ($model->isDirty('title')) {
                $model->slug = Str::slug($model->title);
            }
            $model->updated_by = auth()->id();
        });

        // After update
        static::updated(function ($model) {
            Log::info("Post {$model->id} was updated");
            Cache::forget("post_{$model->id}");
        });

        // Before delete
        static::deleting(function ($model) {
            // Don't let admin delete featured posts
            if ($model->is_featured) {
                throw new Exception("Cannot delete featured posts");
            }
            Log::warning("Deleting post: {$model->id}");
        });

        // After delete
        static::deleted(function ($model) {
            // Cleanup: delete associated media, logs, etc.
            $model->images()->delete();
            Log::info("Post {$model->id} deleted");
        });

        // Before any write (create or update)
        static::saving(function ($model) {
            $model->markdown = MarkdownConverter::toHtml($model->markdown);
        });

        // After any write (create or update)
        static::saved(function ($model) {
            Cache::put("post_{$model->id}", $model, now()->addHours(24));
        });

        // Soft delete events
        static::restoring(function ($model) {
            Log::info("About to restore post: {$model->id}");
        });

        static::restored(function ($model) {
            Log::info("Post {$model->id} was restored");
        });
    }
}

// Event available:
// - retrieved: After model fetched from database
// - creating/created
// - updating/updated
// - saving/saved
// - deleting/deleted
// - restoring/restored
// - replicating


// ============================================================================
// 6. OBSERVERS - Extracting Event Logic
// ============================================================================

// Define observer class
namespace App\Observers;

class PostObserver {
    public function creating(Post $post): void {
        $post->slug = Str::slug($post->title);
        $post->excerpt = Str::limit($post->content, 100);
    }

    public function created(Post $post): void {
        Log::info("Post created: {$post->id}");
    }

    public function updating(Post $post): void {
        if ($post->isDirty('title')) {
            $post->slug = Str::slug($post->title);
        }
    }

    public function updated(Post $post): void {
        Cache::forget("post_{$post->id}");
    }

    public function deleting(Post $post): void {
        if ($post->is_featured) {
            throw new Exception("Cannot delete featured posts");
        }
    }

    public function deleted(Post $post): void {
        $post->images()->delete();
    }

    public function restoring(Post $post): void {
        Log::info("Restoring post: {$post->id}");
    }

    public function restored(Post $post): void {
        Log::info("Post restored: {$post->id}");
    }
}

// Register in EventServiceProvider or AppServiceProvider
namespace App\Providers;

class AppServiceProvider extends ServiceProvider {
    public function boot() {
        Post::observe(PostObserver::class);
        // OR observe multiple
        // Post::observe([PostObserver::class, AuditObserver::class]);
    }
}

// Benefits of Observers:
// - Keep models clean
// - Reuse across projects
// - Easier to test
// - Single responsibility


// ============================================================================
// 7. SOFT DELETES
// ============================================================================

use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model {
    use SoftDeletes;

    protected $dates = ['deleted_at'];
}

// Migration:
// Schema::create('posts', function (Blueprint $table) {
//     $table->id();
//     $table->string('title');
//     $table->text('content');
//     $table->softDeletes(); // Adds deleted_at column
//     $table->timestamps();
// });

class SoftDeleteExamples {
    public function examples() {
        $post = Post::find(1);

        // Soft delete (marked deleted, not removed)
        $post->delete();
        // Or
        $post->delete(); // Sets deleted_at to now()

        // Query excludes soft deleted by default
        $posts = Post::all(); // Doesn't include deleted

        // Include soft deleted in query
        $posts = Post::withTrashed()->get(); // Includes deleted

        // Only soft deleted
        $posts = Post::onlyTrashed()->get();

        // Check if soft deleted
        if ($post->trashed()) {
            echo "This post is soft deleted";
        }

        // Restore soft deleted
        $post->restore();
        // Or
        Post::onlyTrashed()->restore();

        // Permanently delete
        $post->forceDelete();
        // Or
        Post::onlyTrashed()->forceDelete();

        // Force delete even if not soft-deleted
        Post::find(1)->forceDelete();

        // Relationships respect soft deletes
        $user->posts()->get(); // Doesn't include deleted posts
        $user->posts()->withTrashed()->get(); // Includes deleted
    }
}

// Use cases:
// - Audit trails
// - User recovery features
// - Prevent accidents
// - GDPR compliance


// ============================================================================
// 8. SCOPES - Query Reusability
// ============================================================================

class Post extends Model {
    // --- LOCAL SCOPES ---
    // Instance methods for optional filtering

    public function scopeActive($query) {
        return $query->where('status', 'active');
    }

    public function scopeRecent($query) {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeByAuthor($query, $authorId) {
        return $query->where('author_id', $authorId);
    }

    public function scopePopular($query) {
        return $query->where('views', '>', 1000);
    }

    public function scopePublished($query) {
        return $query->where('published_at', '<=', now());
    }

    public function scopeSearch($query, $term) {
        return $query->where('title', 'like', "%{$term}%")
                    ->orWhere('content', 'like', "%{$term}%");
    }
}

// Usage:
// Post::active()->get();
// Post::active()->recent()->get();
// Post::byAuthor(5)->popular()->get();
// $user->posts()->active()->recent()->get();
// Post::search('laravel')->active()->get();

// --- GLOBAL SCOPES ---
// Automatically applied to every query

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ActiveScope implements Scope {
    public function apply(Builder $builder, Model $model) {
        $builder->where('status', 'active');
    }
}

// Apply in model
class Post extends Model {
    protected static function booted() {
        static::addGlobalScope(new ActiveScope);
        // or
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('status', 'active');
        });
    }
}

// Using global scopes
$posts = Post::all(); // Only active posts
$posts = Post::withoutGlobalScopes()->get(); // All posts
$posts = Post::withoutGlobalScope(ActiveScope::class)->get(); // All including inactive
$posts = Post::withoutGlobalScope('active')->get(); // Using string key

// Global scopes are automatically applied like soft deletes!

// LOCAL vs GLOBAL SCOPES:
// LOCAL:  Optional, called explicitly
// GLOBAL: Always applied unless explicitly removed
// Use LOCAL for: filters, search, sorting
// Use GLOBAL for: default behaviors like soft deletes, tenant isolation


// ============================================================================
// 9. ELOQUENT vs QUERY BUILDER - When to Use Each
// ============================================================================

class EloquentVsQueryBuilder {
    public function examples() {
        // --- QUERY BUILDER (When to use) ---
        // - One-off queries without models
        // - Complex joins and aggregations
        // - Raw SQL needed
        // - Slightly better performance

        $posts = DB::table('posts')
                  ->where('status', 'active')
                  ->join('users', 'posts.user_id', '=', 'users.id')
                  ->select('posts.id', 'posts.title', 'users.name')
                  ->where('users.role', 'admin')
                  ->orderBy('posts.created_at', 'desc')
                  ->paginate();

        // --- ELOQUENT (When to use) ---
        // - Working with models
        // - Model relationships
        // - Model events and observers
        // - Accessors/mutators
        // - Better readability

        $posts = Post::where('status', 'active')
                    ->with('author') // Eager load
                    ->whereHas('author', function ($query) {
                        $query->where('role', 'admin');
                    })
                    ->latest('created_at')
                    ->paginate();

        // --- PERFORMANCE COMPARISON ---
        // Query Builder: SELECT * FROM posts WHERE status = 'active' LIMIT 15;
        // Eloquent: (same SQL, just wrapped in Model)
        // Overhead is minimal in modern Laravel

        // --- N+1 PROBLEM ---
        // BAD: Eloquent (N+1 queries)
        $posts = Post::all();
        foreach ($posts as $post) {
            echo $post->author->name; // 1 + N queries
        }

        // GOOD: Eloquent (1 query)
        $posts = Post::with('author')->get();
        foreach ($posts as $post) {
            echo $post->author->name; // 2 queries total
        }

        // --- MIXED APPROACH (Best of both) ---
        // Eloquent for relationships and business logic
        // Query Builder for complex queries
        $posts = Post::select('id', 'title', 'author_id')
                    ->from('posts')
                    ->join('users', 'posts.user_id', '=', 'users.id')
                    ->where('users.status', 'active')
                    ->with('author')
                    ->get()
                    ->map(function ($post) {
                        return [
                            'id' => $post->id,
                            'title' => $post->title,
                            'author' => $post->author->name,
                            'full_name' => $post->author->full_name, // Accessor
                        ];
                    });

        // GETTING QUERY BUILDER FROM ELOQUENT
        $query = Post::query(); // Eloquent query builder
        $query->where('status', 'active');
        $posts = $query->get(); // Get as models

        // Drop to raw query builder
        $query = Post::toBase(); // Returns Query Builder
        $raw = Post::query()->toBase();
    }
}

// SUMMARY:
// - Eloquent for: models, relationships, events, accessors
// - Query Builder for: complex queries, aggregations, raw SQL
// - Use Eloquent by default, Query Builder when needed
// - Performance difference is negligible in most cases
// - Focus on N+1 prevention over ORM choice

