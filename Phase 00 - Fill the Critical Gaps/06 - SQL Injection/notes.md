# 06 - SQL Injection - Complete Protection Mastery

## Understanding SQL Injection

### What is SQL Injection?

SQL Injection is an attack where malicious SQL code is inserted into an application's query through user input. The attacker's input is interpreted as SQL code instead of data, allowing them to manipulate the database.

### Why This is Critical

- **Direct database access** - Bypasses all application logic
- **Data breach** - Steal sensitive information
- **Data destruction** - Delete or modify records
- **Authentication bypass** - Login without credentials
- **Privilege escalation** - Gain admin access
- **Remote code execution** - Execute system commands
- **Estimated impact** - One of OWASP Top 10 vulnerabilities

---

## How SQL Injection Attacks Work

### The Basic Attack Scenario

**Vulnerable Login Form:**

```php
// ❌ VULNERABLE CODE
$username = $_POST['username'];
$password = $_POST['password'];

$query = "SELECT * FROM users WHERE username = '" . $username . "' AND password = '" . $password . "'";
$result = mysqli_query($connection, $query);
```

**Normal use:**

```
Username: john
Password: secret123

Query: SELECT * FROM users WHERE username = 'john' AND password = 'secret123'
Result: Returns john's user record if credentials match ✓
```

**Attacker's input:**

```
Username: ' OR '1'='1
Password: anything

Query: SELECT * FROM users WHERE username = '' OR '1'='1' AND password = 'anything'
Result: Returns ALL users because '1'='1' is always true! ✗
Attacker can login as first user (often admin)!
```

### How The Attack Works

1. **Attacker enters malicious input** as username
2. **Input concatenated into query** without escaping
3. **Single quote closes the string** prematurely
4. **OR '1'='1'** added as condition (always true)
5. **Server executes modified query** as-is
6. **Query logic subverted** - security bypassed
7. **Authentication bypassed** - attacker logged in

### Attack Variations

**Union-based SQL Injection:**

```sql
-- Attacker input:
' UNION SELECT username, password, email FROM admin_users --

-- Resulting query:
SELECT id, name FROM users WHERE id = '1'
UNION SELECT username, password, email FROM admin_users --'

-- Returns admin credentials!
```

**Time-based Blind SQL Injection:**

```sql
-- Attacker input:
' OR (SELECT SLEEP(5)) --

-- If query takes 5 seconds, attacker knows condition is true
-- Uses timing to determine database structure and values
```

**Boolean-based Blind SQL Injection:**

```sql
-- Attacker input:
' OR '1'='1

-- Attacker checks: Does response differ when condition true vs false?
-- Uses differences to extract data byte-by-byte
```

**Stacked Queries (Some databases):**

```sql
-- Attacker input:
'; DROP TABLE users; --

-- Executes multiple queries:
-- Query 1: Original query
-- Query 2: DROP TABLE users (deletes all users!)
```

---

## PDO Prepared Statements Explained

### What are Prepared Statements?

Prepared statements are a method of safely executing SQL queries where:

1. SQL structure is sent to database first
2. Parameters are sent separately as data
3. Database knows exactly what is code and what is data
4. No mixing of code and data possible

### How They Work

**Structure:**

```php
// 1. Prepare the statement with placeholders
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");

// 2. Execute with parameters separately
$stmt->execute([$username, $password]);

// 3. Database receives:
// - SQL structure: SELECT * FROM users WHERE username = ? AND password = ?
// - Data: ['john', 'secret123']
// Database knows ? are data placeholders, NEVER code
```

**Why It's Safe:**

```php
// Even if attacker enters: ' OR '1'='1
// Parameter remains as STRING data

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute(["' OR '1'='1"]);

// Database treats entire input as username value
// Query becomes:
// SELECT * FROM users WHERE username = '\' OR \'1\'=\'1'
// No SQL injection possible!
```

### Named Placeholders (More Readable)

```php
// Using named placeholders
$stmt = $pdo->prepare("
    SELECT * FROM users
    WHERE username = :username
    AND password = :password
");

$stmt->execute([
    ':username' => $username,
    ':password' => $password
]);

// More readable and less error-prone
```

### Positional Placeholders (Simpler)

```php
// Using ? placeholders (must match order)
$stmt = $pdo->prepare("
    SELECT * FROM users
    WHERE username = ?
    AND password = ?
");

$stmt->execute([$username, $password]);

// Parameters must be in same order as ?
```

### Preventing Type Confusion

```php
// Specify parameter type for extra safety
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([123], PDO::PARAM_INT);  // Enforces integer type

// Or with explicit type binding:
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bindParam(1, $id, PDO::PARAM_INT);
$stmt->execute();
```

---

## Why Eloquent & Query Builder Are Safe

### How Eloquent Handles Queries

**Eloquent automatically uses prepared statements:**

```php
// All of these use prepared statements internally
User::where('username', $username)->first();

// Equivalent to:
// SELECT * FROM users WHERE username = ? (parameter: $username)

User::where('email', 'like', '%@example.com%')->get();
// SELECT * FROM users WHERE email LIKE ? (parameter: '%@example.com%')

User::whereIn('id', [1, 2, 3])->get();
// SELECT * FROM users WHERE id IN (?, ?, ?)
```

**Why it's safe:**

1. **Always prepared** - Every where() uses prepared statements
2. **No string concatenation** - Never builds SQL strings
3. **Type handling** - Automatically handles parameter types
4. **Escaping** - Database driver handles escaping
5. **Validation** - Built-in validation options

### Query Builder Examples

```php
// All SAFE - use prepared statements

// Simple where
$users = DB::table('users')
    ->where('status', 'active')
    ->get();

// Multiple where conditions
$users = DB::table('users')
    ->where('status', 'active')
    ->where('role', 'admin')
    ->get();

// whereIn
$users = User::whereIn('id', $ids)->get();

// whereBetween
$users = User::whereBetween('created_at', [$start, $end])->get();

// whereNull
$users = User::whereNull('deleted_at')->get();

// All safe! No SQL injection possible!
```

### Chaining Multiple Conditions

```php
// Safe - Query Builder handles all parameters
$users = User::where('age', '>', 18)
    ->where('country', 'US')
    ->where('verified', true)
    ->orderBy('created_at', 'desc')
    ->paginate(15);

// All variables are parameterized, never concatenated
```

---

## Dangerous Patterns: Raw DB::select with Variables

### ❌ THE MOST DANGEROUS PATTERN

```php
// ❌ EXTREMELY DANGEROUS - DO NOT USE
$username = $_POST['username'];
$password = $_POST['password'];

$users = DB::select("SELECT * FROM users WHERE username = '$username' AND password = '$password'");
// Attacker can: bypass authentication, steal all data, delete everything!
```

**Why dangerous:**

- Variables directly concatenated into SQL
- No prepared statement
- User input goes straight to database
- Zero protection against injection

### ❌ Raw With Some Protection (Still Risky)

```php
// ❌ PARTIALLY DANGEROUS - String concatenation risks
$query = "SELECT * FROM users WHERE username = '" . $username . "'";
$users = DB::select($query);

// Better than above, but still risky if $username comes from untrusted source
```

### ✅ SAFE: Raw With Prepared Statements

```php
// ✅ SAFE - Using prepared statement
$users = DB::select("SELECT * FROM users WHERE username = ?", [$username]);

// OR with named parameters:
$users = DB::select("SELECT * FROM users WHERE username = :username", ['username' => $username]);

// Parameters are bound separately, not concatenated
```

### When Raw Queries Are Necessary

```php
// Complex queries that Query Builder can't handle

// ✅ SAFE - Complex query with proper parameterization
$results = DB::select("
    SELECT u.id, u.username, COUNT(p.id) as post_count
    FROM users u
    LEFT JOIN posts p ON u.id = p.user_id
    WHERE u.created_at > ?
    AND u.status = ?
    GROUP BY u.id
    HAVING post_count > ?
    ORDER BY post_count DESC
", [$startDate, 'active', 10]);

// All parameters bound safely
```

---

## DB::raw() - When Safe and When Not

### What is DB::raw()?

DB::raw() tells Laravel to insert raw SQL without escaping. It's a "trust me" function that bypasses all protections.

### ❌ DANGEROUS: With User Input

```php
// ❌ DANGEROUS - NEVER DO THIS
$sortBy = $_GET['sort'];
$users = User::orderBy(DB::raw($sortBy))->get();

// If attacker sends: sort=CASE WHEN (1=1) THEN id ELSE username END
// Executes as code, not data!

// ❌ DANGEROUS - String concatenation
$query = "id = " . $id;
User::whereRaw(DB::raw($query))->get();

// If $id = "1 OR 1=1", becomes: WHERE id = 1 OR 1=1
// Returns all users!
```

### ✅ SAFE: With Hardcoded Values Only

```php
// ✅ SAFE - Column names or hardcoded functions
User::orderBy(DB::raw('RAND()'))->get();

// ✅ SAFE - Hardcoded database functions
User::select('name', DB::raw('COUNT(*) as total'))
    ->groupBy('name')
    ->get();

// ✅ SAFE - System columns
User::whereRaw('YEAR(created_at) = 2024')->get();

// ✅ SAFE - Prepared statements with DB::raw()
User::whereRaw('DATEDIFF(?, created_at) < 30', [now()])->get();
```

### Best Practices for DB::raw()

```php
// Always parameterize anything that comes from user input

// ❌ BAD
$column = $_GET['column'];
User::select(DB::raw($column))->get();

// ✅ GOOD - Don't allow user to choose columns
User::select('id', 'name', 'email')->get();

// ✅ GOOD - Whitelist allowed columns
$allowedColumns = ['id', 'name', 'email'];
$column = $_GET['column'] ?? 'id';
if (!in_array($column, $allowedColumns)) {
    abort(400);  // Invalid column
}
User::select($column)->get();

// ✅ GOOD - Use parameterized raw
$userId = $_GET['id'];
User::whereRaw('id = ?', [$userId])->get();
```

### DB::raw() with Parameters

```php
// Safe way: use ? placeholders
User::whereRaw('YEAR(created_at) = ?', [2024])->get();

// Safe way: use named parameters
User::whereRaw('YEAR(created_at) = :year', ['year' => 2024])->get();

// Safe way: use static methods
User::whereRaw('DATEDIFF(?, created_at) < 30', [now()])->get();
```

---

## Mass Assignment Vulnerability: $fillable vs $guarded

### What is Mass Assignment?

Mass assignment is when you assign multiple fields from user input at once:

```php
// Mass assignment
$user = User::create($request->all());

// Equivalent to:
// $user = User::create([
//     'name' => $request->input('name'),
//     'email' => $request->input('email'),
//     'password' => $request->input('password'),
//     'role' => $request->input('role'),  // Attacker sets this!
//     'is_admin' => $request->input('is_admin')  // Attacker sets this too!
// ]);
```

### ❌ THE VULNERABILITY

```php
// Attacker sends form with extra fields
POST /user/register
name=John&email=john@example.com&password=secret&role=admin&is_admin=true

// If User model doesn't protect, all fields get set!
// Attacker becomes admin!
```

### ✅ PROTECTION: Using $fillable (Whitelist)

```php
// app/Models/User.php
class User extends Model
{
    // Only these fields can be mass-assigned
    protected $fillable = [
        'name',
        'email',
        'password'
    ];
}

// Now attacker can't set 'role' or 'is_admin' through mass assignment
$user = User::create($request->all());
// 'role' and 'is_admin' remain unchanged (safe!)
```

### ✅ PROTECTION: Using $guarded (Blacklist)

```php
// app/Models/User.php
class User extends Model
{
    // These fields CANNOT be mass-assigned
    protected $guarded = [
        'id',
        'role',
        'is_admin',
        'created_at',
        'updated_at'
    ];
}

// Attacker can't set guarded fields
// But any new field they add WILL be assignable (less safe)
```

### Fillable vs Guarded Comparison

| Approach          | Method    | Safety    | Default          |
| ----------------- | --------- | --------- | ---------------- |
| **$fillable**     | Whitelist | Safer     | Recommended      |
| **$guarded**      | Blacklist | Less Safe | Use if necessary |
| **No protection** | None      | Dangerous | Don't use!       |

### Best Practice: Always Use $fillable

```php
// Best practice - explicit whitelist
class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id'
    ];

    // Set other fields manually
    public function update(Request $request)
    {
        $product = Product::findOrFail($request->id);

        // Only allow mass assignment for fillable fields
        $product->update($request->only('name', 'description', 'price'));

        // Set sensitive fields explicitly
        if ($request->user()->isAdmin()) {
            $product->category_id = $request->category_id;
            $product->save();
        }

        return $product;
    }
}
```

### Using only() and except()

```php
// Explicitly control what gets assigned

// Only allow specific fields
$user = User::create($request->only('name', 'email', 'password'));

// Allow all except these
$user = User::create($request->except('role', 'is_admin'));

// Even safer: validate then create
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'password' => 'required|min:8'
]);
$user = User::create($validated);  // Only validated fields
```

---

## Testing for SQL Injection Vulnerabilities

### Manual Testing Patterns

```php
// Test 1: Single quote
' OR '1'='1

// Test 2: Double quote
" OR "1"="1

// Test 3: Boolean blind
' OR 1=1 --

// Test 4: Comment bypass
'; DROP TABLE users; --

// Test 5: Union-based
' UNION SELECT username, password FROM admin --

// Test 6: Time-based
' OR SLEEP(5) --

// If application hangs for 5 seconds, vulnerable!
```

### Unit Testing for SQL Injection

```php
public function testSqlInjectionProtection()
{
    // Test that malicious input is parameterized, not executed

    $maliciousInput = "' OR '1'='1";

    // This should not return all users, only match exact username
    $users = User::where('username', $maliciousInput)->get();

    $this->assertCount(0, $users);  // No matches for that exact string
}

public function testPreparedStatementSafety()
{
    DB::enableQueryLog();

    $username = "'; DROP TABLE users; --";

    // Should be parameterized, not executed
    $user = User::where('username', $username)->first();

    $queries = DB::getQueryLog();

    // Verify it's a parameterized query
    $this->assertStringContainsString('?', $queries[0]['query']);

    // Verify table still exists
    $this->assertTrue(Schema::hasTable('users'));
}

public function testMassAssignmentProtection()
{
    $data = [
        'name' => 'John',
        'email' => 'john@example.com',
        'password' => bcrypt('secret'),
        'role' => 'admin',  // Attacker trying to set this
        'is_admin' => true   // And this
    ];

    $user = User::create($data);

    // Check that protected fields weren't set
    $this->assertFalse($user->is_admin);
    $this->assertNotEquals('admin', $user->role);
}
```

### Integration Testing

```php
public function testLoginWithSqlInjection()
{
    $response = $this->post('/login', [
        'username' => "' OR '1'='1",
        'password' => 'anything'
    ]);

    // Should fail authentication, not bypass it
    $response->assertStatus(401);
    $this->assertGuest();
}

public function testSearchWithSqlInjection()
{
    $response = $this->post('/search', [
        'query' => "'; DELETE FROM posts; --"
    ]);

    // Should return no results or safe error
    // Should NOT delete table
    $this->assertTrue(Schema::hasTable('posts'));
}
```

### Tools for Testing

```bash
# SQLMap - automated SQL injection testing
sqlmap -u "http://example.com/user.php?id=1" --dbs

# Burp Suite - intercept and test requests
# OWASP ZAP - security scanning

# In Laravel - Query logging and inspection
DB::enableQueryLog();
// Run your code
$queries = DB::getQueryLog();
dd($queries);  // Check if parameterized
```

---

## Prevention Checklist

### ✅ DO:

- ✅ Use Eloquent or Query Builder
- ✅ Use prepared statements with ?or :parameter
- ✅ Use $fillable to whitelist fields
- ✅ Validate and sanitize input
- ✅ Use Sanctum for API authentication
- ✅ Log suspicious queries
- ✅ Test for SQL injection
- ✅ Keep database software updated

### ❌ DON'T:

- ❌ Concatenate user input into SQL
- ❌ Use DB::select() with variables
- ❌ Trust client-side validation
- ❌ Use DB::raw() with user input
- ❌ Disable prepared statements
- ❌ Use $guarded with sensitive fields
- ❌ Allow dynamic column names from input
- ❌ Skip security testing

---

## Checkpoint Questions

1. How does SQL injection work? Draw the attack flow.
2. What makes prepared statements safe?
3. Why is Eloquent safer than raw queries?
4. When is it safe to use DB::raw()?
5. What's the difference between $fillable and $guarded?
6. How would you test if an application is vulnerable?
7. Can an attacker bypass prepared statements?
8. What are 3 dangerous patterns to avoid?
9. How would you handle complex queries safely?
10. Why is whitelist ($fillable) better than blacklist ($guarded)?

---

## Resources

- [OWASP SQL Injection](https://owasp.org/www-community/attacks/SQL_Injection)
- [Laravel Security - Mass Assignment](https://laravel.com/docs/eloquent#mass-assignment)
- [PDO Prepared Statements](https://www.php.net/manual/en/pdo.prepared-statements.php)
- [Laravel Query Builder](https://laravel.com/docs/queries)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
