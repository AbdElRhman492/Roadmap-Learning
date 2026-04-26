<?php

// 04 - Middleware - Write Your Own

/**
 * ============================================================================
 * 1. CREATING MIDDLEWARE - php artisan make:middleware
 * ============================================================================
 */

// Command: php artisan make:middleware CheckAge
// Creates: app/Http/Middleware/CheckAge.php

// This is a basic middleware structure:
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAge
{
    /**
     * Handle method signature - THIS IS THE CORE
     * 
     * @param Request $request - The incoming HTTP request
     * @param Closure $next - The next middleware in the pipeline
     * @return Response - The response that gets sent back
     */
    public function handle(Request $request, Closure $next): Response
    {
        // BEFORE LOGIC - executes before request is processed
        if ($request->query('age') < 18) {
            return response('Must be 18 years old', 403);
        }

        // Call next middleware/controller
        $response = $next($request);

        // AFTER LOGIC - executes after controller returns response
        $response->header('X-Checked', 'true');

        return $response;
    }
}

/**
 * ============================================================================
 * 2. BEFORE vs AFTER MIDDLEWARE
 * ============================================================================
 */

// BEFORE MIDDLEWARE - Process request BEFORE it reaches controller
class AuthenticateUser
{
    public function handle(Request $request, Closure $next): Response
    {
        // This runs BEFORE the controller
        if (!auth()->check()) {
            return response('Unauthorized', 401);
        }

        return $next($request);
    }
}

// AFTER MIDDLEWARE - Process response AFTER controller returns
class LogResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // This runs AFTER the controller and response is created
        $response->header('X-Response-Time', microtime(true));
        \Log::info('Response sent', [
            'path' => $request->path(),
            'status' => $response->status(),
        ]);

        return $response;
    }
}

// BEFORE AND AFTER - Same middleware doing both
class RequestResponseLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        // BEFORE LOGIC
        $start = microtime(true);
        \Log::info('Request received', [
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        // Process request through middleware stack
        $response = $next($request);

        // AFTER LOGIC
        $duration = microtime(true) - $start;
        \Log::info('Response sent', [
            'status' => $response->status(),
            'duration' => $duration,
        ]);

        return $response;
    }
}

/**
 * ============================================================================
 * 3. REGISTERING MIDDLEWARE IN KERNEL.php
 * ============================================================================
 */

// File: app/Http/Kernel.php

// GLOBAL MIDDLEWARE - Applied to ALL requests (before routing)
protected $middleware = [
    // \App\Http\Middleware\TrustHosts::class,
    \App\Http\Middleware\TrustProxies::class,
    \Fruitcake\Cors\HandleCors::class,
    \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
    \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
    \App\Http\Middleware\TrimStrings::class,
    \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
];

// ROUTE MIDDLEWARE - Applied to specific routes
protected $routeMiddleware = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
    'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
    'can' => \Illuminate\Auth\Middleware\Authorize::class,
    'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
    'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
    'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
    'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    // Custom middleware
    'age.check' => \App\Http\Middleware\CheckAge::class,
    'admin' => \App\Http\Middleware\CheckAdmin::class,
    'cors' => \App\Http\Middleware\Cors::class,
];

/**
 * ============================================================================
 * 4. ROUTE MIDDLEWARE vs GLOBAL MIDDLEWARE
 * ============================================================================
 */

// GLOBAL MIDDLEWARE EXAMPLE - Applied to ALL requests
class TrustProxies
{
    public function handle(Request $request, Closure $next): Response
    {
        // Runs on EVERY request - even before routing
        $request->setTrustedProxies(['10.0.0.0/8'], 'REMOTE_ADDR');
        return $next($request);
    }
}

// ROUTE MIDDLEWARE EXAMPLE - Applied selectively
class CheckAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only runs on routes where middleware is explicitly applied
        if (!auth()->user() || !auth()->user()->is_admin) {
            return response('Not admin', 403);
        }
        return $next($request);
    }
}

// Usage in routes:
/*
// Without middleware - no admin check
Route::get('/users', [UserController::class, 'index']);

// With middleware - admin check applied
Route::get('/admin/users', [UserController::class, 'index'])->middleware('admin');

// Multiple middleware
Route::get('/admin/users', [UserController::class, 'index'])
    ->middleware('auth')
    ->middleware('admin');

// Or inline
Route::get('/admin/users', [UserController::class, 'index'])
    ->middleware(['auth', 'admin']);
*/

/**
 * ============================================================================
 * 5. MIDDLEWARE GROUPS
 * ============================================================================
 */

// In app/Http/Kernel.php
protected $middlewareGroups = [
    // WEB group - applied to web routes
    'web' => [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],

    // API group - applied to API routes
    'api' => [
        'throttle:60,1',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],

    // CUSTOM group - your own combination
    'admin' => [
        'auth',                                          // Authenticate
        'admin',                                         // Check admin role
        \App\Http\Middleware\LogAdminActions::class,    // Log all admin actions
    ],

    'premium' => [
        'auth',                                         // Authenticate
        \App\Http\Middleware\CheckSubscription::class, // Check subscription
        \App\Http\Middleware\RateLimitPremium::class,  // Premium rate limit
    ],
];

// Usage in routes:
/*
// Apply single middleware group
Route::middleware('web')->group(function () {
    Route::get('/', [HomeController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
});

// API routes automatically get 'api' middleware group
Route::middleware('api')->prefix('api')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
});

// Custom group
Route::middleware('admin')->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
});

// Combine groups
Route::middleware('web', 'premium')->group(function () {
    Route::get('/premium', [PremiumController::class, 'index']);
});
*/

/**
 * ============================================================================
 * 6. PASSING PARAMETERS TO MIDDLEWARE
 * ============================================================================
 */

// Middleware that accepts parameters
class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // $roles will contain all parameters after middleware name
        // Example: 'admin,moderator' -> ['admin', 'moderator']

        if (!auth()->check()) {
            return response('Unauthorized', 401);
        }

        $userRole = auth()->user()->role;

        // Check if user has one of the required roles
        if (!in_array($userRole, $roles)) {
            return response("Forbidden - requires role: {$roles[0]}", 403);
        }

        return $next($request);
    }
}

// Middleware that accepts specific parameters
class RateLimitByPlan
{
    public function handle(Request $request, Closure $next, $requests, $minutes): Response
    {
        // Parameters: $requests = 100, $minutes = 60
        // Usage: middleware('rate:100,60')

        $key = auth()->id() . ':' . date('Y-m-d H:' . intdiv(date('i'), $minutes));
        $current = \Cache::get($key, 0);

        if ($current >= (int)$requests) {
            return response('Rate limit exceeded', 429);
        }

        \Cache::put($key, $current + 1, now()->addMinutes((int)$minutes));

        return $next($request);
    }
}

// Another example: Environment-specific middleware
class RequireEnvironment
{
    public function handle(Request $request, Closure $next, ...$environments): Response
    {
        if (!in_array(app()->environment(), $environments)) {
            return response('Not available in this environment', 403);
        }

        return $next($request);
    }
}

// Usage in routes:
/*
// Single parameter
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware('checkRole:admin');

// Multiple parameters
Route::get('/posts', [PostController::class, 'index'])
    ->middleware('rate:100,60');  // 100 requests per 60 minutes

// Multiple roles
Route::post('/users', [UserController::class, 'store'])
    ->middleware('checkRole:admin,moderator');

// Multiple values
Route::get('/api/data', [ApiController::class, 'data'])
    ->middleware('require-env:production,staging');
*/

/**
 * ============================================================================
 * 7. TERMINABLE MIDDLEWARE
 * ============================================================================
 */

// Terminable middleware - has terminate() method that runs AFTER response sent
class AnalyticsTracker
{
    public function handle(Request $request, Closure $next): Response
    {
        // Store start time in request for later use
        $request->attributes->put('start_time', microtime(true));

        return $next($request);
    }

    /**
     * This method runs AFTER the response is sent to the browser
     * Perfect for async tasks that don't need to complete before user sees response
     */
    public function terminate(Request $request, Response $response): void
    {
        $duration = microtime(true) - $request->attributes->get('start_time');

        // These tasks happen AFTER response sent - user doesn't wait for them
        \Analytics::track([
            'user_id' => auth()->id(),
            'path' => $request->path(),
            'method' => $request->method(),
            'status' => $response->status(),
            'duration' => $duration,
        ]);

        // Send to external service
        \SendGrid::logRequest($request, $response);

        // Clean up temporary files
        if ($request->hasFile('upload')) {
            Storage::disk('temp')->delete($request->file('upload')->getPathname());
        }
    }
}

// Register terminable middleware
/*
// In Kernel.php, just add to $routeMiddleware or $middleware
// The terminate() method will automatically be called

// Or explicitly make it terminable by implementing the interface
*/

class UserActivityLogger
{
    private $logFile;

    public function handle(Request $request, Closure $next): Response
    {
        $this->logFile = 'logs/activity/' . auth()->id() . '.log';
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        // Log to file after response sent - doesn't slow down response
        \Log::stack(['single'])->info('User Activity', [
            'user_id' => auth()->id(),
            'action' => $request->route()->getName(),
            'time' => now(),
        ]);
    }
}

/**
 * ============================================================================
 * 8. PRACTICAL EXAMPLES - REAL WORLD USE CASES
 * ============================================================================
 */

// Example 1: CORS Middleware
class Cors
{
    public function handle(Request $request, Closure $next): Response
    {
        // For preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            return response()
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type,Authorization')
                ->status(200);
        }

        $response = $next($request);

        $response->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type,Authorization');

        return $response;
    }
}

// Example 2: API Token Authentication
class VerifyApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token || !\DB::table('api_tokens')
            ->where('token', hash('sha256', $token))
            ->where('revoked', false)
            ->exists()) {
            return response('Invalid API token', 401);
        }

        return $next($request);
    }
}

// Example 3: Subdomain Routing Middleware
class SubdomainTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $subdomain = explode('.', $request->getHost())[0];

        if ($subdomain === 'www') {
            $tenant = null;
        } else {
            $tenant = \App\Models\Tenant::where('subdomain', $subdomain)->firstOrFail();
            \Cache::put('tenant', $tenant, now()->addHours(1));
        }

        return $next($request);
    }
}

// Example 4: Language/Locale Middleware
class SetLocale
{
    public function handle(Request $request, Closure $next, $locale = null): Response
    {
        // Accept locale from URL parameter, session, or config
        $locale = $locale ?? session('locale') ?? config('app.locale');

        if (in_array($locale, config('app.available_locales'))) {
            app()->setLocale($locale);
            session(['locale' => $locale]);
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        // Log language usage after response sent
        \Log::info('Locale used: ' . app()->getLocale());
    }
}

// Example 5: IP Whitelist/Blacklist Middleware
class IpFilter
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $whitelist = config('security.ip_whitelist');
        $blacklist = config('security.ip_blacklist');

        if (!empty($blacklist) && in_array($ip, $blacklist)) {
            return response('IP blocked', 403);
        }

        if (!empty($whitelist) && !in_array($ip, $whitelist)) {
            return response('IP not whitelisted', 403);
        }

        return $next($request);
    }
}

/**
 * ============================================================================
 * 9. MIDDLEWARE EXECUTION FLOW - Visual Example
 * ============================================================================
 */

/*
    REQUEST
      ↓
    Global Middleware 1 (Before)
      ↓
    Global Middleware 2 (Before)
      ↓
    Route Middleware (Before)
      ↓
    Middleware Group (Before)
      ↓
    CONTROLLER EXECUTION
      ↓
    Middleware Group (After)
      ↓
    Route Middleware (After)
      ↓
    Global Middleware 2 (After)
      ↓
    Global Middleware 1 (After)
      ↓
    RESPONSE SENT TO BROWSER
      ↓
    Terminable Middleware.terminate()
      ↓
    REQUEST COMPLETE

Example:
- RequestLogger middleware tracks time
- AuthMiddleware checks if user is logged in
- AdminMiddleware checks if user is admin
- Controller returns response
- Response logged and modified
- Response sent to browser
- Analytics data sent AFTER user gets response
*/

/**
 * ============================================================================
 * 10. KEY POINTS SUMMARY
 * ============================================================================
 */

/*
✓ Middleware is a pipeline pattern - requests flow through layers
✓ handle() method has signature: (Request, Closure) -> Response
✓ Before logic runs when $next is called
✓ After logic runs when middleware gets response back from $next
✓ Global middleware applies to ALL requests
✓ Route middleware applies only to specified routes
✓ Middleware groups bundle multiple middleware together
✓ Pass parameters using: middleware('name:param1,param2')
✓ Access parameters in handle() method as extra parameters
✓ Terminable middleware runs AFTER response sent to browser
✓ Use terminate() for async tasks, logging, cleanup
✓ Always return Response object or call next($request)
✓ Can short-circuit pipeline by returning response without calling next()
*/
