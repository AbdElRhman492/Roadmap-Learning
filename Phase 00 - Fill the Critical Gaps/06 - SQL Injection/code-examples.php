<?php

// ============================================================================
// 06 - SQL Injection - Complete Protection Mastery
// ============================================================================

// =============================================================================
// 1. DEMONSTRATING SQL INJECTION ATTACKS
// =============================================================================

/**
 * SQL Injection Attack Scenarios
 */
class SqlInjectionVulnerabilityExamples
{
  /**
   * ❌ VULNERABLE: String concatenation in login
   */
  public function vulnerableLogin()
  {
    $php = <<<'PHP'
        // ❌ EXTREMELY DANGEROUS - DO NOT USE
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        // String concatenation - opens door to injection!
        $query = "SELECT * FROM users WHERE username = '" . $username . "' AND password = '" . $password . "'";
        $result = mysqli_query($connection, $query);
        
        // Attacker sends:
        // Username: ' OR '1'='1
        // Password: anything
        
        // Query becomes:
        // SELECT * FROM users WHERE username = '' OR '1'='1' AND password = 'anything'
        
        // Result: Returns FIRST user (usually admin) because '1'='1' is always true!
        // Attacker logged in as admin without knowing password!
        PHP;

    return $php;
  }

  /**
   * ❌ VULNERABLE: Search functionality
   */
  public function vulnerableSearch()
  {
    $php = <<<'PHP'
        // ❌ DANGEROUS - Direct concatenation
        $search = $_GET['q'];
        $query = "SELECT * FROM products WHERE name LIKE '%" . $search . "%'";
        
        // Attacker sends:
        // ?q=test%' UNION SELECT username, password, email FROM users --
        
        // Query becomes:
        // SELECT * FROM products WHERE name LIKE '%test%' UNION SELECT username, password, email FROM users --'
        
        // Result: Returns product results PLUS all usernames and passwords!
        // Database credentials leaked!
        PHP;

    return $php;
  }

  /**
   * ❌ VULNERABLE: ID parameter
   */
  public function vulnerableById()
  {
    $php = <<<'PHP'
        // ❌ DANGEROUS - Type not enforced
        $id = $_GET['id'];
        $query = "SELECT * FROM users WHERE id = " . $id;
        
        // Normal use:
        // ?id=5 → SELECT * FROM users WHERE id = 5  ✓
        
        // Attacker sends:
        // ?id=5 OR 1=1
        // Query: SELECT * FROM users WHERE id = 5 OR 1=1
        // Result: Returns ALL users!
        
        // Or:
        // ?id=5; DROP TABLE users; --
        // Query: SELECT * FROM users WHERE id = 5; DROP TABLE users; --
        // Result: Table deleted!
        PHP;

    return $php;
  }

  /**
   * ❌ VULNERABLE: Time-based blind injection
   */
  public function vulnerableBlindTiming()
  {
    $php = <<<'PHP'
        // ❌ DANGEROUS - Vulnerable to timing attacks
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        $query = "SELECT * FROM users WHERE username = '" . $username . "' AND password = '" . $password . "'";
        $result = mysqli_query($connection, $query);
        
        // Attacker sends:
        // Username: admin' AND (SELECT SLEEP(5)) --
        // Password: anything
        
        // Query: SELECT * FROM users WHERE username = 'admin' AND (SELECT SLEEP(5)) --' AND password = 'anything'
        
        // Result: Query takes 5 seconds to complete
        // Attacker knows 'admin' user exists by timing!
        // Can extract data byte-by-byte using SLEEP()
        PHP;

    return $php;
  }

  /**
   * Attack impact demonstration
   */
  public function attackImpactFlow()
  {
    return [
      "Authentication Bypass" => [
        "Attack: ' OR '1'='1",
        "Result: Login without credentials",
        "Impact: Attacker becomes admin"
      ],
      "Data Extraction" => [
        "Attack: ' UNION SELECT username, password FROM admin",
        "Result: Database credentials exposed",
        "Impact: Full database compromise"
      ],
      "Data Destruction" => [
        "Attack: '; DROP TABLE users; --",
        "Result: Table deleted",
        "Impact: Data loss, system down"
      ],
      "Data Modification" => [
        "Attack: '; UPDATE users SET role='admin' WHERE id=1; --",
        "Result: User becomes admin",
        "Impact: Privilege escalation"
      ],
      "Remote Code Execution" => [
        "Attack: '; INTO OUTFILE '/var/www/shell.php'",
        "Result: Shell file created on server",
        "Impact: Complete system compromise"
      ]
    ];
  }
}

// =============================================================================
// 2. PDO PREPARED STATEMENTS - THE SAFE WAY
// =============================================================================

/**
 * ✅ Safe prepared statement usage
 */
class PreparedStatementExamples
{
  /**
   * Basic prepared statement with positional placeholders
   */
  public function basicPreparedStatement()
  {
    $php = <<<'PHP'
        // ✅ SAFE - Prepared statement with ? placeholders
        $pdo = new PDO('mysql:host=localhost;dbname=app', 'user', 'password');
        
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        // 1. Prepare statement with placeholders
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
        
        // 2. Execute with parameters as separate array
        $stmt->execute([$username, $password]);
        
        // 3. Fetch results
        $user = $stmt->fetch();
        
        // Why it's safe:
        // - SQL structure sent to database first
        // - Parameters sent separately as DATA
        // - Database knows ? are placeholders, never code
        // - Even if $username = "' OR '1'='1", treated as string data
        PHP;

    return $php;
  }

  /**
   * Named placeholders (more readable)
   */
  public function namedPlaceholders()
  {
    $php = <<<'PHP'
        // ✅ SAFE - Named placeholders (preferred for readability)
        $stmt = $pdo->prepare("
            SELECT * FROM users 
            WHERE username = :username 
            AND password = :password
        ");
        
        $stmt->execute([
            ':username' => $_POST['username'],
            ':password' => $_POST['password']
        ]);
        
        $user = $stmt->fetch();
        
        // More readable than ?
        // Easier to maintain
        // Reusable in complex queries
        PHP;

    return $php;
  }

  /**
   * Binding parameters with type specification
   */
  public function bindWithTypes()
  {
    $php = <<<'PHP'
        // ✅ SAFEST - Explicit type binding
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bindParam(1, $id, PDO::PARAM_INT);  // Force integer type
        
        $id = $_GET['id'];
        $stmt->execute();
        $user = $stmt->fetch();
        
        // Or:
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? AND status = ?");
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $status, PDO::PARAM_STR);
        
        $userId = 5;
        $status = 'published';
        $stmt->execute();
        PHP;

    return $php;
  }

  /**
   * Parameterizing complex queries
   */
  public function complexQueryParameterized()
  {
    $php = <<<'PHP'
        // ✅ SAFE - Complex query with parameters
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, COUNT(p.id) as post_count, 
                   MAX(p.created_at) as latest_post
            FROM users u
            LEFT JOIN posts p ON u.id = p.user_id
            WHERE u.created_at > ?
            AND u.status = ?
            AND u.country = ?
            GROUP BY u.id
            HAVING post_count > ?
            ORDER BY post_count DESC
            LIMIT ?, ?
        ");
        
        $startDate = '2024-01-01';
        $status = 'active';
        $country = 'US';
        $minPosts = 10;
        $offset = 0;
        $limit = 20;
        
        $stmt->execute([$startDate, $status, $country, $minPosts, $offset, $limit]);
        $results = $stmt->fetchAll();
        
        // All parameters safely bound - no injection possible!
        PHP;

    return $php;
  }

  /**
   * Preventing parameter injection in array handling
   */
  public function arrayParameterization()
  {
    $php = <<<'PHP'
        // ✅ SAFE - Array parameters
        $stmt = $pdo->prepare("
            SELECT * FROM posts 
            WHERE user_id IN (?, ?, ?)
            AND status = ?
        ");
        
        // Create placeholders for each ID
        $userIds = [1, 5, 10];
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        
        $stmt = $pdo->prepare("
            SELECT * FROM posts 
            WHERE user_id IN ($placeholders)
            AND status = ?
        ");
        
        $params = array_merge($userIds, ['published']);
        $stmt->execute($params);
        $posts = $stmt->fetchAll();
        PHP;

    return $php;
  }
}

// =============================================================================
// 3. WHY ELOQUENT & QUERY BUILDER ARE SAFE
// =============================================================================

/**
 * ✅ Safe database operations with Laravel
 */
class EloquentSafetyExamples
{
  /**
   * Basic Eloquent queries (all safe)
   */
  public function basicEloquentQueries()
  {
    $php = <<<'PHP'
        // ✅ ALL SAFE - Eloquent uses prepared statements

        // Simple where
        $user = User::where('username', $username)->first();
        // Generates: SELECT * FROM users WHERE username = ? (with parameter)

        // Multiple conditions
        $users = User::where('status', 'active')
                     ->where('role', 'admin')
                     ->get();
        // All conditions parameterized

        // whereIn
        $users = User::whereIn('id', [1, 2, 3, 4, 5])->get();
        // Generates: SELECT * FROM users WHERE id IN (?, ?, ?, ?, ?)

        // whereBetween
        $posts = Post::whereBetween('created_at', [$start, $end])->get();
        // Parameterized dates

        // whereNull
        $users = User::whereNull('deleted_at')->get();
        // Safe - no injection vector

        // whereRaw with parameters (safe!)
        $users = User::whereRaw('YEAR(created_at) = ?', [2024])->get();
        // Raw SQL, but parameter is safe
        PHP;

    return $php;
  }

  /**
   * Complex Eloquent queries
   */
  public function complexEloquentQueries()
  {
    $php = <<<'PHP'
        // ✅ SAFE - Complex queries with relationships

        // With relationships
        $users = User::with('posts', 'comments')
                     ->where('status', 'active')
                     ->get();

        // Joins
        $posts = Post::join('users', 'posts.user_id', '=', 'users.id')
                     ->where('users.status', 'active')
                     ->select('posts.*')
                     ->get();

        // Subqueries
        $posts = Post::whereIn('user_id', 
                    User::where('verified', true)->pluck('id')
                )->get();

        // Advanced where
        $users = User::where('age', '>', 18)
                     ->where('country', 'US')
                     ->orWhere('verified', true)
                     ->get();

        // All parameterized - safe!
        PHP;

    return $php;
  }

  /**
   * Why Eloquent is safe
   */
  public function whyEloquentIsSafe()
  {
    return [
      "Prepared Statements" => "Every where() uses prepared statements",
      "No String Concatenation" => "Never builds SQL strings",
      "Type Handling" => "Automatically handles parameter types",
      "Escaping" => "Database driver handles escaping",
      "Built-in Validation" => "Validates input types",
      "Relationships" => "Handles joins safely",
      "Aggregates" => "COUNT, SUM, AVG all parameterized"
    ];
  }
}

// =============================================================================
// 4. DANGEROUS PATTERNS - DB::select WITH VARIABLES
// =============================================================================

/**
 * ❌ Dangerous patterns to AVOID
 */
class DangerousPatterns
{
  /**
   * ❌ MOST DANGEROUS: String concatenation
   */
  public function dangerousStringConcatenation()
  {
    $php = <<<'PHP'
        // ❌ EXTREMELY DANGEROUS
        $username = $_POST['username'];
        $query = "SELECT * FROM users WHERE username = '" . $username . "'";
        $users = DB::select($query);
        
        // Attacker: ' OR '1'='1
        // Query: SELECT * FROM users WHERE username = '' OR '1'='1'
        // Result: ALL users returned!
        PHP;

    return $php;
  }

  /**
   * ✅ SAFE: DB::select with parameters
   */
  public function safeDatabaseSelect()
  {
    $php = <<<'PHP'
        // ✅ SAFE - Using prepared statements
        $username = $_POST['username'];
        
        // With positional parameter
        $users = DB::select("SELECT * FROM users WHERE username = ?", [$username]);
        
        // OR with named parameter
        $users = DB::select("SELECT * FROM users WHERE username = :username", ['username' => $username]);
        
        // All safe - parameters bound separately
        PHP;

    return $php;
  }

  /**
   * Recognizing vulnerable patterns
   */
  public function vulnerablePatternsToIdentify()
  {
    return [
      "❌ Direct concatenation" => 'DB::select("SELECT * FROM users WHERE id = " . $id)',
      "❌ String interpolation" => 'DB::select("SELECT * FROM users WHERE id = $id")',
      "❌ sprintf" => 'DB::select(sprintf("SELECT * FROM posts WHERE id = %d", $id))',
      "❌ PHP string building" => '$query = "SELECT * FROM users WHERE username = \'" . $_POST["username"] . "\'";',
      "✅ Parameter binding" => 'DB::select("SELECT * FROM users WHERE id = ?", [$id])'
    ];
  }
}

// =============================================================================
// 5. DB::raw() - WHEN SAFE AND WHEN NOT
// =============================================================================

/**
 * DB::raw() proper usage
 */
class DbRawExamples
{
  /**
   * ❌ DANGEROUS: DB::raw() with user input
   */
  public function dangerousDbRaw()
  {
    $php = <<<'PHP'
        // ❌ DANGEROUS - User input in raw()
        $sortBy = $_GET['sort'];
        $users = User::orderBy(DB::raw($sortBy))->get();
        
        // Attacker sends: sort=CASE WHEN (1=1) THEN id ELSE username END
        // Executes as code!
        
        // ❌ DANGEROUS - Concatenation with raw()
        $column = $_GET['column'];
        User::select(DB::raw($column))->get();
        
        // Attacker sends: column=*; DROP TABLE users; --
        // Deleted!
        PHP;

    return $php;
  }

  /**
   * ✅ SAFE: DB::raw() with hardcoded values
   */
  public function safeDbRaw()
  {
    $php = <<<'PHP'
        // ✅ SAFE - Hardcoded functions
        $users = User::orderBy(DB::raw('RAND()'))->get();
        
        // ✅ SAFE - Hardcoded calculations
        $stats = User::select('name', DB::raw('COUNT(*) as total'))
                    ->groupBy('name')
                    ->get();
        
        // ✅ SAFE - Hardcoded columns
        $posts = Post::select('title', DB::raw('YEAR(created_at) as year'))
                     ->get();
        
        // ✅ SAFE - System functions
        $users = User::whereRaw('DATEDIFF(?, created_at) < 30', [now()])->get();
        PHP;

    return $php;
  }

  /**
   * Parameterized DB::raw()
   */
  public function parameterizedDbRaw()
  {
    $php = <<<'PHP'
        // ✅ SAFE - DB::raw() with parameters
        
        // With ? placeholders
        User::whereRaw('YEAR(created_at) = ?', [2024])->get();
        
        // With named parameters
        User::whereRaw('MONTH(created_at) = :month', ['month' => 5])->get();
        
        // Complex raw query with parameters
        $posts = DB::select(
            "SELECT * FROM posts WHERE created_at > ? AND status = ?",
            [$startDate, 'published']
        );
        
        // All safe!
        PHP;

    return $php;
  }

  /**
   * Whitelisting approach for dynamic columns
   */
  public function whitelistApproach()
  {
    $php = <<<'PHP'
        // ✅ SAFE - Whitelist allowed columns
        
        $allowedColumns = ['id', 'name', 'email', 'created_at'];
        $sortBy = request()->get('sort', 'id');
        $direction = request()->get('direction', 'asc');
        
        // Validate against whitelist
        if (!in_array($sortBy, $allowedColumns)) {
            $sortBy = 'id';  // Default to safe column
        }
        
        if (!in_array(strtoupper($direction), ['ASC', 'DESC'])) {
            $direction = 'asc';  // Default to safe direction
        }
        
        // Now safe to use
        $users = User::orderBy($sortBy, $direction)->get();
        
        // NEVER:
        // $users = User::orderBy($sortBy)->get();  // Without validation!
        PHP;

    return $php;
  }
}

// =============================================================================
// 6. MASS ASSIGNMENT VULNERABILITY
// =============================================================================

/**
 * Mass assignment protection
 */
class MassAssignmentExamples
{
  /**
   * ❌ VULNERABLE: No protection
   */
  public function vulnerableMassAssignment()
  {
    $php = <<<'PHP'
        // ❌ VULNERABLE - No $fillable or $guarded
        class User extends Model
        {
            // No protection!
        }
        
        // Attacker sends form:
        // name=John&email=john@example.com&password=secret&is_admin=true
        
        $user = User::create($request->all());
        
        // Result: Attacker becomes admin!
        // All fields set: name, email, password, is_admin
        PHP;

    return $php;
  }

  /**
   * ✅ SAFE: Using $fillable (whitelist)
   */
  public function safeWithFillable()
  {
    $php = <<<'PHP'
        // ✅ SAFE - Explicit whitelist
        class User extends Model
        {
            protected $fillable = [
                'name',
                'email',
                'password'
                // Notably absent: is_admin, role, created_at, etc.
            ];
        }
        
        // Attacker tries:
        // name=John&email=john@example.com&password=secret&is_admin=true
        
        $user = User::create($request->all());
        
        // Result: Only fillable fields set
        // 'is_admin' and 'role' UNCHANGED (default values)
        // Attacker is regular user, not admin!
        
        // Check what was actually set:
        echo $user->is_admin;  // false (default)
        echo $user->role;      // null (default)
        PHP;

    return $php;
  }

  /**
   * ✅ SAFER: Using $guarded (blacklist)
   */
  public function safeWithGuarded()
  {
    $php = <<<'PHP'
        // ✅ SAFER - Explicit blacklist
        class User extends Model
        {
            protected $guarded = [
                'id',
                'is_admin',
                'role',
                'created_at',
                'updated_at'
            ];
        }
        
        // Protected fields can't be mass-assigned
        // But any new field can be assigned (less safe!)
        // Not recommended for sensitive models
        PHP;

    return $php;
  }

  /**
   * Best practice patterns
   */
  public function bestPracticePatterns()
  {
    $php = <<<'PHP'
        // ✅ BEST: Validate then explicitly set
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed'
        ]);
        
        // Only validated fields get set
        $user = User::create($validated);
        
        // Set sensitive fields explicitly if needed
        if ($request->user()->isAdmin()) {
            $user->role = $request->input('role');
            $user->is_admin = $request->boolean('is_admin');
            $user->save();
        }
        
        // ✅ ALTERNATIVE: Use only()
        $user = User::create($request->only('name', 'email', 'password'));
        
        // ✅ ALTERNATIVE: Use except()
        $user = User::create($request->except('is_admin', 'role'));
        PHP;

    return $php;
  }

  /**
   * Understanding the difference
   */
  public function fillableVsGuarded()
  {
    return [
      "Approach" => [
        "$fillable" => "Whitelist - ONLY these fields allowed",
        "$guarded" => "Blacklist - EXCEPT these fields allowed"
      ],
      "Safety" => [
        "$fillable" => "Safer - explicit opt-in",
        "$guarded" => "Less safe - implicit opt-in"
      ],
      "Recommendation" => [
        "$fillable" => "Use this - explicitly list safe fields",
        "$guarded" => "Use only if necessary"
      ],
      "Default" => [
        "$fillable" => "Recommended default",
        "$guarded" => "Alternative approach"
      ]
    ];
  }
}

// =============================================================================
// 7. TESTING FOR SQL INJECTION
// =============================================================================

/**
 * ✅ Testing and verification
 */
class SqlInjectionTesting
{
  /**
   * Unit test examples
   */
  public function unitTestExamples()
  {
    $php = <<<'PHP'
        // Test that parameterized queries work correctly
        public function testSqlInjectionPrevention()
        {
            $maliciousInput = "' OR '1'='1";
            
            // This should NOT return all users
            $users = User::where('username', $maliciousInput)->get();
            
            // Should find 0 results (no user with that exact name)
            $this->assertCount(0, $users);
        }
        
        // Test that queries are parameterized
        public function testQueryIsParameterized()
        {
            DB::enableQueryLog();
            
            $userId = "5; DROP TABLE users;";
            User::where('id', $userId)->first();
            
            $queries = DB::getQueryLog();
            $lastQuery = $queries[0];
            
            // Should be parameterized with ?
            $this->assertStringContainsString('?', $lastQuery['query']);
            
            // Verify table still exists
            $this->assertTrue(Schema::hasTable('users'));
        }
        
        // Test mass assignment protection
        public function testMassAssignmentProtection()
        {
            $data = [
                'name' => 'Attacker',
                'email' => 'attacker@example.com',
                'password' => bcrypt('secret'),
                'is_admin' => true,  // Trying to escalate privileges
                'role' => 'admin'
            ];
            
            $user = User::create($data);
            
            // Verify protected fields weren't set
            $this->assertFalse($user->is_admin);
            $this->assertNull($user->role);
        }
        PHP;

    return $php;
  }

  /**
   * Integration test examples
   */
  public function integrationTestExamples()
  {
    $php = <<<'PHP'
        // Test login with SQL injection attempt
        public function testLoginWithSqlInjection()
        {
            $response = $this->post('/login', [
                'username' => "' OR '1'='1",
                'password' => 'anything'
            ]);
            
            // Should fail, not bypass authentication
            $response->assertStatus(401);
            $this->assertGuest();  // Not logged in
        }
        
        // Test search with SQL injection
        public function testSearchWithInjection()
        {
            $response = $this->post('/search', [
                'query' => "'; DROP TABLE posts; --"
            ]);
            
            // Should return safe results
            $response->assertStatus(200);
            
            // Table should still exist
            $this->assertTrue(Schema::hasTable('posts'));
        }
        
        // Test API endpoint protection
        public function testApiWithInjection()
        {
            $response = $this->getJson('/api/users?filter[username]=' . urlencode("' OR '1'='1"));
            
            // Should handle safely
            $response->assertStatus(200);
            
            // Should return filtered results, not all users
            $this->assertLessThan(100, count($response->json('data')));
        }
        PHP;

    return $php;
  }

  /**
   * Manual testing patterns
   */
  public function manualTestingPatterns()
  {
    return [
      "Single Quote" => "' OR '1'='1",
      "Double Quote" => '" OR "1"="1',
      "Comment Bypass" => "'; --",
      "Space Comment" => "' # ",
      "Semicolon" => "'; DROP TABLE users; --",
      "Union-based" => "' UNION SELECT username, password FROM admin --",
      "Time-based" => "' OR SLEEP(5) --",
      "Boolean-based" => "' AND 1=1 --",
      "Null Byte" => "%00' OR '1'='1",
      "Hex Encoding" => "0x272f2a2a2f OR 0x31"
    ];
  }
}

// =============================================================================
// 8. REAL-WORLD SECURE IMPLEMENTATION
// =============================================================================

/**
 * Production-ready secure patterns
 */
class ProductionSecurePatterns
{
  /**
   * Secure user registration
   */
  public function secureRegistration()
  {
    $php = <<<'PHP'
        class UserController
        {
            public function store(Request $request)
            {
                // 1. Validate all input
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users',
                    'password' => 'required|min:8|confirmed'
                ]);
                
                // 2. Hash password
                $validated['password'] = bcrypt($validated['password']);
                
                // 3. Create using validated data only
                // Mass assignment protection via $fillable
                $user = User::create($validated);
                
                return response()->json($user, 201);
            }
        }
        
        class User extends Model
        {
            protected $fillable = ['name', 'email', 'password'];
            protected $hidden = ['password'];
        }
        PHP;

    return $php;
  }

  /**
   * Secure search implementation
   */
  public function secureSearch()
  {
    $php = <<<'PHP'
        class SearchController
        {
            public function index(Request $request)
            {
                $query = $request->input('q', '');
                $page = $request->integer('page', 1);
                
                // 1. Validate input
                if (strlen($query) < 2) {
                    return response()->json(['error' => 'Query too short'], 422);
                }
                if (strlen($query) > 100) {
                    return response()->json(['error' => 'Query too long'], 422);
                }
                
                // 2. Use parameterized query
                $results = DB::table('posts')
                    ->where('title', 'like', '%' . $query . '%')
                    ->orWhere('content', 'like', '%' . $query . '%')
                    ->paginate(15);
                
                // All parameters bound safely by Query Builder
                return response()->json($results);
            }
        }
        PHP;

    return $php;
  }

  /**
   * Secure complex query
   */
  public function secureComplexQuery()
  {
    $php = <<<'PHP'
        public function getTopContributors($minPosts = 10)
        {
            // All parameters bound safely
            return User::whereHas('posts', function ($query) use ($minPosts) {
                // Parameterized - safe!
                $query->where('status', 'published')
                      ->where('created_at', '>', now()->subYear());
            })
            ->withCount(['posts' => function ($query) use ($minPosts) {
                $query->where('status', 'published');
            }])
            ->having('posts_count', '>=', $minPosts)
            ->orderByDesc('posts_count')
            ->get();
        }
        PHP;

    return $php;
  }
}

// =============================================================================
// SUMMARY: Prevention Checklist
// =============================================================================

$preventionChecklist = [
  "Query Building" => [
    "✅ Always use Eloquent or Query Builder",
    "✅ Use parameterized queries with ? or :parameter",
    "✅ Never concatenate user input into SQL",
    "✅ Validate all input before using"
  ],

  "DB::raw() Usage" => [
    "✅ Only with hardcoded values",
    "✅ Use parameters if necessary",
    "✅ Whitelist allowed columns",
    "✅ Never pass user input directly"
  ],

  "Mass Assignment" => [
    "✅ Define $fillable for allowed fields",
    "✅ Use $guarded only as backup",
    "✅ Validate before creating",
    "✅ Set sensitive fields explicitly"
  ],

  "Testing" => [
    "✅ Test with malicious input patterns",
    "✅ Verify queries are parameterized",
    "✅ Log suspicious queries",
    "✅ Automated security scanning"
  ]
];
