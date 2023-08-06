<?php

namespace app;

class Container
{
    private static array $registered_services = [];

    public static function registerService(string $class_name, callable $factory): void
    {
        self::$registered_services[$class_name] = $factory;
    }

    public static function getService(string $class_name)
    {
        if (isset(self::$registered_services[$class_name])) {
            return call_user_func(self::$registered_services[$class_name]);
        }

        throw new \Exception("Service $class_name is not registered.");
    }
}