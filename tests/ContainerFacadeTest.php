<?php

namespace WebFiori\Container\Tests;

use PHPUnit\Framework\TestCase;
use WebFiori\Container\Container;
use WebFiori\Container\ContainerFacade;

// Fixtures from ContainerTest
interface FacadeLoggerInterface {
    public function log(string $msg): void;
}
class FacadeFileLogger implements FacadeLoggerInterface {
    public function log(string $msg): void {}
}
class FacadeNullLogger implements FacadeLoggerInterface {
    public function log(string $msg): void {}
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
    public function testFacadeBind() {
        ContainerFacade::bind(FacadeLoggerInterface::class, FacadeFileLogger::class);
        $instance = ContainerFacade::make(FacadeLoggerInterface::class);
        $this->assertInstanceOf(FacadeFileLogger::class, $instance);
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
    /**
     * @test
     */
    public function testFacadeSingleton() {
        ContainerFacade::singleton(FacadeLoggerInterface::class, FacadeFileLogger::class);
        $a = ContainerFacade::make(FacadeLoggerInterface::class);
        $b = ContainerFacade::make(FacadeLoggerInterface::class);
        $this->assertSame($a, $b);
    }
    /**
     * @test
     */
    public function testFacadeInstance() {
        $logger = new FacadeNullLogger();
        ContainerFacade::instance(FacadeLoggerInterface::class, $logger);
        $this->assertSame($logger, ContainerFacade::make(FacadeLoggerInterface::class));
    }
    /**
     * @test
     */
    public function testFacadeHas() {
        $this->assertFalse(ContainerFacade::has(FacadeLoggerInterface::class));
        ContainerFacade::bind(FacadeLoggerInterface::class, FacadeFileLogger::class);
        $this->assertTrue(ContainerFacade::has(FacadeLoggerInterface::class));
    }
    /**
     * @test
     */
    public function testFacadeRemove() {
        ContainerFacade::bind(FacadeLoggerInterface::class, FacadeFileLogger::class);
        ContainerFacade::remove(FacadeLoggerInterface::class);
        $this->assertFalse(ContainerFacade::has(FacadeLoggerInterface::class));
    }
}
