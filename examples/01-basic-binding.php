<?php

/**
 * Example: Basic binding and resolution.
 */
require_once __DIR__.'/../vendor/autoload.php';

use WebFiori\Container\Container;

// Define interfaces and implementations
interface NotificationChannel {
    public function send(string $to, string $message): void;
}

class EmailChannel implements NotificationChannel {
    public function send(string $to, string $message): void {
        echo "Email to $to: $message\n";
    }
}

class SmsChannel implements NotificationChannel {
    public function send(string $to, string $message): void {
        echo "SMS to $to: $message\n";
    }
}

// Create container and bind
$container = new Container();
$container->bind(NotificationChannel::class, EmailChannel::class);

// Resolve
$channel = $container->make(NotificationChannel::class);
$channel->send('user@example.com', 'Hello!');
// Output: Email to user@example.com: Hello!

// Swap implementation
$container->bind(NotificationChannel::class, SmsChannel::class);
$channel = $container->make(NotificationChannel::class);
$channel->send('+1234567890', 'Hello!');
// Output: SMS to +1234567890: Hello!
