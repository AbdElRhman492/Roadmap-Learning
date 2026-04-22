# 02 - Eloquent ORM - Advanced

## Understanding

Eloquent ORM is Laravel's powerful object-relational mapping layer that allows you to interact with your database using expressive PHP syntax. Advanced Eloquent goes beyond basic CRUD operations to include sophisticated relationship handling, data transformation, model events, and query optimization techniques.

**Why Advanced Eloquent Matters:**

- Build complex database relationships with minimal code
- Filter and aggregate related data efficiently
- Transform model data automatically
- Execute logic at specific model lifecycle points
- Keep your application DRY and maintainable

---

## Key Concepts

### 1. ALL 6 RELATIONSHIP TYPES

#### **1. One-To-One**

- One model belongs to exactly one other model
- Example: User hasOne Profile
- Use when one record maps to one related record
- Foreign key stored in related table

#### **2. One-To-Many**

- One model has many related models
- Example: User hasMany Posts
- Foreign key stored in related table (posts table has user_id)
- Most common relationship type

#### **3. Many-To-Many**

- Two models can have many of each other
- Example: Post hasMany Tags, Tag hasMany Posts
- Requires pivot table (posts_tags)
- Access pivot data with `$post->tags()->withPivot('...')->get()`

#### **4. Has-Many-Through**

- Access related models through intermediate model
- Example: Country hasMany Posts through Users
- Syntax: `$country->posts()` even though direct link doesn't exist
- Chain multiple relationships without explicit joins

#### **5. Polymorphic Relations**

- One model can belong to multiple other models
- Example: Image morphTo (belongs to Post OR User OR Comment)
- Uses `imageable_type` and `imageable_id` columns
- Single table handles multiple entity types

#### **6. Many-To-Many Polymorphic**

- Multiple models can have many of the same related model
- Example: Post & Video both have many Tags
- Most complex but powerful relationship pattern
- Requires pivot table with morphable columns

---

### 2. QUERYING RELATIONSHIPS - whereHas() & whereMorphTo()

**whereHas()**: Filter models based on relationship conditions

```
$posts = Post::whereHas('comments', function($query) {
    $query->where('approved', true);
})->get();
```

**doesntHave()**: Inverse of whereHas

```
$posts = Post::doesntHave('comments')->get(); // Posts with no comments
```

**whereDoesntHave()**: Complex inverse conditions

```
$posts = Post::whereDoesntHave('comments', function($query) {
    $query->where('spam', true);
})->get();
```

**with()**: Eager loading to prevent N+1 queries

```
$posts = Post::with('author', 'comments')->get();
```

**withCount()**: Get count without loading all records

```
$posts = Post::withCount('comments')->get();
// Access: $post->comments_count
```

**withSum(), withAvg(), withMin(), withMax()**

```
$posts = Post::withSum('views', 'count')->get();
// Access: $post->views_sum_count
```

---

### 3. PIVOT TABLES - Many-To-Many Data

**withPivot()**: Include pivot columns in result

```
$user->posts()->withPivot('role', 'created_at')->get();
// Access: $post->pivot->role
```

**wherePivot()**: Filter by pivot column

```
$user->posts()->wherePivot('role', 'editor')->get();
```

**sync()**: Replace all pivot records

```
$user->roles()->sync([1, 2, 3]);
// OR with extra data
$user->roles()->sync([1 => ['active' => true], 2, 3]);
```

**attach() & detach()**

```
$user->roles()->attach(1);
$user->roles()->detach([1, 2]);
```

**toggle()**: Attach if detached, detach if attached

```
$user->roles()->toggle([1, 2]);
```

---

### 4. ACCESSORS & MUTATORS - Data Transformation

**Old Syntax (getter/setter methods)**

```
public function getFullNameAttribute() {
    return $this->first_name . ' ' . $this->last_name;
}

public function setPasswordAttribute($value) {
    $this->attributes['password'] = Hash::make($value);
}
```

**New Syntax (PHP 8 Attributes) - RECOMMENDED**

```
#[Attribute]
protected function name(): Attribute {
    return Attribute::make(
        get: fn($value) => ucfirst($value),
        set: fn($value) => strtolower($value),
    );
}
```

**Use Cases:**

- Format dates, numbers, currency
- Hash passwords before saving
- Compute calculated fields
- Transform data for API responses

---

### 5. MODEL EVENTS - Lifecycle Hooks

**Available Events:**

- `creating` & `created` - Before/after insert
- `updating` & `updated` - Before/after update
- `deleting` & `deleted` - Before/after delete
- `restoring` & `restored` - Before/after restore (soft deletes)
- `saving` & `saved` - Before/after any write operation

**In Model:**

```
protected static function booted() {
    static::creating(function ($model) {
        $model->slug = Str::slug($model->title);
    });

    static::deleting(function ($model) {
        Log::info("Deleting: {$model->id}");
    });
}
```

**Priority Tips:**

- Use `creating` to set defaults
- Use `saving` for operations needed on both create & update
- Use `deleting` to cascade delete related records
- Never query database in events (causes N+1 problems)

---

### 6. OBSERVERS - Extracting Event Logic

Extract event logic into separate class for cleanliness

```
// Register in EventServiceProvider
protected $observers = [
    Post::class => [PostObserver::class],
];

// Then in PostObserver class
public function creating(Post $post) { }
public function created(Post $post) { }
public function updating(Post $post) { }
// etc...
```

**Benefits:**

- Single Responsibility Principle
- Reusable across projects
- Easier to test
- More readable models

---

### 7. SOFT DELETES - "Soft" Deletion

**Instead of permanent deletion, just mark as deleted**

Migration:

```
$table->softDeletes(); // Adds deleted_at column
```

Model:

```
use SoftDeletes;

protected $dates = ['deleted_at'];
```

**Queries include soft-deleted by default:**

```
Post::all(); // Doesn't include soft deleted
Post::withTrashed()->get(); // Includes soft deleted
Post::onlyTrashed()->get(); // Only soft deleted
Post::restore(); // Restore all soft deleted
$post->restore(); // Restore single
$post->forceDelete(); // Permanent delete
```

**Use Cases:**

- Audit trail / historical data
- User recovery features
- Preventing accidental permanent deletion
- Data compliance (GDPR retention)

---

### 8. SCOPES - Query Reusability

**Local Scopes** (instance method)

```
class Post extends Model {
    public function scopeActive($query) {
        return $query->where('status', 'active');
    }

    // Usage
    Post::active()->get();
    $user->posts()->active()->get();
}
```

**Global Scopes** (automatic in every query)

```
class Post extends Model {
    protected static function booted() {
        static::addGlobalScope(new ActiveScope);
    }
}

class ActiveScope implements Scope {
    public function apply(Builder $builder, Model $model) {
        $builder->where('status', 'active');
    }
}

// You can bypass with
Post::withoutGlobalScopes()->get();
Post::withoutGlobalScope(ActiveScope::class)->get();
```

**Local Scopes vs Global Scopes:**

- Use **Local** for optional filters
- Use **Global** for default filtering (like soft deletes)
- Global scopes always apply (unless explicitly removed)

---

### 9. ELOQUENT VS QUERY BUILDER - When to Use Each

**Use Query Builder When:**

- One-off queries without models
- Complex joins and aggregations
- No need for model features (events, accessors, etc.)
- Raw SQL needed in specific parts

```
DB::table('posts')->where('status', 'active')->paginate();
```

**Use Eloquent When:**

- Working with models and relationships
- Need model events and observers
- Using accessors/mutators
- Better readability and maintainability
- Using polymorphic relationships

```
Post::active()->with('author')->paginate();
```

**Performance Considerations:**

- Query Builder is slightly faster (fewer abstractions)
- Eloquent's overhead is minimal in most cases
- N+1 prevention (eager loading) matters more than ORM choice
- Use `->toBase()` to drop to Query Builder when needed

**Mixed Approach (Best):**

```
Post::select('id', 'title')
    ->from('posts')
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->where('users.status', 'active')
    ->get();
```

---

## Code Examples

See `code-examples.php` for practical implementations of all concepts.

---

## Resources

- Laravel Eloquent Documentation: https://laravel.com/docs/eloquent-relationships
- Laravel Eloquent API: https://laravel.com/docs/eloquent
- Query Optimization: https://laravel.com/docs/database#performance-considerations
- Model Events: https://laravel.com/docs/eloquent#events

---

## Checkpoint

**Mastery Checklist:**

- [ ] Implemented all 6 relationship types in a project
- [ ] Used whereHas() to filter by complex conditions
- [ ] Created pivot table with custom columns
- [ ] Built custom accessors/mutators with new Attribute syntax
- [ ] Set up model events for business logic
- [ ] Extracted logic into an Observer class
- [ ] Implemented soft deletes with proper restoration
- [ ] Created reusable local and global scopes
- [ ] Chose Eloquent vs Query Builder appropriately
- [ ] Prevented N+1 problems with eager loading and withCount()
- [ ] Built a complete relationship structure (3+ models with relationships)
- [ ] Tested model events and observers
