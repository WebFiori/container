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

use ReflectionClass;
use ReflectionNamedType;

/**
 * A lightweight dependency injection container with auto-resolution.
 *
 * Supports binding interfaces to implementations, singleton registration,
 * callable factories, and automatic constructor dependency resolution.
 */
class Container {
    /**
     * @var array Registered bindings.
     */
    private array $bindings = [];
    /**
     * @var array Resolved singleton instances.
     */
    private array $instances = [];

    /**
     * Bind an abstract type to a concrete implementation.
     *
     * Each call to make() will create a new instance.
     *
     * @param string $abstract The interface or class name to bind.
     * @param string|callable $concrete The implementation class name or a callable factory.
     */
    public function bind(string $abstract, string|callable $concrete): void {
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => false,
        ];
    }
    /**
     * Bind an abstract type as a singleton.
     *
     * The first call to make() creates the instance; subsequent calls return the same instance.
     *
     * @param string $abstract The interface or class name to bind.
     * @param string|callable $concrete The implementation class name or a callable factory.
     */
    public function singleton(string $abstract, string|callable $concrete): void {
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => true,
        ];
    }
    /**
     * Register an existing instance in the container.
     *
     * @param string $abstract The interface or class name.
     * @param object $instance The instance to register.
     */
    public function instance(string $abstract, object $instance): void {
        $this->instances[$abstract] = $instance;
    }
    /**
     * Resolve an instance of the given type from the container.
     *
     * If the type is bound, uses the registered binding. Otherwise, attempts
     * to auto-resolve by inspecting the constructor.
     *
     * @param string $abstract The interface or class name to resolve.
     *
     * @return object The resolved instance.
     *
     * @throws ContainerException If the type cannot be resolved.
     */
    public function make(string $abstract): object {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $binding = $this->bindings[$abstract] ?? null;

        if ($binding !== null) {
            $instance = $this->build($binding['concrete']);

            if ($binding['singleton']) {
                $this->instances[$abstract] = $instance;
            }

            return $instance;
        }

        return $this->build($abstract);
    }
    /**
     * Check if a binding or instance exists for the given type.
     *
     * @param string $abstract The interface or class name.
     *
     * @return bool True if the container can resolve the type.
     */
    public function has(string $abstract): bool {
        return isset($this->instances[$abstract]) || isset($this->bindings[$abstract]);
    }
    /**
     * Remove a binding and its cached instance.
     *
     * @param string $abstract The interface or class name to remove.
     */
    public function remove(string $abstract): void {
        unset($this->bindings[$abstract], $this->instances[$abstract]);
    }
    /**
     * Remove all bindings and instances.
     */
    public function reset(): void {
        $this->bindings = [];
        $this->instances = [];
    }
    /**
     * Build an instance from a concrete class name or callable.
     *
     * @param string|callable $concrete The class name or factory callable.
     *
     * @return object The built instance.
     *
     * @throws ContainerException If the class cannot be instantiated.
     */
    private function build(string|callable $concrete): object {
        if (is_callable($concrete)) {
            return $concrete($this);
        }

        if (!class_exists($concrete)) {
            throw new ContainerException("Cannot resolve '$concrete': class does not exist.");
        }

        $reflection = new ReflectionClass($concrete);

        if (!$reflection->isInstantiable()) {
            throw new ContainerException("Cannot resolve '$concrete': class is not instantiable.");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $params = [];

        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                try {
                    $params[] = $this->make($type->getName());
                } catch (ContainerException $e) {
                    if ($param->isDefaultValueAvailable()) {
                        $params[] = $param->getDefaultValue();
                    } else if ($type->allowsNull()) {
                        $params[] = null;
                    } else {
                        throw $e;
                    }
                }
            } else if ($param->isDefaultValueAvailable()) {
                $params[] = $param->getDefaultValue();
            } else if ($type !== null && $type->allowsNull()) {
                $params[] = null;
            } else {
                throw new ContainerException(
                    "Cannot resolve parameter '\${$param->getName()}' for '$concrete'."
                );
            }
        }

        return $reflection->newInstanceArgs($params);
    }
}
