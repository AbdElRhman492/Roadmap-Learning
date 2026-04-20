# 01 - Laravel Request Lifecycle

## Module Status: ✅ COMPLETE (100%)

The Laravel Request Lifecycle is the foundation for understanding how Laravel processes every HTTP request. This module covers the complete journey from the initial browser request to the final response.

---

## 📋 Learning Objectives

By completing this module, you understand:

- ✅ The complete HTTP request flow in Laravel
- ✅ Role of `public/index.php` as the entry point
- ✅ Application bootstrapping via `bootstrap/app.php`
- ✅ HTTP Kernel and its responsibilities
- ✅ Global middleware execution order
- ✅ Router matching and route resolution
- ✅ Route-specific middleware
- ✅ Service Container in controller instantiation
- ✅ Response building and middleware post-processing
- ✅ Difference between HTTP Kernel and Console Kernel

---

## 🎯 Key Concepts Mastered

### 1. **Request Entry Point** ✅

- `public/index.php` is the first file executed
- Loads Composer autoloader
- Creates and bootstraps the Laravel application
- Hands the request to the framework

### 2. **Application Bootstrap** ✅

- `bootstrap/app.php` creates the application instance
- Binds core services to the container
- Prepares kernel bindings
- Sets up overall application state

### 3. **HTTP Kernel** ✅

- Central coordinator for HTTP requests
- Orchestrates middleware pipeline
- Routes incoming requests
- Responsible for returning a response

### 4. **Global Middleware Stack** ✅

- Executes BEFORE route matching
- Handles framework concerns (maintenance mode, proxy trust, trimming)
- Typically handles session and CSRF setup
- Execution order matters - earlier middleware can alter the request

### 5. **Router & Route Matching** ✅

- Compares URI and HTTP method against route definitions
- Routes defined in `routes/web.php` and `routes/api.php`
- Resolves route parameters
- Applies route-specific middleware

### 6. **Route Middleware** ✅

- Attached to specific routes or route groups
- Examples: `auth`, `throttle`, `verified`
- Can be chained for multiple concerns
- Executes AFTER global middleware, BEFORE controller

### 7. **Service Container in Action** ✅

- Laravel asks container to build requested controller
- Constructor dependencies are auto-resolved
- Type hinting enables automatic injection
- Keeps controllers clean and testable

### 8. **Response Building** ✅

- Controller returns view, JSON, redirect, or custom response
- Laravel converts result into HTTP response
- Response middleware can modify headers/content
- Sent back to browser

### 9. **HTTP vs Console Kernel** ✅

- **HTTP Kernel**: Browser/API requests
- **Console Kernel**: Artisan commands and scheduled tasks
- Different entry points and middleware/bootstrapping

---

## 📁 Module Files

| File                                   | Purpose                                                 |
| -------------------------------------- | ------------------------------------------------------- |
| [notes.md](notes.md)                   | Comprehensive theoretical guide with all 9 key concepts |
| [code-examples.php](code-examples.php) | Practical code demonstrations (ready for expansion)     |
| README.md                              | This file - module overview and checkpoint              |

---

## 📊 Completion Checklist

### Understanding Verified ✅

- ✅ Can trace a request from `public/index.php` to final response
- ✅ Know what HTTP Kernel does in the lifecycle
- ✅ Understand when global middleware runs
- ✅ Can explain route matching and middleware
- ✅ Know how service container creates controllers
- ✅ Understand HTTP Kernel vs Console Kernel
- ✅ Can diagram the complete request lifecycle

---

## 🔗 Knowledge Connections

This module is foundational for understanding:

- **Next Module (Eloquent ORM - Advanced)** → Controllers and middleware context
- **Middleware Module** → Where middleware runs in lifecycle
- **Routing Module** → How routes are matched and resolved
- **Authentication Module** → Where auth middleware runs
- **Caching Module** → Where response caching happens in lifecycle

---

## 🎓 Practical Applications

Understanding the request lifecycle enables you to:

1. **Debug middleware issues** — Know exactly when middleware executes
2. **Optimize request handling** — Place logic at the right stage
3. **Implement custom middleware** — Understand where it fits
4. **Design controllers properly** — Leverage service container injection
5. **Handle responses correctly** — Know the complete flow
6. **Troubleshoot performance** — Identify bottlenecks in the flow

---

## 📚 Topics Covered

| Topic                            | Coverage |
| -------------------------------- | -------- |
| Entry Point (`public/index.php`) | Complete |
| Bootstrap (`bootstrap/app.php`)  | Complete |
| HTTP Kernel                      | Complete |
| Global Middleware                | Complete |
| Router & Route Matching          | Complete |
| Route Middleware                 | Complete |
| Service Container                | Complete |
| Response Building                | Complete |
| HTTP vs Console Kernel           | Complete |
| Checkpoint Questions             | 6/6      |

---

## ✨ Module Highlights

- **Foundational Knowledge** — Essential for all Laravel developers
- **Clear Architecture** — Simple entry point to complex framework internals
- **Visual Flow** — Complete request lifecycle diagram in notes
- **Practical Examples** — Real-world routing and middleware scenarios
- **Self-Assessment** — 6 checkpoint questions to verify understanding

---

## 🚀 Next Steps

**After completing this module:**

1. Progress to **02 - Eloquent ORM - Advanced** for database mastery
2. Review the request lifecycle diagram regularly as you progress
3. Use this knowledge as context for middleware and routing modules
4. Return to this module when debugging framework-level issues

---

**Module Author:** Learning Path Curriculum
**Last Updated:** April 20, 2026
**Status:** Complete and ready for production learning
