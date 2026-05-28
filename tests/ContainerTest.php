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
}

class ContainerFacadeTest extends TestCase {
    protected function setUp(): void {
        ContainerFacade::reset();
    }
    /**
     * @test
     */
    public function testFacadeGetInstanceReturnsContainer() {
        $this->assertInstanceOf(Container::class, ContainerFacade::getInstance());
    }
    /**
     * @test
     */
    public function testFacadeSetInstance() {
        $custom = new Container();
        ContainerFacade::setInstance($custom);
        $this->assertSame($custom, ContainerFacade::getInstance());
    }
    /**
     * @test
     */
    public function testFacadeDelegatesToInstance() {
        ContainerFacade::bind(LoggerInterface::class, FileLogger::class);
        $instance = ContainerFacade::make(LoggerInterface::class);
        $this->assertInstanceOf(FileLogger::class, $instance);
    }
    /**
     * @test
     */
    public function testFacadeResetCreatesNewInstance() {
        $first = ContainerFacade::getInstance();
        ContainerFacade::reset();
        $second = ContainerFacade::getInstance();
        $this->assertNotSame($first, $second);
    }
}
