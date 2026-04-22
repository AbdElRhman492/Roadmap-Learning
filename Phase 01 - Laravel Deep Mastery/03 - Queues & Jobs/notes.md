# 03 - Queues & Jobs

## Understanding

Queues are one of Laravel's most powerful features for handling long-running tasks asynchronously. Instead of making users wait for slow operations (sending emails, processing images, generating reports) to complete, you push them to a queue where workers process them in the background.

**Why Queues Matter:**

- Keep HTTP responses fast (user doesn't wait)
- Distribute work across multiple workers
- Retry failed jobs automatically
- Process tasks at off-peak times
- Scale to millions of jobs efficiently
- Separate concerns: web server vs background processing

**Real-World Scenario:** Sending emails to 60,000 students

- Without queues: HTTP request hangs for 10+ minutes
- With queues: Response sent immediately, emails processed in background in ~15 minutes with 5 workers

---

## Key Concepts

### 1. THE PROBLEM QUEUES SOLVE

**Synchronous Processing (No Queues)** ❌

```
User Request → Send Email → Wait... → HTTP Response
```

- Slow responses (poor UX)
- Worker dies = lost request
- Can't retry failed tasks
- Scales poorly

**Asynchronous Processing (With Queues)** ✅

```
User Request → Push to Queue → HTTP Response (immediate)
         ↓
   Background Worker → Send Email → Retry if fails
```

- Fast responses (good UX)
- Resilient to failures
- Automatic retries
- Scales to millions

**Slow Operations That Need Queues:**

- Sending emails (100ms - 1s each)
- Processing images/videos (seconds to minutes)
- API calls to external services
- Database intensive reports
- Bulk data imports/exports
- Payment processing
- Webhooks and notifications

---

### 2. CREATING JOBS

**Command:**

```bash
php artisan make:job SendWelcomeEmail
php artisan make:job ProcessExamSubmission
php artisan make:job GenerateReportPDF
```

**Job Structure:**

```php
<?php
namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWelcomeEmail implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, SerializesModels;

    private $user;

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function handle() {
        // Actual work happens here
        Mail::to($this->user)->send(new WelcomeEmail($this->user));
    }
}
```

**Key Components:**

- `ShouldQueue` interface: Makes job queueable
- `Dispatchable` trait: Allows `dispatch()` method
- `InteractsWithQueue` trait: Access to queue info
- `SerializesModels` trait: Serialize/deserialize models
- `__construct()`: Receive dependencies
- `handle()`: Main logic (executed by worker)

**Job Lifecycle:**

1. Create job instance with data
2. Serialize to JSON
3. Push to queue
4. Worker picks it up
5. Deserialize and execute `handle()`
6. Mark as completed
7. If failed, retry or move to dead letter queue

---

### 3. DISPATCHING JOBS

#### **dispatch() - Fire and Forget**

```php
// From controller, event, listener, etc.
dispatch(new SendWelcomeEmail($user));

// Returns immediately (user doesn't wait)
```

#### **dispatchAfterResponse() - After HTTP Response**

```php
dispatchAfterResponse(new SendWelcomeEmail($user));

// Job only added to queue AFTER response sent to client
// Useful for: cleanup, logging, non-critical tasks
```

**When to Use Each:**

- `dispatch()`: Essential background work
- `dispatchAfterResponse()`: Nice-to-have tasks, reduced client latency

#### **dispatch() with Configuration**

```php
dispatch(new SendWelcomeEmail($user))
    ->onQueue('emails')           // Specific queue
    ->delay(60)                   // 1 minute delay
    ->onConnection('redis');      // Use Redis, not database
```

**Chaining Dispatch:**

```php
SendWelcomeEmail::dispatch($user)
    ->onQueue('emails')
    ->delay(now()->addHours(1));
```

---

### 4. REAL USE CASE: BULK EMAIL TO 60K STUDENTS ⭐ CRITICAL

**Scenario:** Send exam results to 60,000 students

**WITHOUT Queues (❌ WRONG):**

```php
$students = Student::all(); // 60,000 records
foreach ($students as $student) {
    Mail::to($student)->send(new ExamResults($student)); // Each takes ~100-500ms
}
// Total time: 60,000 * 200ms = 3+ hours of blocking!
// User gets 500 error or timeout after 30 seconds
```

**WITH Queues (✅ CORRECT):**

```php
// Controller
$students = Student::all();
foreach ($students as $student) {
    SendExamResults::dispatch($student)->onQueue('emails');
}
// Response sent in <100ms

// Job
class SendExamResults implements ShouldQueue {
    public $tries = 5;        // Retry up to 5 times
    public $backoff = [10, 30, 60, 300, 900]; // Exponential backoff

    public function handle() {
        Mail::to($this->student)->send(new ExamResults($this->student));
    }
}

// Terminal (run multiple worker processes)
php artisan queue:work --queue=emails
php artisan queue:work --queue=emails  // Worker 2
php artisan queue:work --queue=emails  // Worker 3
php artisan queue:work --queue=emails  // Worker 4
php artisan queue:work --queue=emails  // Worker 5
```

**Performance:**

- Dispatching: <100ms (response sent)
- Processing with 5 workers: 60,000 ÷ 5 workers × 200ms/email = ~40 minutes
- With 20 workers: ~10 minutes
- Can run during off-peak hours to reduce server load

**Critical Configurations:**

```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 5,
    ],
],

// config/app.php queue values
'queue' => env('QUEUE_DRIVER', 'sync'),
```

---

### 5. QUEUE DRIVERS

Different backends for storing job data:

#### **sync Driver** (Synchronous - No Queueing)

- Executes jobs immediately (no worker needed)
- Default in development
- Use only for: testing, development
- NOT production-safe

```bash
# .env
QUEUE_CONNECTION=sync
```

```php
// Jobs execute immediately in same process
dispatch(new SendEmail()); // Blocks until done
```

#### **database Driver** (SQLite/MySQL/PostgreSQL)

- Jobs stored in database table
- Good for: small/medium applications, no external services
- Requires: `php artisan queue:table && migrate`
- Need at least 1 worker: `php artisan queue:work`

```bash
# .env
QUEUE_CONNECTION=database

# .env file
DB_CONNECTION=sqlite
```

```php
// config/queue.php
'connections' => [
    'database' => [
        'driver' => 'database',
        'connection' => 'sqlite',
        'table' => 'jobs',
        'retry_after' => 90,
    ],
],
```

#### **Redis Driver** (Recommended for Production)

- Super fast, in-memory
- Best for: high-volume applications, 1000s of jobs/sec
- Requires: Redis server running
- Atomic operations, can't lose jobs

```bash
# .env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 5,
    ],
],
```

**Performance Comparison:**

- Sync: 0.001s per job (blocking)
- Database: 0.01-0.05s per job
- Redis: 0.001-0.005s per job

**Driver Selection:**

- Development: `sync` (instant, visible failures)
- Small app: `database` (simple, no setup)
- Production: `redis` (fast, reliable)
- Critical jobs: `redis` (speed and resilience)

---

### 6. RUNNING QUEUE WORKERS

#### **Start a Worker**

```bash
php artisan queue:work
```

Worker process:

1. Connects to queue
2. Waits for job
3. Pulls job when available
4. Deserializes and executes `handle()`
5. Marks complete
6. Repeats

#### **Worker Configuration Options**

```bash
php artisan queue:work --queue=emails
php artisan queue:work redis --queue=high,default
php artisan queue:work --tries=3
php artisan queue:work --timeout=60
php artisan queue:work --daemon
php artisan queue:work --maxJobs=1000
php artisan queue:work --maxTime=3600
```

**Key Options:**

- `--queue=emails` - Process specific queue(s)
- `--tries=3` - Retry failed jobs 3 times
- `--timeout=60` - Kill job if running >60s
- `--daemon` - Keep running (use Supervisor)
- `--maxJobs=1000` - Stop after 1000 jobs
- `--maxTime=3600` - Stop after 1 hour

#### **Multiple Workers (Process Manager)**

Use **Supervisor** to manage worker processes:

```ini
# /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --queue=default --tries=3
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/laravel-worker.log
```

Start workers:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

Monitor:

```bash
php artisan queue:monitor # View job stats
sudo supervisorctl status  # View worker processes
tail -f /var/log/laravel-worker.log # View logs
```

---

### 7. FAILED JOBS - RETRY AND HANDLING

#### **Job Failure Scenarios**

```php
class SendEmail implements ShouldQueue {
    public $tries = 5;              // Retry 5 times
    public $timeout = 120;          // 2 minute timeout
    public $backoff = [10, 30, 60]; // Wait 10s, 30s, 60s between retries

    public function handle() {
        // If this throws Exception, job fails and retries
        Mail::to($this->user)->send(new WelcomeEmail());
    }

    public function failed(Throwable $exception) {
        // Called when job fails all retries
        Log::error("Email failed: " . $exception->getMessage());
        Notification::route('slack', env('SLACK_WEBHOOK'))
            ->notify(new JobFailed($this->user, $exception));
    }
}
```

**Retry Configuration:**

```php
public $tries = 3;                    // Max attempts
public $timeout = 120;                // Timeout per attempt (seconds)
public $backoff = [10, 60, 300];      // Wait time between retries (seconds)
public $retryAfter = 3600;            // Wait 1 hour before retry
```

#### **Dead Letter Queue (Jobs That Failed Permanently)**

```bash
php artisan queue:failed  # View failed jobs
php artisan queue:retry   # Retry all failed jobs
php artisan queue:retry job-id # Retry specific job
php artisan queue:forget job-id # Delete failed job
```

**Failed Jobs Table:**

```bash
php artisan queue:failed-table
php artisan migrate
```

**Monitor Failed Jobs:**

```bash
php artisan queue:failed # List all failed jobs
# Shows: id, connection, queue, class, failed_at, exception

# Format:
# 1  redis     default  SendEmail           2024-04-22 10:30:00  Error: Connection timeout
```

#### **Handling Different Failure Types**

```php
class ProcessPayment implements ShouldQueue {
    public function handle() {
        try {
            PaymentGateway::charge($this->amount);
        } catch (PaymentDeclined $e) {
            // Don't retry - customer declined, notify them
            $this->user->notify(new PaymentDeclined($e->getMessage()));
            throw $e; // Skip retries
        } catch (TimeoutException $e) {
            // Retry this - temporary network issue
            throw $e; // Will retry
        } catch (ServerException $e) {
            // Retry this - temporary server issue
            throw $e; // Will retry
        }
    }

    public function failed(Throwable $exception) {
        Log::critical("Payment failed permanently: " . $exception);
        // Escalate to support team
    }
}
```

---

### 8. JOB CHAINING

Chain multiple jobs to execute sequentially. If one fails, remaining jobs are skipped.

```php
use Bus;

// Dispatch job chain
Bus::chain([
    new ProcessExamSubmission($exam),
    new GenerateReportPDF($exam),
    new SendResultsEmail($exam),
])->dispatch();

// Or
Bus::chain([
    new ProcessExamSubmission($exam),
    new GenerateReportPDF($exam),
    new SendResultsEmail($exam),
])->onQueue('high-priority')->dispatch();
```

**Execution Flow:**

```
ProcessExamSubmission → (success) → GenerateReportPDF → (success) → SendResultsEmail
                     ↓
                  (failure) → Chain stops, remaining jobs skipped
```

**With Error Handling:**

```php
Bus::chain([
    new ProcessExamSubmission($exam),
    new GenerateReportPDF($exam),
    new SendResultsEmail($exam),
])
->catch(function (Throwable $e) {
    // Handle chain failure
    Log::error("Exam processing chain failed: " . $e->getMessage());
    Notification::send($admin, new ChainFailedNotification($exam, $e));
})
->dispatch();
```

**Use Cases for Chaining:**

- Multi-step workflows (upload → process → store → notify)
- Dependent operations (generate report → send email → log)
- Atomic operations (all succeed or all fail)

---

### 9. DELAYED DISPATCH

Execute jobs at a later time.

```php
// Delay by X seconds
dispatch(new SendWelcomeEmail($user))->delay(60); // 1 minute

// Delay until specific time
dispatch(new SendWelcomeEmail($user))->delay(now()->addHours(2));

// Delay until tomorrow 9 AM
dispatch(new SendWelcomeEmail($user))->delay(now()->tomorrow()->setHour(9));

// Chain with other options
SendWelcomeEmail::dispatch($user)
    ->onQueue('emails')
    ->delay(now()->addMinutes(5))
    ->onConnection('redis');
```

**Use Cases:**

- Send follow-up emails after N days
- Delayed notifications
- Rate limiting (spread jobs over time)
- Schedule batch processing off-peak

**Behind the Scenes:**

```
Delayed Job: available_at = now() + delay
Worker: SELECT * FROM jobs WHERE available_at <= now()
```

- Jobs sit in queue with future `available_at` timestamp
- Worker only picks jobs where `available_at` <= current time
- Doesn't consume resources while waiting

---

## Code Examples

See `code-examples.php` for complete implementation examples.

---

## Resources

- Laravel Queues Documentation: https://laravel.com/docs/queues
- Queue Configuration: https://laravel.com/docs/queues#configuration
- Job Configuration: https://laravel.com/docs/queues#job-configuration
- Supervisor: http://supervisord.org/
- Redis: https://redis.io/

---

## Checkpoint

**Mastery Checklist:**

- [ ] Understand why queues are essential for scaling
- [ ] Created multiple job classes with proper structure
- [ ] Dispatched jobs from controllers and events
- [ ] Used `dispatch()` vs `dispatchAfterResponse()`
- [ ] Configured different queue drivers (sync, database, Redis)
- [ ] Started and managed queue workers
- [ ] Set up job retries with exponential backoff
- [ ] Implemented failed job handling
- [ ] Created job chains for multi-step workflows
- [ ] Used delayed dispatch for scheduled processing
- [ ] Set up Supervisor for production worker management
- [ ] Built a real-world bulk email scenario (1000+ jobs)
- [ ] Monitored failed jobs and recovery
- [ ] Tested job execution and failures
