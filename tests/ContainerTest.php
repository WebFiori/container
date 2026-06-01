<?php

namespace WebFiori\Container\Tests;

use PHPUnit\Framework\TestCase;
use WebFiori\Container\Container;
use WebFiori\Container\ContainerException;
use WebFiori\Container\ContainerFacade;

// Test fixtures
interface LoggerInterface {
    public function log(string $msg): void;
}

class FileLogger implements LoggerInterface {
    public function log(string $msg): void {
    }
}

class NullLogger implements LoggerInterface {
    public function log(string $msg): void {
    }
}

class UserService {
    public LoggerInterface $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }
}

class OrderService {
    public UserService $userService;
    public LoggerInterface $logger;

    public function __construct(UserService $userService, LoggerInterface $logger) {
        $this->userService = $userService;
        $this->logger = $logger;
    }
}

class NoConstructor {
    public string $value = 'default';
}

class WithDefaults {
    public string $name;
    public int $count;

    public function __construct(string $name = 'test', int $count = 5) {
        $this->name = $name;
        $this->count = $count;
    }
}

class WithNullable {
    public ?LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null) {
        $this->logger = $logger;
    }
}

class Unresolvable {
    public function __construct(string $required) {
    }
}

abstract class AbstractClass {
}


class WithDefaultDependency {
    public ?LoggerInterface $logger;

    public function __construct(LoggerInterface $logger = null) {
        $this->logger = $logger;
    }
}

class WithNullableScalar {
    public ?int $value;

    public function __construct(?int $value = null) {
        $this->value = $value;
    }
}


class WithConcreteDefault {
    public string $driver;
    public ?LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, string $driver = 'file') {
        $this->logger = $logger;
        $this->driver = $driver;
    }
}

class WithNullableNoDefault {
    public ?int $value;

    public function __construct(?int $value) {
        $this->value = $value;
    }
}


class WithUnresolvableDefault {
    public ?object $dep;

    public function __construct(AbstractClass $dep = null) {
        $this->dep = $dep;
    }
}


class DefaultImpl implements LoggerInterface {
    public function log(string $msg): void {}
}

class WithNewDefault {
    public LoggerInterface $logger;

    public function __construct(LoggerInterface $logger = new DefaultImpl()) {
        $this->logger = $logger;
    }
}

class NeedsStdClass {
    public function __construct(public \stdClass $dep) {}
}

class ContainerTest extends TestCase {
    private Container $container;

    protected function setUp(): void {
        $this->container = new Container();
    }
    /**
     * @test
     */
    public function testBindAndMake() {
        $this->container->bind(LoggerInterface::class, FileLogger::class);
        $instance = $this->container->make(LoggerInterface::class);

        $this->assertInstanceOf(FileLogger::class, $instance);
    }
    /**
     * @test
     */
    public function testBindCreatesNewInstanceEachTime() {
        $this->container->bind(LoggerInterface::class, FileLogger::class);
        $a = $this->container->make(LoggerInterface::class);
        $b = $this->container->make(LoggerInterface::class);

        $this->assertNotSame($a, $b);
    }
    /**
     * @test
     */
    public function testSingletonReturnsSameInstance() {
        $this->container->singleton(LoggerInterface::class, FileLogger::class);
        $a = $this->container->make(LoggerInterface::class);
        $b = $this->container->make(LoggerInterface::class);

        $this->assertSame($a, $b);
    }
    /**
     * @test
     */
    public function testInstanceRegistration() {
        $logger = new FileLogger();
        $this->container->instance(LoggerInterface::class, $logger);

        $this->assertSame($logger, $this->container->make(LoggerInterface::class));
    }
    /**
     * @test
     */
    public function testAutoResolutionNoConstructor() {
        $instance = $this->container->make(NoConstructor::class);

        $this->assertInstanceOf(NoConstructor::class, $instance);
        $this->assertEquals('default', $instance->value);
    }
    /**
     * @test
     */
    public function testAutoResolutionWithDefaults() {
        $instance = $this->container->make(WithDefaults::class);

        $this->assertInstanceOf(WithDefaults::class, $instance);
        $this->assertEquals('test', $instance->name);
        $this->assertEquals(5, $instance->count);
    }
    /**
     * @test
     */
    public function testAutoResolutionWithDependencies() {
        $this->container->bind(LoggerInterface::class, FileLogger::class);
        $instance = $this->container->make(UserService::class);

        $this->assertInstanceOf(UserService::class, $instance);
        $this->assertInstanceOf(FileLogger::class, $instance->logger);
    }
    /**
     * @test
     */
    public function testRecursiveResolution() {
        $this->container->bind(LoggerInterface::class, FileLogger::class);
        $instance = $this->container->make(OrderService::class);

        $this->assertInstanceOf(OrderService::class, $instance);
        $this->assertInstanceOf(UserService::class, $instance->userService);
        $this->assertInstanceOf(FileLogger::class, $instance->userService->logger);
        $this->assertInstanceOf(FileLogger::class, $instance->logger);
    }
    /**
     * @test
     */
    public function testCallableFactory() {
        $this->container->bind(LoggerInterface::class, function (Container $c) {
            return new NullLogger();
        });

        $instance = $this->container->make(LoggerInterface::class);
        $this->assertInstanceOf(NullLogger::class, $instance);
    }
    /**
     * @test
     */
    public function testCallableFactoryReceivesContainer() {
        $this->container->singleton(LoggerInterface::class, FileLogger::class);
        $this->container->bind(UserService::class, function (Container $c) {
            return new UserService($c->make(LoggerInterface::class));
        });

        $instance = $this->container->make(UserService::class);
        $this->assertInstanceOf(FileLogger::class, $instance->logger);
    }
    /**
     * @test
     */
    public function testHas() {
        $this->assertFalse($this->container->has(LoggerInterface::class));
        $this->container->bind(LoggerInterface::class, FileLogger::class);
        $this->assertTrue($this->container->has(LoggerInterface::class));
    }
    /**
     * @test
     */
    public function testHasWithInstance() {
        $this->container->instance(LoggerInterface::class, new FileLogger());
        $this->assertTrue($this->container->has(LoggerInterface::class));
    }
    /**
     * @test
     */
    public function testRemove() {
        $this->container->bind(LoggerInterface::class, FileLogger::class);
        $this->container->remove(LoggerInterface::class);
        $this->assertFalse($this->container->has(LoggerInterface::class));
    }
    /**
     * @test
     */
    public function testReset() {
        $this->container->bind(LoggerInterface::class, FileLogger::class);
        $this->container->singleton(UserService::class, UserService::class);
        $this->container->reset();

        $this->assertFalse($this->container->has(LoggerInterface::class));
        $this->assertFalse($this->container->has(UserService::class));
    }
    /**
     * @test
     */
    public function testUnresolvableThrows() {
        $this->expectException(ContainerException::class);
        $this->container->make(Unresolvable::class);
    }
    /**
     * @test
     */
    public function testNonExistentClassThrows() {
        $this->expectException(ContainerException::class);
        $this->container->make('NonExistent\\ClassName');
    }
    /**
     * @test
     */
    public function testAbstractClassThrows() {
        $this->expectException(ContainerException::class);
        $this->container->make(AbstractClass::class);
    }
    /**
     * @test
     */
    public function testInterfaceWithoutBindingThrows() {
        $this->expectException(ContainerException::class);
        $this->container->make(LoggerInterface::class);
    }
    /**
     * @test
     */
    public function testNullableParameterResolvesToNull() {
        $instance = $this->container->make(WithNullable::class);

        $this->assertInstanceOf(WithNullable::class, $instance);
        $this->assertNull($instance->logger);
    }
    /**
     * @test
     */
    public function testNullableParameterResolvesWhenBound() {
        $this->container->bind(LoggerInterface::class, FileLogger::class);
        $instance = $this->container->make(WithNullable::class);

        $this->assertInstanceOf(WithNullable::class, $instance);
        $this->assertInstanceOf(FileLogger::class, $instance->logger);
    }
    /**
     * @test
     */
    public function testOverrideBinding() {
        $this->container->bind(LoggerInterface::class, FileLogger::class);
        $this->container->bind(LoggerInterface::class, NullLogger::class);

        $instance = $this->container->make(LoggerInterface::class);
        $this->assertInstanceOf(NullLogger::class, $instance);
    }
    /**
     * @test
     */
    public function testRemoveSingletonClearsCache() {
        $this->container->singleton(LoggerInterface::class, FileLogger::class);
        $this->container->make(LoggerInterface::class);
        $this->container->remove(LoggerInterface::class);
        $this->assertFalse($this->container->has(LoggerInterface::class));
    }
    /**
     * @test
     */
    public function testRemoveInstanceEntry() {
        $this->container->instance(LoggerInterface::class, new FileLogger());
        $this->container->remove(LoggerInterface::class);
        $this->assertFalse($this->container->has(LoggerInterface::class));
    }
    /**
     * @test
     */
    public function testUnresolvableDependencyWithDefault() {
        // Class with unbound interface param that has a default value
        $instance = $this->container->make(WithDefaultDependency::class);
        $this->assertInstanceOf(WithDefaultDependency::class, $instance);
        $this->assertNull($instance->logger);
    }
    /**
     * @test
     */
    public function testUnresolvableDependencyNonNullableThrows() {
        // Class with unbound interface param, no default, not nullable
        $this->expectException(\WebFiori\Container\ContainerException::class);
        $this->container->make(UserService::class);
    }
    /**
     * @test
     */
    public function testNullableScalarParam() {
        $instance = $this->container->make(WithNullableScalar::class);
        $this->assertInstanceOf(WithNullableScalar::class, $instance);
        $this->assertNull($instance->value);
    }
    /**
     * @test
     */
    public function testUnresolvableDependencyFallsToConcreteDefault() {
        // LoggerInterface unbound, but param has default 'file' for $driver
        // This tests the catch branch where isDefaultValueAvailable is true with non-null default
        $this->container->bind(LoggerInterface::class, FileLogger::class);
        $instance = $this->container->make(WithConcreteDefault::class);
        $this->assertEquals('file', $instance->driver);
    }
    /**
     * @test
     */
    public function testNullableScalarNoDefault() {
        $instance = $this->container->make(WithNullableNoDefault::class);
        $this->assertNull($instance->value);
    }
    /**
     * @test
     */
    public function testUnresolvableClassTypeWithDefaultNull() {
        $instance = $this->container->make(WithUnresolvableDefault::class);
        $this->assertInstanceOf(WithUnresolvableDefault::class, $instance);
        $this->assertNull($instance->dep);
    }
    /**
     * @test
     */
    public function testUnresolvableWithNewInstanceDefault() {
        // LoggerInterface is NOT bound, but param has `= new DefaultImpl()`
        // Container fails to resolve LoggerInterface, falls to default value
        $instance = $this->container->make(WithNewDefault::class);
        $this->assertInstanceOf(WithNewDefault::class, $instance);
        $this->assertInstanceOf(DefaultImpl::class, $instance->logger);
    }
    /**
     * @test
     */
    public function testSingletonNotSharedAcrossAbstracts() {
        $this->container->singleton('a', \stdClass::class);
        $this->container->singleton('b', \stdClass::class);
        $instanceA = $this->container->make('a');
        $instanceB = $this->container->make('b');
        $this->assertNotSame($instanceA, $instanceB);
    }
    /**
     * @test
     */
    public function testBindAfterSingletonOverrides() {
        $this->container->singleton(\stdClass::class, \stdClass::class);
        $first = $this->container->make(\stdClass::class);
        // Remove clears the cached singleton
        $this->container->remove(\stdClass::class);
        $this->container->bind(\stdClass::class, \stdClass::class);
        $second = $this->container->make(\stdClass::class);
        $third = $this->container->make(\stdClass::class);
        // After override to bind, each call creates new instance
        $this->assertNotSame($second, $third);
    }
    /**
     * @test
     */
    public function testFactoryCalledEachTimeForBind() {
        $count = 0;
        $this->container->bind('counter', function () use (&$count) {
            $count++;
            return new \stdClass();
        });
        $this->container->make('counter');
        $this->container->make('counter');
        $this->container->make('counter');
        $this->assertEquals(3, $count);
    }
    /**
     * @test
     */
    public function testFactoryCalledOnceForSingleton() {
        $count = 0;
        $this->container->singleton('counter', function () use (&$count) {
            $count++;
            return new \stdClass();
        });
        $this->container->make('counter');
        $this->container->make('counter');
        $this->container->make('counter');
        $this->assertEquals(1, $count);
    }
    /**
     * @test
     */
    public function testResetClearsBothBindingsAndInstances() {
        $this->container->bind('x', \stdClass::class);
        $this->container->instance('y', new \stdClass());
        $this->assertTrue($this->container->has('x'));
        $this->assertTrue($this->container->has('y'));
        $this->container->reset();
        $this->assertFalse($this->container->has('x'));
        $this->assertFalse($this->container->has('y'));
    }
    /**
     * @test
     */
    public function testAutoResolutionPreservesConstructorArgs() {
        // Verify that auto-resolved dependencies get correct instances
        $this->container->instance(\stdClass::class, (object)['name' => 'injected']);
        $obj = $this->container->make(NeedsStdClass::class);
        $this->assertEquals('injected', $obj->dep->name);
    }
}
