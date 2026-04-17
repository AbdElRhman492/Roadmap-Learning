<?php

require 'vendor/autoload.php';

use Aws\S3\S3Client;

$root = __DIR__ . '/storage';
$file = 'one/two/three/test.txt';
$contents = 'Hello, World!';
$savePath = "{$root}/{$file}";

if (!is_dir(dirname($savePath))) {
    mkdir(dirname($savePath), 0777, true);
}
file_put_contents($root . '/' . $file, $contents);


// --------------------- Amazon s3
if (class_exists(S3Client::class)) {
    $s3Key = 'fdgerhrhrhh';
    $s3Secret = 'fdgerhrhrhh';
    $s3Bucket = 'my-bucket';

    $s3 = new S3Client([
        'version' => 'latest',
        'region' => 'us-east-1',
        'credentials' => [
            'key' => $s3Key,
            'secret' => $s3Secret,
        ],
    ]);

    try {
        $s3->putObject([
            'Bucket' => $s3Bucket,
            'Key' => $file,
            'Body' => $contents,
        ]);
        echo "File uploaded successfully to S3.\n";
    } catch (Aws\S3\Exception\S3Exception $e) {
        echo "Error uploading file to S3: " . $e->getMessage() . "\n";
    }
} else {
    echo "AWS SDK not available, skipping S3 upload demo.\n";
}

echo "\n\n=== OOP Workshop: Inheritance, Interfaces, Encapsulation, Composition ===\n";

function section(string $title): void
{
    echo "\n-- {$title} --\n";
}

section('Inheritance + Abstract Classes');

abstract class LessonNotification
{
    public function __construct(protected string $message) {}

    abstract public function send(): void;
}

class LessonEmailNotification extends LessonNotification
{
    public function send(): void
    {
        echo "Email notification: {$this->message}\n";
    }
}

class LessonSmsNotification extends LessonNotification
{
    public function send(): void
    {
        echo "SMS notification: {$this->message}\n";
    }
}

(new LessonEmailNotification('New lecture available'))->send();
(new LessonSmsNotification('Reminder: homework deadline'))->send();

section('Interfaces as Feature Filters');

interface CanBePublished
{
    public function publish(): void;
    public function isPublished(): bool;
}

class CoursePost implements CanBePublished
{
    private bool $published = false;

    public function publish(): void
    {
        $this->published = true;
        echo "Course post published\n";
    }

    public function isPublished(): bool
    {
        return $this->published;
    }
}

class PublishAction
{
    public function handle(CanBePublished $model): void
    {
        if ($model->isPublished()) {
            echo "Already published\n";
            return;
        }

        $model->publish();
    }
}

$post = new CoursePost();
$publisher = new PublishAction();
$publisher->handle($post);
$publisher->handle($post);

section('Encapsulation + Visibility');

class Wallet
{
    private float $balance = 0.0;

    public function deposit(float $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Deposit must be positive');
        }

        $this->balance += $amount;
    }

    public function currentBalance(): float
    {
        return $this->balance;
    }
}

$wallet = new Wallet();
$wallet->deposit(150);
echo 'Wallet balance: ' . $wallet->currentBalance() . "\n";

section('Getters and Setters');

class StudentAccount
{
    private string $email;

    public function __construct(string $email)
    {
        $this->setEmail($email);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid student email');
        }

        $this->email = strtolower($email);
    }
}

$student = new StudentAccount('Student@Example.COM');
echo 'Stored email: ' . $student->getEmail() . "\n";

section('From Getters/Setters to Property Hooks');
echo "Property hooks are available in modern PHP versions (8.4+).\n";
echo "If your runtime does not support hooks, keep validated getters/setters like StudentAccount.\n";

section('Object Composition + Abstractions');

interface PaymentGatewayContract
{
    public function charge(float $amount): string;
}

class StripeGateway implements PaymentGatewayContract
{
    public function charge(float $amount): string
    {
        return 'Stripe charged $' . number_format($amount, 2);
    }
}

class FakeGateway implements PaymentGatewayContract
{
    public function charge(float $amount): string
    {
        return 'Fake gateway accepted $' . number_format($amount, 2);
    }
}

class EnrollmentService
{
    public function __construct(private PaymentGatewayContract $gateway) {}

    public function enroll(float $amount): string
    {
        return $this->gateway->charge($amount);
    }
}

$liveEnrollment = new EnrollmentService(new StripeGateway());
echo $liveEnrollment->enroll(299.99) . "\n";

$testEnrollment = new EnrollmentService(new FakeGateway());
echo $testEnrollment->enroll(0) . "\n";
