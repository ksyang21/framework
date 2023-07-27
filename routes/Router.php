<?php

namespace routes;

use utils\Middleware;

class Router
{
	private array $routes = [];

	/**
	 * An array to store all the shared data, from router to controllers
	 * @var array
	 */
	private array $shared_data = [];

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
			'url'        => $url,
			'controller' => $handler,
			'method'     => $method,
		];
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

		foreach ($this->routes as $route) {
			$pattern    = $this->convertRegex($route['url']);
			$controller = $route['controller'];
			$auth       = $this->auth();
			if ($auth) {
				if ($route['method'] === 'GET' && preg_match($pattern, $request_url, $matches)) {
					if (is_callable($controller)) {
						[$controller_class, $method] = $controller;
						if (class_exists($controller_class)) {
							$instance = new $controller_class();
							if (method_exists($instance, $method)) {
								call_user_func_array([$instance, $method], [$matches]);
								return;
							}
						}
					}
				} else if ($route['method'] === 'POST' && preg_match($pattern, $request_url, $matches)) {
					if (is_callable($controller)) {
						[$controller_class, $method] = $controller;
						if (class_exists($controller_class)) {
							$instance = new $controller_class();
							if (method_exists($instance, $method)) {
								call_user_func_array([$instance, $method], [$_POST]);
								return;
							}
						}
					}
				}
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

	public function auth(): bool
	{

		$middlewareInstance = new Middleware();

		foreach ($this->auths as $middleware) {
			if (method_exists($middlewareInstance, $middleware)) {
				// Execute the specified middleware method if it exists
				$response = $middlewareInstance->{$middleware}();
				if ($response) {
					$this->shared_data[] = $response;
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