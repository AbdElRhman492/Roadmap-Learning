# 01 - OOP Pillars

## Understanding

Think about OOP as designing software with **real objects and clear responsibilities**.

- A **class** is a blueprint.
- An **object** is a real instance created from that blueprint.
- Good OOP makes code easier to test, change, and scale.

---

## Classes and Objects

### Class
A class defines:
- data (properties)
- behavior (methods)

```php
class Song
{
	public function __construct(
		public string $name,
		public string $artist,
		public int $year
	) {}
}
```

### Object
An object is the runtime value created from a class.

```php
$song = new Song('Numb', 'Linkin Park', 2003);
echo $song->name; // Numb
```

---

## DTOs (Data Transfer Objects)

A DTO is a simple object used to move data between layers (controller -> service -> repository).

Rules of thumb:
- keep DTOs simple
- avoid business logic inside DTOs
- usually immutable (`readonly`) in modern PHP

```php
final readonly class CreateUserDto
{
	public function __construct(
		public string $name,
		public string $email,
		public string $password
	) {}
}

// Example usage
$dto = new CreateUserDto('Sara', 'sara@example.com', 'secret');
```

Why DTOs help:
- strong structure instead of loose arrays
- better autocomplete and refactoring
- easier validation boundaries

---

## Types in PHP

Use types everywhere possible:
- parameter types
- return types
- property types

```php
class PriceCalculator
{
	public function applyTax(float $amount, float $taxRate): float
	{
		return $amount + ($amount * $taxRate);
	}
}
```

Useful type features:
- union types: `int|string`
- nullable types: `?string`
- mixed should be rare
- `void`, `never`, `static` return types (advanced)

Typed code reduces runtime surprises.

---

## Dependencies and Coupling

### Dependency
A dependency is anything your class needs to do its job.

```php
class OrderService
{
	public function __construct(private PaymentGateway $gateway) {}
}
```

### Coupling
Coupling means how strongly one class depends on another concrete detail.

- **High coupling**: class creates dependencies itself (`new StripeGateway()` inside method).
- **Low coupling**: class receives dependency from outside (constructor injection).

Low coupling gives easier testing and safer changes.

---

## Interfaces and Implementation

### Interface
Interface defines a contract (what must be done).

```php
interface Mailer
{
	public function send(string $to, string $message): void;
}
```

### Implementation
Implementation is the concrete class that fulfills the contract.

```php
class SmtpMailer implements Mailer
{
	public function send(string $to, string $message): void
	{
		// send email through SMTP
	}
}

class LogMailer implements Mailer
{
	public function send(string $to, string $message): void
	{
		// write email info to logs (useful for local/testing)
	}
}
```

Using interface in business logic:

```php
class NewsletterService
{
	public function __construct(private Mailer $mailer) {}

	public function subscribe(string $email): void
	{
		$this->mailer->send($email, 'Welcome to our newsletter');
	}
}
```

This is polymorphism in practice: same method call, different behavior by implementation.

---

## Providers (Service Providers / Factory Providers)

A provider is responsible for giving the correct implementation.

Simple factory-style provider example:

```php
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

$mailer = MailerProvider::make('smtp');
$service = new NewsletterService($mailer);
```

In frameworks like Laravel, service providers register bindings in the container (IoC).

---

## Static Analysis (PHPStan / Psalm)

Static analysis checks code **without running it**.

It catches:
- wrong argument types
- possible null errors
- unknown methods/properties
- dead or unreachable code

Example issue:

```php
function uppercase(string $value): string
{
	return strtoupper($value);
}

$name = null;
echo uppercase($name); // static analyzer warns before runtime
```

Why it matters:
- fewer production bugs
- confidence during refactor
- better team code quality standards

---

## Mental Model (Important)

Use this order while designing:
1. Define clear types and DTOs for data.
2. Define interfaces for behavior contracts.
3. Build implementations.
4. Inject dependencies (do not hardcode concrete classes).
5. Use providers/container to choose implementations.
6. Run static analysis to catch mistakes early.

---

## Common Mistakes

- passing raw arrays everywhere instead of DTOs
- returning `mixed` too often
- creating dependencies with `new` inside business classes
- using concrete classes instead of interfaces in constructors
- giant classes with too many responsibilities

---

## Checkpoint

If you understand this file well, you should be able to:

- create a class and instantiate objects
- design a DTO and type it correctly
- explain dependency vs coupling
- write an interface and two implementations
- connect implementation through a provider
- explain why static analysis is useful




please handle this to make profesional prompt as a senior software developer i need prompt to handle all this

i need to create website for center management and teacher managment it will be separated but connected togeather so the center can buy or subscribe to center version and teacher can also buy or subscribe to teacher version

all teachers in center can has their own version by login in teacher version with theri credentials

center version will be separated to:
stats
branches
moderators with permessions
students (registerd or add)
rooms
types education
school years
school types
educational types

subjects
teachers

allow teacher subscription

reservations, it start at the start of edu year each user enter and auto subscribe to teacher if he picked him into his reservations and make the payment, has seats for each educational type and also school type,same reservation may has diff types like hall with the teacher and hall with TVs so allow it and make it easy to change what the avaliable now and automatic switch when the primary seats finish if i choose that option, i need it to be compelete and very professional cause this is one of our best features so it must be perfect and easy for all users, there is many payment methods allow adding or editing them easily and choose what is available now to show for the users when pay something : (fawateark, cash payments and manual activaion in center, platform credits payments, vf-we-e& wallet payments and manual activation from center)

handle invoices page professionally to easy use and tracking and manual activation and automatic so on

there will be 2 types for attendance ( per lecture, per month - 4 lectures)

choose the best way for center to add their schedule (name, teacher, day,time, pricing so on )
then allow create instances from this lecture for the next weak so on ( i has version i was create the fixed time then use it as a templete to create this weak's lecture and can repeat as much i need)

then the attendance system i need it very easy and speed without complex steps but high performance and professional and compelete (in my version i enter into the lecture created with templete then start searching for users and assign as attended) but this take too much time because there is a confirmation model with many fields so i need it to be easy and fast

attendance may has prerequesits so respect it and make it easy to create prerequesite as required subscription, prev lecture or spec exam grade make it easy to add or edit

users may be fixed types for each subject it may be attend this subject for free or free in general in all subjects or has discount in spec subject or in general and take this into your algorithm when set this user attend and finance

also allow reporting user attendances , the diff between lectures up to n lectures so i can see each user attend what and absent for what

also allow blocking or freeze or warn and reason for any of them

let's go into finance: each leacture has it's price contain (center earning, teacher earning, moderator earning) all as percentages from the total price or it can be fixed amount not percentage so allow both

i need to get report for each lecture after taking attendance and mark as finished contain the total and center,teacher,modertor totals and if free users attended show them as list to know them also if discounted show them in the report and allow printing

handle notification system for updates and any thing allow push notifications

I want to create a comprehensive educational system, a complete ecosystem serving all stakeholders: students, teachers, centers, and parents, from the beginning to the end of the academic year, all in one place.

The goal is for the system to be user-friendly, robust, and comprehensive, covering all needs to the point that:

• Centers can rely on it.

• Teachers can use it comfortably.

• Students can understand it.

• Parents can't do without it.

General Idea

I have previous experience and have developed two systems that are already operational in multiple centers.
But this time, I want to take it to a higher level and cover the entire market professionally.

Parent (Key Strength)

Parents will be connected to the system in multiple ways:

• A dashboard on the website (children's data + charts + reports)

• A simple and user-friendly mobile application

• Notifications via all methods:

(Website – Mobile – SMS – WhatsApp – Telegram)

They will receive:

• Attendance and absence records

• Grades

• Alerts

• Group messages when needed

Student

A single dashboard where they can view:

• Their data

• Their schedule

• Their status
Everything in a simple and clear way.

The system itself is divided into 3 parts:

1. Course Registration System
Students register and pay online from home at the beginning of the term.

2. Center Management System
Attendance and absence records – Finances – Student tracking – Teacher accounts.

3. Teacher System
Teachers register the centers where they work, and students appear automatically. They can manage attendance and various subscriptions (per session/month).

All of this is directly related to the guardian.

---

## OOP Deep Dive Add-On (Appendix)

This section extends your current OOP pillars with practical patterns used in real projects.

### 1) Inheritance and Abstract Classes

Inheritance lets a child class reuse and specialize behavior from a parent class.

- Use inheritance for **is-a** relationships.
- Avoid deep inheritance trees (harder to maintain).
- Prefer composition when behavior can change often.

```php
abstract class Notification
{
	public function __construct(protected string $message) {}

	abstract public function send(): void;
}

class EmailNotification extends Notification
{
	public function send(): void
	{
		echo "Email: {$this->message}";
	}
}
```

Why `abstract`?
- You can force a contract (`send`) while sharing common fields/logic (`$message`).
- You prevent direct creation of incomplete base classes.

---

### 2) Interfaces as Feature Filters

Think of interfaces as capability flags.
If a class implements the interface, your service knows that feature is available.

```php
interface CanBeLiked
{
	public function like(): void;
	public function isLiked(): bool;
}

class LikeAction
{
	public function handle(CanBeLiked $model): void
	{
		if (! $model->isLiked()) {
			$model->like();
		}
	}
}
```

Benefits:
- safer than checking random method names at runtime
- clean polymorphism
- easy testing with mocks/fakes

---

### 3) Encapsulation and Visibility

Encapsulation means protecting object state and controlling how it changes.

Visibility levels:
- `public`: accessible from anywhere
- `protected`: inside class + children
- `private`: inside the class only

```php
class BankAccount
{
	private float $balance = 0;

	public function deposit(float $amount): void
	{
		if ($amount <= 0) {
			throw new InvalidArgumentException('Amount must be positive');
		}

		$this->balance += $amount;
	}

	public function balance(): float
	{
		return $this->balance;
	}
}
```

Rule: keep sensitive state private and expose valid operations.

---

### 4) Getters and Setters

Getters and setters are useful when a property needs rules.

```php
class User
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
		if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
			throw new InvalidArgumentException('Invalid email');
		}

		$this->email = strtolower($email);
	}
}
```

Use setters only if mutation is valid for your domain.
If object must not change, prefer immutable/read-only design.

---

### 5) From Getters/Setters to Property Hooks

Property hooks (newer PHP syntax) can move validation/transformation near property access.

```php
class Profile
{
	public private(set) string $email {
		set {
			if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
				throw new InvalidArgumentException('Invalid email');
			}
			$this->email = strtolower($value);
		}
	}

	public function __construct(string $email)
	{
		$this->email = $email;
	}
}
```

Practical guidance:
- If your team/runtime is not on a hooks-supported PHP version, keep classic getters/setters.
- Choose one style per project/module for consistency.

---

### 6) Understanding Object Composition and Abstractions

Composition means building behavior by combining objects instead of inheriting everything.

- Inheritance: **is-a** (`Dog` is an `Animal`)
- Composition: **has-a** (`OrderService` has a `PaymentGateway`)

```php
interface BillingGateway
{
	public function charge(float $amount): string;
}

class StripeGateway implements BillingGateway
{
	public function charge(float $amount): string
	{
		return "Stripe charged {$amount}";
	}
}

class SubscriptionService
{
	public function __construct(private BillingGateway $gateway) {}

	public function subscribe(float $amount): string
	{
		return $this->gateway->charge($amount);
	}
}
```

This gives:
- low coupling
- easier replacement (Stripe/PayPal/Fake)
- better tests and cleaner architecture

---

## Quick Self-Check (Advanced)

You should now be able to:
- pick inheritance vs composition correctly
- design abstract base classes with clear extension points
- use interfaces as feature contracts
- protect state with visibility + encapsulation
- choose between setters and property hooks based on PHP version/team standards
- inject abstractions instead of concrete dependencies

