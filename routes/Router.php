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

	private function addRoute(string $url, $handler, string $method, $controller = null, $controllerMethod = null): void
	{
		// If the controller information is not provided, set the handler as a callback function
		if (!$controller && !$controllerMethod) {
			$controller = $handler;
			$handler    = null;
		}

		$this->routes[] = [
			'url'               => $url,
			'controller'        => $controller,
			'method'            => $method,
			'controller_method' => $controllerMethod,
			'callback'          => $handler,
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
		$request_method = $_SERVER['REQUEST_METHOD'];

		foreach ($this->routes as $route) {
			$pattern    = $this->convertRegex($route['url']);
			$controller = $route['controller'];
			$auth       = $this->auth();
//			$auth = true;
			if ($auth) {
				if ($route['method'] === $request_method && preg_match($pattern, $request_url, $matches)) {
					if (isset($route['controller']) && isset($route['controller_method'])) {
						// Handle the case where the route has a controller and controller method defined
						[$controller_class, $method] = [$route['controller'], $route['controller_method']];
						if (class_exists($controller_class)) {
							$instance = new $controller_class();
							if (method_exists($instance, $method)) {
								call_user_func_array([$instance, $method], [$matches]);
								return;
							}
						}
					} else if (isset($route['callback']) && is_callable($route['callback'])) {
						// Handle the case where the route has a simple callback function
						call_user_func($route['callback']);
						return;
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