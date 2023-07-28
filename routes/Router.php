<?php

namespace routes;

use utils\Middleware;

class Router
{
    private array $routes = [];

    /**
     * An array to store all the data, to be passed from router to controllers
     * Contains $_POST, $_GET, $_FILES, and shared data from Middleware
     * @var array
     */
    private array $data = [];

    /**
     * Middlewares for authorization actions
     * @var array
     */
    private array $auths = [];

    /**
     * An array that contains prefixes to the URL, to remove them from the request URI
     * @var array|string[]
     */
    private array $_EXCLUDES = ['/seo'];

    public function get(string $url, $handler): void
    {
        $this->addRoute($url, $handler, 'GET');
    }

    private function addRoute(string $url, $handler, string $method): void
    {
        $this->routes[] = [
            'auth'       => $this->auths,
            'url'        => $url,
            'controller' => $handler,
            'method'     => $method,
        ];
        $this->auths    = [];
    }

    public function post(string $url, $handler): void
    {
        $this->addRoute($url, $handler, 'POST');
    }

    public function handleRequest(): void
    {
        $request_url = $_SERVER['REQUEST_URI'];
        // Remove unnecessary segments from $request_url
        foreach ($this->_EXCLUDES as $EXCLUDE) {
            $request_url = str_replace($EXCLUDE, '', $request_url);
        }
        $request_method = $_SERVER['REQUEST_METHOD'];
        foreach ($this->routes as $route) {
            $pattern    = $this->convertRegex($route['url']);
            $controller = $route['controller'];
            if (is_array($controller)) {
                if ($request_method === $route['method'] && preg_match($pattern, $request_url, $matches)) {
                    $auth = $this->auth($route);
                    if ($auth) {
                        if (is_callable($controller)) {
                            [$controller_class, $method] = $controller;
                            if (class_exists($controller_class)) {
                                $instance = new $controller_class();
                                if (method_exists($instance, $method)) {
                                    $this->data = array_merge($matches, $_POST, $_FILES);
                                    call_user_func_array([$instance, $method], [$this->data]);
                                    return;
                                }
                            }
                        }
                    }
                }
            } else {
                // For closure object function
            }
        }

        // No matching route found
        echo '404 Not Found';
    }

    private function convertRegex(string $url): string
    {
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $url);
        return "#^" . $pattern . "$#";
    }

    public function auth(array $route): bool
    {
        $auths = $route['auth'];
        foreach ($auths as &$auth) {
            $auth_parts = explode('-', $auth);
            $auth       = lcfirst(implode('', array_map('ucfirst', $auth_parts)));
        }
        $middleware_instance = new Middleware();
        foreach ($auths as $middleware) {
            if (method_exists($middleware_instance, $middleware)) {
                // Execute the specified middleware method if it exists
                $response = $middleware_instance->{$middleware}();
                if ($response) {
                    $this->data[] = $response;
                } else {
                    return FALSE;
                }
            }
        }

        return TRUE;
    }

    public function middleware(array $middlewares): Router
    {
        $this->auths = $middlewares;
        return $this;
    }
}