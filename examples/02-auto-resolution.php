<?php

/**
 * Example: Auto-resolution of constructor dependencies.
 */
require_once __DIR__.'/../vendor/autoload.php';

use WebFiori\Container\Container;

// Define classes with dependencies
interface Logger {
    public function info(string $msg): void;
}

class ConsoleLogger implements Logger {
    public function info(string $msg): void {
        echo "[INFO] $msg\n";
    }
}

class UserRepository {
    private Logger $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    public function findById(int $id): array {
        $this->logger->info("Finding user $id");

        return ['id' => $id, 'name' => 'John'];
    }
}

class UserService {
    private UserRepository $repo;

    public function __construct(UserRepository $repo) {
        $this->repo = $repo;
    }

    public function getUser(int $id): array {
        return $this->repo->findById($id);
    }
}

// Only bind the interface — concrete classes are auto-resolved
$container = new Container();
$container->bind(Logger::class, ConsoleLogger::class);

// Container resolves: UserService → UserRepository → Logger (ConsoleLogger)
$service = $container->make(UserService::class);
$user = $service->getUser(42);
// Output: [INFO] Finding user 42

print_r($user);
// Output: Array ( [id] => 42 [name] => John )
