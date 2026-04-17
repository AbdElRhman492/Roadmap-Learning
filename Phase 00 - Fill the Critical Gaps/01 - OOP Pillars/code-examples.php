<?php

declare(strict_types=1);

// 01 - OOP Pillars - runnable examples matching notes.md

function printSection(string $title): void
{
    echo "\n=== {$title} ===\n";
}

// Classes and Objects
class Song
{
    public function __construct(
        public string $name,
        public string $artist,
        public int $year
    ) {}
}

// DTO (Data Transfer Object)
final readonly class CreateUserDto
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password
    ) {}
}

// Types example
class PriceCalculator
{
    public function applyTax(float $amount, float $taxRate): float
    {
        return $amount + ($amount * $taxRate);
    }
}

// Dependencies example
interface PaymentGateway
{
    public function charge(float $amount): string;
}

class FakePaymentGateway implements PaymentGateway
{
    public function charge(float $amount): string
    {
        return "Charged $" . number_format($amount, 2);
    }
}

class OrderService
{
    public function __construct(private PaymentGateway $gateway) {}

    public function checkout(float $amount): string
    {
        return $this->gateway->charge($amount);
    }
}

// Interface and implementations
interface Mailer
{
    public function send(string $to, string $message): void;
}

class SmtpMailer implements Mailer
{
    public function send(string $to, string $message): void
    {
        echo "SMTP -> {$to}: {$message}\n";
    }
}

class LogMailer implements Mailer
{
    public function send(string $to, string $message): void
    {
        echo "LOG -> {$to}: {$message}\n";
    }
}

// High coupling: tightly bound to one concrete implementation.
class BadNewsletterService
{
    private SmtpMailer $mailer;

    public function __construct()
    {
        $this->mailer = new SmtpMailer();
    }

    public function subscribe(string $email): void
    {
        $this->mailer->send($email, 'Welcome (bad coupling example)');
    }
}

// Low coupling: depends on interface, so implementation can be swapped.
class NewsletterService
{
    public function __construct(private Mailer $mailer) {}

    public function subscribe(string $email): void
    {
        $this->mailer->send($email, 'Welcome to our newsletter');
    }
}

// Provider example
class MailerProvider
{
    public static function make(string $driver): Mailer
    {
        return match ($driver) {
            'smtp' => new SmtpMailer(),
            'log' => new LogMailer(),
            default => throw new InvalidArgumentException('Unsupported mail driver'),
        };
    }
}

// Static analysis example
function uppercase(string $value): string
{
    return strtoupper($value);
}

function runDemo(): void
{
    printSection('Classes and Objects');
    $song = new Song('Numb', 'Linkin Park', 2003);
    echo "Song: {$song->name} by {$song->artist} ({$song->year})\n";

    printSection('DTOs');
    $dto = new CreateUserDto('Sara', 'sara@example.com', 'secret');
    echo "DTO: {$dto->name} / {$dto->email}\n";

    printSection('Types');
    $calculator = new PriceCalculator();
    echo 'Taxed total: ' . $calculator->applyTax(100.00, 0.15) . "\n";

    printSection('Dependencies');
    $orderService = new OrderService(new FakePaymentGateway());
    echo $orderService->checkout(49.99) . "\n";

    printSection('Coupling');
    $bad = new BadNewsletterService();
    $bad->subscribe('hardcoded@example.com');

    printSection('Interfaces, Implementation, Providers');
    $mailer = MailerProvider::make('log');
    $newsletter = new NewsletterService($mailer);
    $newsletter->subscribe('student@example.com');

    printSection('Static Analysis');
    echo uppercase('php oop') . "\n";
    // $name = null;
    // echo uppercase($name);
    // A static analyzer (PHPStan/Psalm) warns here before runtime.
}

runDemo();

// --------------------------------- New Lessons
class Notification
{
    public function __construct(public string $message)
    {
        //
    }
    public function send()
    {
        echo 'Show pop up flash message';
    }
}

class EmailNotification extends Notification
{
    public function send()
    {
        echo 'Send email notification';
    }
}

class OSNotification extends Notification
{
    public function send()
    {
        echo 'Send OS notification';
    }
}

$notification = new EmailNotification('New user registered');
echo $notification->message . "\n";
$notification->send();

class User {}

abstract class Achievement
{
    public function __construct(
        public string $name,
        public string $description,
        public string $icon
    ) {}

    abstract public function qualifier(User $user);
}

class FirstPostAchievement extends Achievement
{
    public function qualifier(User $user)
    {
        // $user->posts->count() > 0
        return true;
    }
}

$firstpost = new FirstPostAchievement(
    'First Post',
    'Awarded for creating your first post',
    'firstpost.png'
);

echo $firstpost->qualifier(new User()) ? 'Awarded' : 'Not Awarded';

interface CanBeLiked
{
    public function like();
    public function isLiked();
}

class Comment implements CanBeLiked
{
    public function like()
    {
        echo 'Like comment';
    }

    public function isLiked()
    {
        return false;
    }
}

class Post implements CanBeLiked
{
    public function like()
    {
        echo 'Like comment';
    }

    public function isLiked()
    {
        return false;
    }
}

class PerformLikeAction
{
    public function handle(CanBeLiked $model)
    {
        if ($model->isLiked()) {
            echo 'Already liked';
            return;
        }

        $model->like();
    }
}

(new PerformLikeAction())->handle(new Comment());
(new PerformLikeAction())->handle(new Post());

class Example
{
    protected string $name;
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}

$ex = new Example('Sara');
echo $ex->getName();


class Example2
{
    public string $name;
    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

$userEx2 = new Example2('Sara');
echo $userEx2->name;

class Example3
{
    public function __construct(private string $email) {}

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail(string $email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->email = $email;
        } else {
            throw new InvalidArgumentException('Invalid email address');
        }
    }
}

$userEx3 = new Example3('Sara');
$userEx3->setEmail('test@em.com');
echo $userEx3->getEmail();


class Example4
{
    public private(set) string $email {
        get => str_replace('@', '(at)', $this->email);
        set {
            if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email address');
            }

            $this->email = $value;
        }
    }
    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function updateEmail(string $email)
    {
        $this->email = $email;
    }
}

$userEx4 = new Example4('Sara@gmail.com');
$userEx4->updateEmail('aaa@gmail.com');
echo $userEx4->email;

trait Billable
{
    protected function getStripeCustomer() {}

    protected function getStripeSubscription() {}
}

interface BillingPortal
{
    public function getCustomer();

    public function getSubscription();
}

class StripeBillingPortal implements BillingPortal
{
    public function getCustomer() {}

    public function getSubscription() {}
}

class PayPalBillingPortal implements BillingPortal
{
    public function getCustomer() {}

    public function getSubscription() {}
}

class Subscription
{
    use Billable;

    //    protected StripeBillingPortal $stripeBillingPortal;

    //    public function __construct()
    //    {
    //        $this->stripeBillingPortal = new StripeBillingPortal();
    //    }

    public function __construct(
        protected BillingPortal $billingPortal
    ) {}

    public function create()
    {
        //        $this->getStripeCustomer(); FROM BILLABLE TRAIT
        $this->billingPortal->getCustomer();
    }

    public function cancel() {}

    public function swap(string $newPlan) {}

    public function invoice() {}
}

$stripeSubscription = new Subscription(new StripeBillingPortal());
$stripeSubscription->create();

$paypalSubscription = new Subscription(new PayPalBillingPortal());
$paypalSubscription->create();
