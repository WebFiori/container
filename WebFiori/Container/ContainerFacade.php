<?php

/**
 * This file is licensed under MIT License.
 *
 * Copyright (c) 2026 WebFiori Framework
 *
 * For more information on the license, please visit:
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 *
 */
namespace WebFiori\Container;

/**
 * A static facade for the Container class.
 *
 * Provides a convenient static API that delegates to a default Container instance.
 * For dependency injection or multiple containers, use the Container class directly.
 */
class ContainerFacade {
    /**
     * @var Container|null The default container instance.
     */
    private static ?Container $inst = null;
    /**
     * Returns the default Container instance, creating it lazily if needed.
     *
     * @return Container
     */
    public static function getInstance(): Container {
        if (self::$inst === null) {
            self::$inst = new Container();
        }

        return self::$inst;
    }
    /**
     * Replaces the default Container instance.
     *
     * @param Container $container The container instance to use as default.
     */
    public static function setInstance(Container $container): void {
        self::$inst = $container;
    }
    /**
     * @see Container::bind()
     */
    public static function bind(string $abstract, string|callable $concrete): void {
        self::getInstance()->bind($abstract, $concrete);
    }
    /**
     * @see Container::singleton()
     */
    public static function singleton(string $abstract, string|callable $concrete): void {
        self::getInstance()->singleton($abstract, $concrete);
    }
    /**
     * @see Container::instance()
     */
    public static function instance(string $abstract, object $instance): void {
        self::getInstance()->instance($abstract, $instance);
    }
    /**
     * @see Container::make()
     */
    public static function make(string $abstract): object {
        return self::getInstance()->make($abstract);
    }
    /**
     * @see Container::has()
     */
    public static function has(string $abstract): bool {
        return self::getInstance()->has($abstract);
    }
    /**
     * @see Container::remove()
     */
    public static function remove(string $abstract): void {
        self::getInstance()->remove($abstract);
    }
    /**
     * Destroys the default Container instance. The next call will create a fresh one.
     */
    public static function reset(): void {
        self::$inst = null;
    }
}
