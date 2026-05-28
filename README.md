# WebFiori Container

A lightweight dependency injection container for PHP with automatic constructor resolution.

<p align="center">
  <a href="https://github.com/WebFiori/container/actions"><img src="https://github.com/WebFiori/container/actions/workflows/php84.yaml/badge.svg?branch=main"></a>
  <a href="https://codecov.io/gh/WebFiori/container">
    <img src="https://codecov.io/gh/WebFiori/container/branch/main/graph/badge.svg" />
  </a>
  <a href="https://sonarcloud.io/dashboard?id=WebFiori_container">
      <img src="https://sonarcloud.io/api/project_badges/measure?project=WebFiori_container&metric=alert_status" />
  </a>
  <a href="https://github.com/WebFiori/container/releases">
      <img src="https://img.shields.io/github/release/WebFiori/container.svg?label=latest" />
  </a>
  <a href="https://packagist.org/packages/webfiori/container">
      <img src="https://img.shields.io/packagist/dt/webfiori/container?color=light-green">
  </a>
</p>


## Supported PHP Versions

This library requires **PHP 8.1 or higher**.

|                                                                                        Build Status                                                                                        |
|:------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------:|
| <a target="_blank" href="https://github.com/WebFiori/container/actions/workflows/php81.yaml"><img src="https://github.com/WebFiori/container/actions/workflows/php81.yaml/badge.svg?branch=main"></a>  |
| <a target="_blank" href="https://github.com/WebFiori/container/actions/workflows/php82.yaml"><img src="https://github.com/WebFiori/container/actions/workflows/php82.yaml/badge.svg?branch=main"></a>  |
| <a target="_blank" href="https://github.com/WebFiori/container/actions/workflows/php83.yaml"><img src="https://github.com/WebFiori/container/actions/workflows/php83.yaml/badge.svg?branch=main"></a>  |
| <a target="_blank" href="https://github.com/WebFiori/container/actions/workflows/php84.yaml"><img src="https://github.com/WebFiori/container/actions/workflows/php84.yaml/badge.svg?branch=main"></a>  |

## Features

- **Instance-based API** — fully testable, multiple containers can coexist
- **Static facade** (`ContainerFacade`) for quick usage without DI
- **Auto-resolution** of constructor dependencies via reflection
- **Singleton support** — resolve once, return same instance on subsequent calls
- **Callable factories** for complex construction logic
- **Nullable parameter handling** — resolves to null if dependency is unbound
- **Default value support** — uses parameter defaults for unresolvable scalar types
- **Zero dependencies** — requires only PHP 8.1+

## Installation

```bash
composer require webfiori/container
```

## Usage

### Basic Binding

```php
use WebFiori\Container\Container;

$container = new Container();

// Bind interface to implementation
$container->bind(LoggerInterface::class, FileLogger::class);

$logger = $container->make(LoggerInterface::class);
// Returns a new FileLogger instance
```

### Singletons

```php
$container->singleton(DatabaseConnection::class, MySQLConnection::class);

$a = $container->make(DatabaseConnection::class);
$b = $container->make(DatabaseConnection::class);
// $a === $b (same instance)
```

### Auto-Resolution

The container automatically resolves constructor dependencies:

```php
class UserRepository {
    public function __construct(DatabaseConnection $db) {
        // ...
    }
}

class UserService {
    public function __construct(UserRepository $repo, LoggerInterface $logger) {
        // ...
    }
}

$container->singleton(DatabaseConnection::class, MySQLConnection::class);
$container->bind(LoggerInterface::class, FileLogger::class);

// Automatically resolves UserRepository and LoggerInterface
$service = $container->make(UserService::class);
```

### Callable Factories

```php
$container->bind(CacheInterface::class, function (Container $c) {
    $config = $c->make(Config::class);
    return new RedisCache($config->get('redis.host'), $config->get('redis.port'));
});
```

### Registering Existing Instances

```php
$config = new Config('/path/to/config.json');
$container->instance(Config::class, $config);
```

### Static Facade

```php
use WebFiori\Container\ContainerFacade;

ContainerFacade::singleton(LoggerInterface::class, FileLogger::class);
$logger = ContainerFacade::make(LoggerInterface::class);
```

## API

### `Container`

| Method | Description |
|--------|-------------|
| `bind(string $abstract, string\|callable $concrete)` | Register a binding (new instance each time) |
| `singleton(string $abstract, string\|callable $concrete)` | Register a singleton (same instance each time) |
| `instance(string $abstract, object $instance)` | Register an existing instance |
| `make(string $abstract): object` | Resolve an instance |
| `has(string $abstract): bool` | Check if a binding exists |
| `remove(string $abstract): void` | Remove a binding |
| `reset(): void` | Remove all bindings and instances |

### `ContainerFacade`

Static wrapper around a default `Container` instance. Same methods as `Container` plus:

| Method | Description |
|--------|-------------|
| `getInstance(): Container` | Get the default container |
| `setInstance(Container $container): void` | Replace the default container |
| `reset(): void` | Destroy the default instance |

## Behavior

- **Auto-resolution:** If a class is not explicitly bound, the container attempts to instantiate it by resolving its constructor dependencies recursively.
- **Nullable parameters:** If a dependency cannot be resolved and the parameter is nullable, `null` is used.
- **Default values:** Scalar parameters with default values use those defaults when not resolvable.
- **Exceptions:** `ContainerException` is thrown when a dependency cannot be resolved (non-existent class, abstract class, unresolvable parameter).

## License

MIT
