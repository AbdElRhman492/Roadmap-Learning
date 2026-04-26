# 04 - Middleware - Write Your Own

## Understanding

Middleware is a mechanism to filter HTTP requests entering your application. Think of it as a series of "gates" or "layers" that requests pass through before reaching your route handler. Each middleware can examine the request, make decisions, modify it, or even reject it entirely.

Middleware essentially creates a pipeline where requests flow through multiple layers. Each layer can:

- Inspect the request
- Modify the request
- Pass it to the next middleware
- Short-circuit and return a response (preventing further execution)

The request flows through all middlewares, reaches the controller, then flows back through middleware in reverse order (like an onion).

## Key Concepts

### 1. Creating Middleware with Artisan

```bash
php artisan make:middleware MiddlewareName
```

This creates a middleware file in `app/Http/Middleware/` directory with a basic `handle()` method.

### 2. Handle Method Signature

**Traditional (Before/After) Pattern:**

```php
public function handle(Request $request, Closure $next): Response
{
    // Before logic
    $response = $next($request);  // Pass to next middleware
    // After logic
    return $response;
}
```

**Modern Pattern (Request only):**

```php
public function handle(Request $request, Closure $next): Response
{
    return $next($request);
}
```

The `$next` parameter is a `Closure` that represents the next middleware in the pipeline.

### 3. Before vs After Middleware

**Before Middleware:** Logic executes BEFORE the request is processed

- Validate request data
- Authenticate users
- Check permissions
- Set headers

**After Middleware:** Logic executes AFTER the controller returns a response

- Modify response headers
- Log response data
- Transform response content
- Clean up resources

Both can be in the same middleware!

### 4. Registering Middleware

#### Global Middleware (Applied to All Routes)

Registered in `app/Http/Kernel.php`:

```php
protected $middleware = [
    \App\Http\Middleware\TrustProxies::class,
    \App\Http\Middleware\CheckForMaintenanceMode::class,
    \App\Http\Middleware\VerifyCsrfToken::class,  // Applied to all routes
];
```

#### Route Middleware (Applied to Specific Routes)

Registered in `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    'cors' => \App\Http\Middleware\Cors::class,
];
```

Then use in routes:

```php
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth');
```

### 5. Middleware Groups

Grouping multiple middleware together for convenient application to many routes.

Defined in `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\EncryptCookies::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
        \App\Http\Middleware\ShareErrorsFromSession::class,
    ],
    'api' => [
        'throttle:60,1',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

Usage:

```php
Route::middleware('web')->group(function () {
    Route::get('/', [HomeController::class, 'index']);
});
```

### 6. Passing Parameters to Middleware

Middleware can accept additional parameters beyond `$request` and `$next`.

Definition:

```php
public function handle(Request $request, Closure $next, $role = null): Response
{
    if ($role && !$this->user->hasRole($role)) {
        return response('Unauthorized', 403);
    }
    return $next($request);
}
```

Usage in routes:

```php
Route::get('/admin', [AdminController::class, 'index'])->middleware('role:admin');
Route::post('/users', [UserController::class, 'store'])->middleware('role:admin,user');
```

The parameters after the middleware name are passed to the handle method.

### 7. Terminable Middleware

Middleware that needs to perform action AFTER the response is sent to browser. Uses the `terminate()` method.

```php
public function terminate(Request $request, Response $response): void
{
    // Log request-response metrics
    // Send data to external service
    // Clean up resources after response sent
}
```

Common use cases:

- Logging request duration
- Sending analytics
- Cleaning up temporary files
- Writing to external services (after response already sent)

## Middleware Execution Order

1. Request enters application
2. Global middleware processes request (before logic)
3. Route middleware processes request (before logic)
4. Middleware groups process request (before logic)
5. **Request reaches controller and executes**
6. Middleware groups process response (after logic)
7. Route middleware processes response (after logic)
8. Global middleware processes response (after logic)
9. Response sent to browser
10. Terminable middleware executes (if defined)

## Best Practices

1. **Single Responsibility:** Each middleware should handle one concern
2. **Naming:** Use descriptive names (e.g., `VerifyApiToken`, `CheckAdminRole`)
3. **Global vs Route:** Use global only for essential middleware (CSRF, maintenance mode)
4. **Documentation:** Comment what your middleware does and why
5. **Error Handling:** Handle exceptions gracefully in middleware
6. **Performance:** Keep middleware logic fast to avoid request slowdown
7. **Terminator:** Use for async tasks that don't affect the response sent to user

## Code Examples

See code-examples.php for practical implementations
