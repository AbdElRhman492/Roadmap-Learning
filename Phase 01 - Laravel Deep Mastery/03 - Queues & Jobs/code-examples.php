<?php

// 03 - Queues & Jobs
// Comprehensive code examples for queues and background job processing

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Throwable;

// ============================================================================
// 1. BASIC JOB STRUCTURE
// ============================================================================

/**
 * Basic Job
 * php artisan make:job SendWelcomeEmail
 */
class SendWelcomeEmail implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, SerializesModels;

    private $user;

    /**
     * Constructor receives dependencies
     * Will be serialized and passed to worker
     */
    public function __construct($user) {
        $this->user = $user;
    }

    /**
     * Execute the job
     * Called by worker process
     */
    public function handle() {
        Mail::to($this->user->email)->send(new \App\Mail\WelcomeEmail($this->user));
        Log::info("Welcome email sent to: {$this->user->email}");
    }
}

// ============================================================================
// 2. JOB WITH CONFIGURATION
// ============================================================================

class SendExamResults implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, SerializesModels;

    // Queue configuration
    public $queue = 'emails';           // Queue name
    public $connection = 'redis';       // Queue driver
    public $tries = 5;                  // Max retry attempts
    public $timeout = 120;              // Timeout in seconds
    public $backoff = [10, 30, 60, 300, 900]; // Wait between retries (seconds)
    public $retryAfter = 3600;          // Wait 1 hour before first retry

    private $student;

    public function __construct($student) {
        $this->student = $student;
    }

    public function handle() {
        // Simulate sending email
        Mail::to($this->student->email)
            ->send(new \App\Mail\ExamResults($this->student));

        Log::info("Exam results sent to: {$this->student->email}");
    }

    /**
     * Called when job fails permanently (all retries exhausted)
     */
    public function failed(Throwable $exception) {
        Log::error("Failed to send exam results to {$this->student->email}: {$exception->getMessage()}");
        
        // Notify admin
        $this->student->notify(new \App\Notifications\ExamResultsFailedNotification());
    }
}

// ============================================================================
// 3. JOB WITH DYNAMIC BACKOFF
// ============================================================================

class ProcessPayment implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    private $order;
    private $amount;

    public function __construct($order) {
        $this->order = $order;
        $this->amount = $order->total;
    }

    /**
     * Dynamic backoff - called before each retry
     */
    public function backoff(): array {
        return [
            // Retry after 10s on first failure
            // Retry after 1 minute on second failure
            // Retry after 5 minutes on third failure
            10, 60, 300
        ];
    }

    /**
     * Determine if job should be retried
     * Can override default retry behavior
     */
    public function shouldRetry(Throwable $exception): bool {
        // Don't retry if payment declined
        if ($exception instanceof PaymentDeclinedException) {
            return false;
        }

        // Retry for temporary errors
        return $exception instanceof NetworkException;
    }

    public function handle() {
        try {
            \App\Services\PaymentGateway::charge($this->order, $this->amount);
            Log::info("Payment processed for order: {$this->order->id}");
        } catch (PaymentDeclinedException $e) {
            // Don't retry - customer declined
            Log::warning("Payment declined for order: {$this->order->id}");
            throw $e;
        } catch (NetworkException $e) {
            // Will retry
            Log::warning("Network error processing payment: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(Throwable $exception) {
        Log::critical("Payment permanently failed for order: {$this->order->id}");
    }
}

// ============================================================================
// 4. BULK OPERATIONS - CRITICAL USE CASE
// ============================================================================

/**
 * Job: Send email to single student (light task)
 */
class SendBulkExamResultsToStudent implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public $queue = 'emails';
    public $tries = 5;
    public $backoff = [10, 30, 60];

    private $student;

    public function __construct($student) {
        $this->student = $student;
    }

    public function handle() {
        Mail::to($this->student->email)
            ->send(new \App\Mail\ExamResults($this->student));
    }

    public function failed(Throwable $exception) {
        Log::error("Failed sending results to {$this->student->email}");
    }
}

/**
 * REAL USE CASE: Bulk email to 60,000 students
 */
class BulkEmailController {
    /**
     * Send exam results to all students
     * Dispatch to queue - returns immediately
     */
    public function sendExamResultsToAll() {
        $students = \App\Models\Student::all(); // 60,000 students

        foreach ($students as $student) {
            // Each job goes to queue - doesn't block
            SendBulkExamResultsToStudent::dispatch($student)
                ->onQueue('emails')
                ->onConnection('redis');
        }

        // Response sent immediately while jobs process in background
        return response()->json(['message' => '60000 emails queued']);
        // With 5 workers: 60000 ÷ 5 × 200ms/email ≈ 40 minutes to complete
    }

    /**
     * More efficient: batch processing
     * Process chunks of students per job
     */
    public function sendExamResultsInBatches() {
        $students = \App\Models\Student::all();

        foreach ($students->chunk(100) as $chunk) {
            SendExamResultsBatch::dispatch($chunk)
                ->onQueue('bulk-emails')
                ->onConnection('redis');
        }

        return response()->json(['message' => '600 batch jobs queued']);
    }
}

class SendExamResultsBatch implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public $queue = 'bulk-emails';
    public $timeout = 600; // 10 minutes for batch

    private $students;

    public function __construct($students) {
        $this->students = $students;
    }

    public function handle() {
        // Send to 100 students at once
        foreach ($this->students as $student) {
            Mail::to($student->email)
                ->send(new \App\Mail\ExamResults($student));
        }
    }
}

// ============================================================================
// 5. DISPATCHING JOBS - Basic & Advanced
// ============================================================================

class DispatchingExamples {
    public function basicDispatching() {
        // Simple dispatch - fire and forget
        dispatch(new SendWelcomeEmail($user));

        // Returns immediately, doesn't wait for execution
    }

    public function dispatchAfterResponse() {
        // Dispatch after HTTP response sent to client
        dispatchAfterResponse(new SendWelcomeEmail($user));

        // Reduces client latency for non-critical tasks
    }

    public function dispatchWithConfiguration() {
        // Configure queue, delay, connection
        dispatch(new SendWelcomeEmail($user))
            ->onQueue('emails')
            ->delay(60)                    // 1 minute delay
            ->onConnection('redis');       // Use Redis driver

        // Or using the job's static method
        SendWelcomeEmail::dispatch($user)
            ->onQueue('high-priority')
            ->delay(now()->addHours(1));
    }

    public function dispatchMultipleJobs() {
        $users = \App\Models\User::all();

        foreach ($users as $user) {
            dispatch(new SendWelcomeEmail($user))->onQueue('emails');
        }
    }

    public function conditionalDispatch() {
        if ($this->shouldSendEmail()) {
            dispatch(new SendWelcomeEmail($user))->onQueue('emails');
        } else {
            dispatch(new SendWelcomeEmail($user))
                ->delay(now()->addDays(1))
                ->onQueue('delayed-emails');
        }
    }
}

// ============================================================================
// 6. QUEUE DRIVERS - Configuration & Usage
// ============================================================================

/**
 * SYNC DRIVER (Synchronous)
 * NO QUEUEING - executes immediately
 * Development only
 */
// .env
// QUEUE_CONNECTION=sync

// config/queue.php
/*
'connections' => [
    'sync' => [
        'driver' => 'sync',
    ],
],
*/

// Usage: Jobs execute immediately in same process
class SyncExample {
    public function synchronousExecution() {
        // This BLOCKS until done (no background processing)
        dispatch(new SendWelcomeEmail($user));
        // Email sent before function returns

        // Don't use in production!
    }
}

/**
 * DATABASE DRIVER
 * Jobs stored in database table
 * Small/medium applications
 */
// Create jobs table
// php artisan queue:table
// php artisan migrate

// .env
// QUEUE_CONNECTION=database

// config/queue.php
/*
'connections' => [
    'database' => [
        'driver' => 'database',
        'connection' => 'sqlite',
        'table' => 'jobs',
        'retry_after' => 90,
    ],
],
*/

// Start worker
// php artisan queue:work

class DatabaseExample {
    public function setup() {
        // 1. Create jobs table
        // php artisan queue:table

        // 2. Run migration
        // php artisan migrate

        // 3. Start worker (foreground)
        // php artisan queue:work

        // 4. Or with options
        // php artisan queue:work --queue=default --tries=3
    }
}

/**
 * REDIS DRIVER
 * Super fast, in-memory queue
 * Production recommended
 */
// Requires Redis running: redis-server

// .env
/*
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
*/

// config/queue.php
/*
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 5,
    ],
],
*/

// Start worker
// php artisan queue:work redis

class RedisExample {
    public function setup() {
        // 1. Install Redis
        // brew install redis (macOS)
        // apt-get install redis-server (Linux)

        // 2. Start Redis
        // redis-server

        // 3. Verify connection
        // redis-cli ping  # Should return PONG

        // 4. Update .env
        // QUEUE_CONNECTION=redis

        // 5. Start worker
        // php artisan queue:work redis
    }
}

// ============================================================================
// 7. RUNNING QUEUE WORKERS
// ============================================================================

class WorkerExamples {
    public function basicWorker() {
        // php artisan queue:work
        // Starts worker listening to default queue
    }

    public function workerWithOptions() {
        // Listen to specific queue
        // php artisan queue:work --queue=emails

        // Listen to multiple queues (priority order)
        // php artisan queue:work --queue=high,default,low

        // Max retry attempts
        // php artisan queue:work --tries=3

        // Kill job if running longer than 60 seconds
        // php artisan queue:work --timeout=60

        // Run as daemon (keep running, use Supervisor)
        // php artisan queue:work --daemon

        // Stop after processing 1000 jobs
        // php artisan queue:work --maxJobs=1000

        // Stop after running 1 hour
        // php artisan queue:work --maxTime=3600

        // Using Redis driver
        // php artisan queue:work redis --queue=emails

        // Combined options
        // php artisan queue:work redis --queue=high,default --tries=5 --timeout=120
    }

    public function monitorWorkers() {
        // Monitor job stats
        // php artisan queue:monitor

        // List failed jobs
        // php artisan queue:failed

        // View job info
        // php artisan queue:work --verbose
    }
}

/**
 * Supervisor Configuration for Production
 * File: /etc/supervisor/conf.d/laravel-worker.conf
 */
/*
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/user/laravel/artisan queue:work redis --queue=default --tries=3
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/laravel-worker.log
stopasgroup=true
killasgroup=true

Manage with:
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
sudo supervisorctl status
*/

// ============================================================================
// 8. FAILED JOBS - RETRY & HANDLING
// ============================================================================

class FailedJobHandling implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public $tries = 5;
    public $timeout = 60;
    public $backoff = [10, 30, 60];

    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Handle the job
     * If this throws exception, job fails and retries
     */
    public function handle() {
        // Simulate work that might fail
        $result = \App\Services\ExternalAPI::process($this->data);

        if (!$result) {
            throw new \Exception('Failed to process data');
        }

        Log::info('Job succeeded');
    }

    /**
     * Determine if job should be retried
     * Called when exception thrown
     */
    public function shouldRetry(Throwable $exception): bool {
        // Don't retry validation errors
        if ($exception instanceof \InvalidArgumentException) {
            return false;
        }

        // Retry network errors
        return true;
    }

    /**
     * Get number of seconds before retry
     */
    public function backoff(): array {
        return [10, 60, 300]; // 10s, 1m, 5m
    }

    /**
     * Called when job fails permanently (all retries exhausted)
     */
    public function failed(Throwable $exception) {
        Log::critical("Job permanently failed: " . $exception->getMessage());

        // Send alert
        \App\Models\User::admins()->each(function ($admin) use ($exception) {
            $admin->notify(new \App\Notifications\JobFailedNotification($exception));
        });

        // Store in database for investigation
        \App\Models\FailedJob::create([
            'job_class' => static::class,
            'data' => json_encode($this->data),
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

/**
 * Failed Jobs Management Commands
 */
class FailedJobsManagement {
    public function commands() {
        // List all failed jobs
        // php artisan queue:failed

        // Retry all failed jobs
        // php artisan queue:retry

        // Retry specific failed job
        // php artisan queue:retry 1

        // Delete specific failed job
        // php artisan queue:forget 1

        // Flush all failed jobs
        // php artisan queue:flush

        // Monitor failed jobs
        // php artisan queue:monitor failed_jobs
    }
}

// ============================================================================
// 9. JOB CHAINING - Multi-step Workflows
// ============================================================================

class GenerateReportPDF implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    private $exam;

    public function __construct($exam) {
        $this->exam = $exam;
    }

    public function handle() {
        Log::info("Generating report for exam: {$this->exam->id}");
        // Generate PDF
        $this->exam->update(['report_generated' => true]);
    }
}

class SendReportEmail implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, SerializesModels;

    private $exam;

    public function __construct($exam) {
        $this->exam = $exam;
    }

    public function handle() {
        Log::info("Sending report email for exam: {$this->exam->id}");
        Mail::to($this->exam->instructor->email)
            ->send(new \App\Mail\ExamReport($this->exam));
    }
}

class ChainingExamples {
    public function basicChaining() {
        use Illuminate\Support\Facades\Bus;

        // Execute jobs sequentially
        Bus::chain([
            new ProcessExamSubmission($exam),
            new GenerateReportPDF($exam),
            new SendReportEmail($exam),
        ])->dispatch();

        // If ProcessExamSubmission succeeds:
        // → GenerateReportPDF executes
        // If GenerateReportPDF succeeds:
        // → SendReportEmail executes
        // If any job fails:
        // → Remaining jobs skipped
    }

    public function chainingWithConfiguration() {
        use Illuminate\Support\Facades\Bus;

        Bus::chain([
            new ProcessExamSubmission($exam),
            new GenerateReportPDF($exam),
            new SendReportEmail($exam),
        ])
        ->onQueue('high-priority')
        ->onConnection('redis')
        ->dispatch();
    }

    public function chainingWithErrorHandling() {
        use Illuminate\Support\Facades\Bus;

        Bus::chain([
            new ProcessExamSubmission($exam),
            new GenerateReportPDF($exam),
            new SendReportEmail($exam),
        ])
        ->catch(function (Throwable $e) {
            Log::critical("Exam processing chain failed: " . $e->getMessage());

            // Notify admin
            \App\Models\User::admins()->each(function ($admin) use ($exam, $e) {
                $admin->notify(
                    new \App\Notifications\ExamChainFailedNotification($exam, $e)
                );
            });

            // Mark exam as failed
            $exam->update(['status' => 'failed']);
        })
        ->dispatch();
    }

    public function complexChain() {
        use Illuminate\Support\Facades\Bus;

        // Multi-step workflow
        Bus::chain([
            new ValidateExamData($exam),
            new ProcessSubmissions($exam),
            new GenerateStatistics($exam),
            new GenerateReportPDF($exam),
            new SendReportEmail($exam),
            new LogCompletion($exam),
        ])
        ->onQueue('critical')
        ->catch(fn($e) => Log::error("Chain failed: {$e->getMessage()}"))
        ->dispatch();
    }
}

// ============================================================================
// 10. DELAYED DISPATCH - Scheduled Processing
// ============================================================================

class DelayedDispatchExamples {
    public function delayBySeconds() {
        // Delay 1 minute
        dispatch(new SendFollowUpEmail($user))
            ->delay(60);

        // Delay 1 hour
        dispatch(new SendFollowUpEmail($user))
            ->delay(3600);
    }

    public function delayUntilTime() {
        // Delay until specific time
        dispatch(new SendFollowUpEmail($user))
            ->delay(now()->addMinutes(5));

        // Delay until tomorrow at 9 AM
        dispatch(new SendFollowUpEmail($user))
            ->delay(now()->tomorrow()->setHour(9));

        // Delay until next Monday
        dispatch(new SendFollowUpEmail($user))
            ->delay(now()->next(\Carbon\Carbon::MONDAY)->startOfDay());
    }

    public function delayWithOtherConfig() {
        dispatch(new SendFollowUpEmail($user))
            ->onQueue('delayed-emails')
            ->delay(now()->addDays(3))
            ->onConnection('redis');
    }

    public function delayRealWorldUseCases() {
        $user = \App\Models\User::find(1);

        // Follow-up email after 1 week
        dispatch(new SendFollowUpEmail($user))
            ->delay(now()->addWeek());

        // Reminder after 1 day
        dispatch(new SendReminderEmail($user))
            ->delay(now()->addDay());

        // Re-engagement after 30 days
        dispatch(new SendReEngagementEmail($user))
            ->delay(now()->addDays(30));

        // Premium expiry reminder
        dispatch(new SendPremiumExpiryReminder($user))
            ->delay(now()->addDays(5));

        // Newsletter scheduled for tomorrow 9 AM
        dispatch(new SendNewsletter($users))
            ->delay(now()->tomorrow()->setHour(9))
            ->onQueue('newsletters');
    }
}

// ============================================================================
// 11. PRODUCTION PATTERNS - Best Practices
// ============================================================================

/**
 * Production-Ready Job Template
 */
class ProductionJobTemplate implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, SerializesModels;

    // Configuration
    public $queue = 'default';
    public $connection = 'redis';
    public $tries = 5;
    public $timeout = 120;
    public $backoff = [10, 30, 60, 300, 900];

    // Job data
    private $entity;
    private $context;

    public function __construct($entity, $context = []) {
        $this->entity = $entity;
        $this->context = $context;
    }

    /**
     * Main handler
     */
    public function handle() {
        try {
            Log::info("Starting job", [
                'job' => static::class,
                'entity_id' => $this->entity->id,
                'context' => $this->context,
            ]);

            // Do work
            $result = $this->doWork();

            Log::info("Job completed successfully", [
                'job' => static::class,
                'result' => $result,
            ]);

        } catch (Throwable $e) {
            Log::error("Job error (attempt {$this->attempts()})", [
                'job' => static::class,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Will retry
        }
    }

    /**
     * Actual work
     */
    private function doWork() {
        // Implement business logic
        return true;
    }

    /**
     * Dynamic retry logic
     */
    public function shouldRetry(Throwable $exception): bool {
        // Permanent failures - don't retry
        if ($exception instanceof \InvalidArgumentException) {
            return false;
        }

        // Only retry 3 times max on critical failures
        if ($exception instanceof \RuntimeException && $this->attempts() >= 3) {
            return false;
        }

        return true;
    }

    /**
     * Failure handler
     */
    public function failed(Throwable $exception) {
        Log::critical("Job permanently failed", [
            'job' => static::class,
            'entity_id' => $this->entity->id,
            'error' => $exception->getMessage(),
        ]);

        // Notify stakeholders
        \Notification::route('slack', env('SLACK_WEBHOOK_CRITICAL'))
            ->notify(new \App\Notifications\CriticalJobFailure($this->entity, $exception));
    }
}

/**
 * Queue Health Monitoring
 */
class QueueMonitoring {
    public function monitoringCommands() {
        // Monitor queue stats
        // php artisan queue:monitor

        // Monitor specific queues
        // php artisan queue:monitor emails,high,default

        // List all failed jobs with details
        // php artisan queue:failed

        // Real-time job monitoring
        // watch -n 1 "php artisan queue:monitor"
    }

    public function customMonitoring() {
        // Check queue sizes
        $defaultCount = \Illuminate\Support\Facades\Queue::driver('redis')->size();
        $emailsCount = \Illuminate\Support\Facades\Queue::driver('redis')->size('emails');

        Log::info("Queue sizes", [
            'default' => $defaultCount,
            'emails' => $emailsCount,
        ]);
    }
}

