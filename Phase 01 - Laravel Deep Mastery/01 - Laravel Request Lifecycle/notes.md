# 01 - Laravel Request Lifecycle

## Understanding

Laravel’s request lifecycle is the path an incoming HTTP request follows from the web server to the final response. In simple terms:

`public/index.php` → `bootstrap/app.php` → HTTP Kernel → middleware pipeline → router → controller/service container → response

Understanding this flow helps you know where middleware runs, how routes are matched, how controllers are created, and where the response is finalized.

## Key Concepts

### `public/index.php` - the entry point
- This is the first file hit by the web server for HTTP requests.
- It loads Composer’s autoloader.
- It creates the Laravel application bootstrap and hands the incoming request to the framework.

### `bootstrap/app.php` - Application creation
- Creates the Laravel application instance.
- Binds the core services needed by the framework.
- Prepares the container, kernel bindings, and overall application state.

### HTTP Kernel - what it is and what it does
- The HTTP Kernel is the central coordinator for web requests.
- It receives the request and sends it through the middleware pipeline.
- It is responsible for bootstrapping the app for HTTP traffic and returning a response.

### Global middleware stack execution order
- Global middleware runs before the router matches the request.
- It typically handles concerns like maintenance mode, proxy trust, request trimming, empty string conversion, and session/CSRF-related setup.
- Order matters: earlier middleware can change the request before later middleware sees it.

### Router - how route matching works
- The router compares the incoming request’s URI and HTTP method with route definitions.
- Routes are usually defined in `routes/web.php` and `routes/api.php`.
- If a match is found, Laravel resolves route parameters and applies route-specific middleware.

### Route middleware (`auth`, `throttle`, etc.)
- Route middleware is attached to specific routes or route groups.
- `auth` protects routes so only authenticated users can access them.
- `throttle` limits how many requests can be made in a period of time.
- Middleware can also be chained to handle authorization, localization, logging, and more.

### Controller instantiation via Service Container
- When a route points to a controller, Laravel asks the service container to build it.
- Constructor dependencies are automatically resolved if they are type-hinted and bound.
- This keeps controllers clean and makes them easier to test.

### Response building and sending
- A controller can return a view, JSON, redirect, plain string, file download, or a custom response object.
- Laravel converts the result into an HTTP response.
- Response middleware can still modify headers or content before the response is sent back to the browser.

### Difference: HTTP Kernel vs Console Kernel
- The HTTP Kernel handles browser/API requests.
- The Console Kernel handles Artisan commands and scheduled tasks.
- Both boot the application, but they serve different entry points and middleware/bootstrapping needs.

## Code Examples

### Request lifecycle trace
```php
// Browser request
// public/index.php
//   -> bootstrap/app.php
//   -> HTTP Kernel
//   -> global middleware
//   -> router
//   -> route middleware
//   -> controller
//   -> response sent
```

### Route to controller with middleware
```php
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:60,1'])->get('/posts', [PostController::class, 'index']);

// Laravel resolves PostController through the service container,
// runs auth + throttle middleware first, then calls index().
```

## Resources

- Laravel Documentation: Request Lifecycle
- Laravel Documentation: Middleware
- Laravel Documentation: Routing
- Laravel Documentation: Service Container

## Checkpoint

- Can I trace a request from `public/index.php` to the final response?
- Do I know what the HTTP Kernel does in the lifecycle?
- Can I explain when global middleware runs?
- Do I understand how route matching and route middleware work?
- Can I describe how the service container creates controllers?
- Do I know the difference between the HTTP Kernel and the Console Kernel?

