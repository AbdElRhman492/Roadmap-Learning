# 03 - Dependency Injection & IoC (Inversion of Control)

## Understanding Dependency Injection & IoC

Dependency Injection and the Inversion of Control Container are fundamental concepts in modern software development, particularly important for Laravel. They solve one of the most critical problems in object-oriented programming: **how to manage dependencies between classes without creating tight coupling**.

---

## 7 Core Pillars

### Pillar 1: What is DI and Why It Matters

#### The Problem: Tight Coupling

Without Dependency Injection, classes create their own dependencies:

```php
// BAD: Tightly coupled
class UserService
{
    private UserRepository $repository;

    public function __construct()
    {
        // Creates its own dependency - hard to test!
        $this->repository = new UserRepository();
    }
}
```

**Problems:**

- **Hard to test** - Can't mock the database
- **Hard to swap implementations** - If you want a different storage, you must change the class
- **Hidden dependencies** - The class interface doesn't show what it needs
- **Fragile code** - If UserRepository changes its constructor, this breaks

#### The Solution: Loose Coupling

With Dependency Injection, dependencies are injected into the class:

```php
// GOOD: Loosely coupled
class UserService
{
    private UserRepository $repository;

    // Dependencies are injected - class receives what it needs
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }
}
```

**Benefits:**

- **Easy to test** - Inject a mock repository
- **Easy to swap** - Inject different implementations
- **Clear dependencies** - The constructor signature shows what's needed
- **Flexible** - Can use different implementations based on configuration

#### Why It Matters

- **Testability** - The #1 reason for DI - you can test with fake/mock objects
- **Maintainability** - Code is easier to understand and modify
- **Flexibility** - Swap implementations without changing code
- **Decoupling** - Classes don't know about concrete implementations
- **SOLID Principles** - DI is essential for following Dependency Inversion Principle

---

### Pillar 2: Constructor Injection

Constructor injection is the **most common and recommended** pattern.

#### Basic Pattern

```php
interface Logger
{
    public function log(string $message): void;
}

class ConsoleLogger implements Logger
{
    public function log(string $message): void
    {
        echo $message;
    }
}

// Dependency injected via constructor
class UserService
{
    public function __construct(private Logger $logger)
    {
    }

    public function registerUser(string $email): void
    {
        $this->logger->log("Registering: $email");
        // Register user...
    }
}

// Usage:
$logger = new ConsoleLogger();
$service = new UserService($logger);
$service->registerUser('john@example.com');
```

#### Why Constructor Injection is Best

1. **Immutability** - Dependencies can't change after construction
2. **Required dependencies** - You can't forget to provide them
3. **Type safety** - PHP will throw an error if you don't provide the right type
4. **Clear API** - The constructor shows all dependencies needed
5. **Thread safety** - In concurrent scenarios, immutable deps are safer

#### Multiple Dependencies

Classes often need multiple dependencies:

```php
class OrderService
{
    public function __construct(
        private OrderRepository $repository,
        private NotificationService $notifier,
        private Logger $logger
    ) {
    }

    public function placeOrder(array $orderData): void
    {
        $this->logger->log("Processing order");

        $order = $this->repository->save($orderData);

        $this->notifier->sendConfirmation($order);
    }
}

// Usage:
$service = new OrderService(
    new OrderRepository(),
    new NotificationService(),
    new ConsoleLogger()
);
```

---

### Pillar 3: Method Injection

Sometimes dependencies are only needed for specific methods, or they may be optional. In these cases, **method injection** is appropriate.

#### When to Use Method Injection

1. **Optional dependencies** - Not always needed
2. **One-time use dependencies** - Only used once
3. **Changing dependencies** - Need to swap implementation at runtime
4. **Legacy code** - Easier to add to existing classes

#### Pattern

```php
interface Notifier
{
    public function notify(string $message): void;
}

class OrderProcessor
{
    public function processOrder(array $data): void
    {
        // Process the order...
    }

    // Method injection - dependency for this method only
    public function sendNotification(Notifier $notifier, string $message): void
    {
        $notifier->notify($message);
    }
}

// Usage:
$processor = new OrderProcessor();
$processor->processOrder(['item' => 'Book']);
$processor->sendNotification(new EmailNotifier(), "Order confirmed!");
```

#### Constructor vs Method Injection

| Aspect          | Constructor              | Method                      |
| --------------- | ------------------------ | --------------------------- |
| **Usage**       | Core dependencies        | Optional, method-specific   |
| **Frequency**   | Used by multiple methods | Used by one method          |
| **Required**    | Yes                      | No                          |
| **Visibility**  | Clear from class         | Clear from method signature |
| **Performance** | Best - set once          | Slightly more overhead      |

---

### Pillar 4: Service Container - The Core Mechanism

#### What is a Service Container?

A Service Container (IoC Container) is an object that **manages object creation and dependency resolution**.

**Key responsibilities:**

- Store bindings (configurations for how to create services)
- Create instances on demand
- Resolve dependencies automatically
- Manage service lifecycle (singleton vs transient)
- Cache instances when needed

#### How It Works

```php
// The container stores bindings
$container = new Container();

// Bind: "when someone asks for PaymentGateway, create Stripe"
$container->bind(PaymentGateway::class, StripeGateway::class);

// Resolve: Create an instance and return it
$gateway = $container->resolve(PaymentGateway::class); // Returns StripeGateway instance
```

#### Container Resolution Process

```
User asks container for a service
         ↓
Container checks if it's bound
         ↓
YES: Create/return the concrete instance
NO: Try to instantiate directly (if class exists)
         ↓
Analyze constructor parameters
         ↓
Recursively resolve each dependency
         ↓
Inject dependencies and create instance
         ↓
Return the instance
```

#### Simple Container Example

```php
class Container
{
    private array $bindings = [];
    private array $singletons = [];

    public function bind(string $abstract, $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function singleton(string $abstract, $concrete): void
    {
        $this->bind($abstract, $concrete);
        $this->singletons[$abstract] = true;
    }

    public function resolve(string $abstract)
    {
        // Check if singleton already cached
        if (isset($this->singletons[$abstract])) {
            if (is_object($this->bindings[$abstract])) {
                return $this->bindings[$abstract];
            }
        }

        $concrete = $this->bindings[$abstract] ?? $abstract;

        // If factory (closure), call it
        if (is_callable($concrete)) {
            $instance = $concrete($this);
        } else {
            // Otherwise instantiate
            $instance = new $concrete();
        }

        // Cache if singleton
        if (isset($this->singletons[$abstract])) {
            $this->bindings[$abstract] = $instance;
        }

        return $instance;
    }
}
```

#### Binding Types

**1. Class-to-Class Binding**

```php
$container->bind(UserRepository::class, MySQLUserRepository::class);
```

**2. Factory Binding**

```php
$container->bind(DatabaseConnection::class, function($container) {
    return new PDO('mysql:host=localhost', 'user', 'pass');
});
```

**3. Instance Binding**

```php
$instance = new UserRepository();
$container->instance(UserRepository::class, $instance);
```

---

### Pillar 5: Service Providers - boot() vs register()

Service Providers are **the place where you configure services** in your application. Laravel uses them to bootstrap the framework.

#### The Two Phases

**Phase 1: Registration** (`register()`)

- Bind services into the container
- Should NOT use other services yet
- Should NOT perform any logic
- Other services might not be registered

```php
class LogServiceProvider extends ServiceProvider
{
    public function register()
    {
        // ONLY bind services here
        $this->app->singleton(Logger::class, ConsoleLogger::class);
    }
}
```

**Phase 2: Booting** (`boot()`)

- Called after ALL services are registered
- Safe to use other services
- Perform initialization logic
- Configuration, setup, etc.

```php
class LogServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Safe to resolve other services
        $logger = $this->app->make(Logger::class);

        // Configure/initialize
        $logger->setLevel('debug');
        $this->publishConfig();
    }
}
```

#### Real-World Example

```php
class AppServiceProvider extends ServiceProvider
{
    // Phase 1: Bind services
    public function register()
    {
        // Bind repository interface to implementation
        $this->app->singleton(
            UserRepositoryInterface::class,
            MySQLUserRepository::class
        );

        // Bind with factory
        $this->app->singleton(Cache::class, function($app) {
            return new RedisCache(config('redis'));
        });
    }

    // Phase 2: Boot/configure
    public function boot()
    {
        // Now we can use the cache
        $cache = $this->app->make(Cache::class);

        // Register event listeners
        Event::listen('user.created', function($event) {
            // Handle event
        });

        // Publish assets, config files, etc.
        $this->publishes([
            __DIR__ . '/config' => config_path(),
        ]);
    }
}
```

#### Laravel's Bootstrap Order

```
1. Create Container
   ↓
2. Register Core Providers (register())
   ↓
3. Register App Providers (register())
   ↓
4. Boot Core Providers (boot())
   ↓
5. Boot App Providers (boot())
   ↓
6. Request processing
```

---

### Pillar 6: Binding Interfaces to Implementations

This is **the power of dependency injection** - the ability to bind different implementations to the same interface.

#### Why Interfaces?

```php
// BAD: Depend on concrete class
class PaymentController
{
    public function __construct(private StripeGateway $stripe) // Tightly coupled!
    {
    }
}

// GOOD: Depend on interface
class PaymentController
{
    public function __construct(private PaymentGateway $gateway) // Loosely coupled
    {
    }
}
```

#### Multiple Implementations

```php
interface PaymentGateway
{
    public function charge(float $amount): bool;
}

class StripeGateway implements PaymentGateway
{
    public function charge(float $amount): bool
    {
        // Stripe logic
    }
}

class PayPalGateway implements PaymentGateway
{
    public function charge(float $amount): bool
    {
        // PayPal logic
    }
}

class SquareGateway implements PaymentGateway
{
    public function charge(float $amount): bool
    {
        // Square logic
    }
}

// In service provider:
$app->bind(PaymentGateway::class, match(env('PAYMENT_DRIVER')) {
    'stripe' => StripeGateway::class,
    'paypal' => PayPalGateway::class,
    'square' => SquareGateway::class,
});
```

#### Benefits of Interface Binding

1. **Swappable implementations** - Change driver in config, not code
2. **Easy testing** - Bind mock implementations
3. **Multiple drivers** - Support many payment providers
4. **Loose coupling** - Code doesn't know which implementation is used
5. **SOLID-compliant** - Follows Dependency Inversion Principle

#### Testing Example

```php
// In test:
$app->bind(PaymentGateway::class, MockPaymentGateway::class);

// Now your tests run with mock instead of real Stripe
class PaymentControllerTest extends TestCase
{
    public function test_payment_processing()
    {
        $response = $this->post('/payment', ['amount' => 100]);

        $response->assertSuccessful(); // No real charges made!
    }
}
```

---

### Pillar 7: Singletons in the Container

A **singleton** ensures only ONE instance of a service exists throughout the application lifetime.

#### When to Use Singletons

| Use Cases           | Why                             |
| ------------------- | ------------------------------- |
| Database connection | Expensive to create, reuse pool |
| Logger              | Shared log file handle          |
| Config service      | Load config once, share         |
| Cache manager       | Single in-memory store          |
| Service locator     | One registry                    |
| HTTP client         | Reuse connections               |

#### Singleton vs Transient

```php
// TRANSIENT: New instance every time
$container->bind(UserId::class, UserId::class);

$id1 = $container->resolve(UserId::class); // New instance
$id2 = $container->resolve(UserId::class); // Different instance
$id1 === $id2; // false

// SINGLETON: One instance always
$container->singleton(Database::class, Database::class);

$db1 = $container->resolve(Database::class); // Created here
$db2 = $container->resolve(Database::class); // Reused
$db1 === $db2; // true
```

#### Singleton Implementation

```php
class Container
{
    private array $singletons = [];

    public function singleton(string $abstract, $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
        $this->singletons[$abstract] = true;
    }

    public function resolve(string $abstract)
    {
        // Return cached singleton if exists
        if (isset($this->singletons[$abstract]) &&
            is_object($this->bindings[$abstract])) {
            return $this->bindings[$abstract];
        }

        // Create instance
        $instance = $this->makeInstance($abstract);

        // Cache if singleton
        if (isset($this->singletons[$abstract])) {
            $this->bindings[$abstract] = $instance;
        }

        return $instance;
    }
}
```

#### Real-World Example: Database Connection

```php
// Without singleton - bad!
class UserRepository
{
    public function __construct(private PDO $pdo)
    {
        // Each class gets new PDO - creates new connections!
    }
}

// With singleton - good!
class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(PDO::class, function() {
            return new PDO('mysql:host=localhost', 'user', 'pass');
        });
    }
}

// Every class shares the same PDO instance
class UserRepository
{
    public function __construct(private PDO $pdo)
    {
        // Gets the shared singleton instance
    }
}
```

---

## Key Concepts Summary

### Dependency Injection Patterns

| Pattern         | When                     | Example          |
| --------------- | ------------------------ | ---------------- |
| **Constructor** | Main dependencies        | Database, logger |
| **Method**      | Optional/one-time        | Email sending    |
| **Property**    | Rarely (not recommended) | Legacy code      |

### Container Lifecycle

| Phase          | Description              | What to Do              |
| -------------- | ------------------------ | ----------------------- |
| **Binding**    | Register services        | `bind()`, `singleton()` |
| **Resolution** | Create/retrieve instance | `resolve()`, `make()`   |
| **Caching**    | Store if singleton       | Automatic               |
| **Usage**      | Application uses service | Your code               |

### SOLID Principles with DI

- **S** - Each service has one responsibility ✓
- **O** - Open to extension (new implementations) ✓
- **L** - Liskov substitution (swap implementations) ✓
- **I** - Interface segregation (specific contracts) ✓
- **D** - Dependency inversion (depend on interfaces) ✓

---

## Laravel-Specific Information

### Laravel's Service Container

Laravel has a built-in service container (`Illuminate\Container\Container`).

```php
// Binding in service provider
class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Bind
        $this->app->bind('mailer', function($app) {
            return new MailService($app['config']['mail']);
        });

        // Singleton
        $this->app->singleton('auth', function($app) {
            return new AuthManager($app);
        });
    }

    public function boot()
    {
        // Access singleton
        $auth = app('auth');
        $auth->configure();
    }
}
```

### Automatic Resolution

Laravel's container can auto-wire dependencies based on type hints:

```php
// Laravel automatically resolves this
class UserController
{
    public function __construct(
        private UserService $users,  // Auto-resolved
        private Logger $logger       // Auto-resolved
    ) {
    }
}
```

### Facade Pattern

Laravel uses Facades as a convenient way to access container services:

```php
// Behind the scenes, uses container
Log::info('User created');

// Is actually
app('log')->info('User created');
```

---

## Best Practices

### 1. Prefer Constructor Injection

```php
// Good
public function __construct(private Logger $logger) {}

// Avoid
public function someMethod(Logger $logger = null) {}
```

### 2. Depend on Interfaces, Not Concrete Classes

```php
// Good
public function __construct(private CacheInterface $cache) {}

// Avoid
public function __construct(private RedisCache $cache) {}
```

### 3. Keep register() Simple

```php
// Good - only bindings
public function register()
{
    $this->app->singleton(MyService::class, MyService::class);
}

// Avoid - complex logic
public function register()
{
    // Initializing, loading files, etc.
}
```

### 4. Use boot() for Initialization

```php
public function boot()
{
    // Configuration, event registration, etc.
    Event::listen('event', [$this, 'handler']);
}
```

### 5. Bind Early, Use Late

```php
public function register()
{
    $this->app->singleton(ApiClient::class, ApiClient::class);
}

// Much later in boot()
public function boot()
{
    $client = $this->app->make(ApiClient::class);
}
```

---

## Common Patterns

### Factory Pattern with Container

```php
$container->bind(UserRepository::class, function($app) {
    $connection = $app->make(PDO::class);
    return new UserRepository($connection);
});
```

### Lazy Loading

```php
$container->bind(HeavyService::class, function($app) {
    // Only created when actually resolved
    return new HeavyService();
});
```

### Conditional Binding

```php
if (app()->environment('testing')) {
    $container->bind(MailService::class, MockMailService::class);
} else {
    $container->bind(MailService::class, MailService::class);
}
```

---

## Key Takeaways

1. **DI Solves Coupling** - Classes don't know how to create their dependencies
2. **Constructor Injection is Default** - Use it for required dependencies
3. **Method Injection for Flexibility** - Use it for optional or runtime-specific needs
4. **Container Manages Everything** - Centralized service creation and resolution
5. **Service Providers Bootstrap** - `register()` binds, `boot()` initializes
6. **Interfaces Enable Flexibility** - Swap implementations without changing code
7. **Singletons for Expensive Services** - Database, cache, logging
8. **Testability is #1 Benefit** - Inject mocks for isolated testing

---

## Checkpoint Questions

- What's the difference between tight and loose coupling?
- Why is constructor injection preferred?
- When would you use method injection instead?
- How does a service container resolve dependencies?
- What's the difference between `register()` and `boot()`?
- Why depend on interfaces instead of concrete classes?
- When should you use singletons?
- How would you test a class that depends on a database?
