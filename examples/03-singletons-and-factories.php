<?php

/**
 * Example: Singletons and callable factories.
 */
require_once __DIR__.'/../vendor/autoload.php';

use WebFiori\Container\Container;

class DatabaseConnection {
    public int $id;
    private static int $instanceCount = 0;

    public function __construct() {
        self::$instanceCount++;
        $this->id = self::$instanceCount;
        echo "DatabaseConnection #{$this->id} created\n";
    }
}

class Cache {
    public string $driver;

    public function __construct(string $driver = 'file') {
        $this->driver = $driver;
    }
}

$container = new Container();

// Singleton: same instance every time
$container->singleton(DatabaseConnection::class, DatabaseConnection::class);

$db1 = $container->make(DatabaseConnection::class);
$db2 = $container->make(DatabaseConnection::class);
echo "Same instance? ".($db1 === $db2 ? 'Yes' : 'No')."\n";
// Output:
// DatabaseConnection #1 created
// Same instance? Yes

// Callable factory: custom construction logic
$container->bind(Cache::class, function (Container $c) {
    return new Cache('redis');
});

$cache = $container->make(Cache::class);
echo "Cache driver: {$cache->driver}\n";
// Output: Cache driver: redis
