<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple router implementation
 */
class Router
{
    private array $routes = [];
    private array $middlewares = [];

    /**
     * Add GET route
     */
    public function get(string $path, string $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Add POST route
     */
    public function post(string $path, string $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Add PUT route
     */
    public function put(string $path, string $handler, array $middleware = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }

    /**
     * Add DELETE route
     */
    public function delete(string $path, string $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    /**
     * Add a route
     */
    private function addRoute(string $method, string $path, string $handler, array $middleware): self
    {
        // Convert path parameters to regex
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware
        ];

        return $this;
    }

    /**
     * Run the router
     */
    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run middleware
                foreach ($route['middleware'] as $middleware) {
                    $middlewareInstance = new $middleware();
                    $result = $middlewareInstance->handle();
                    if ($result === false) {
                        return;
                    }
                }

                // Parse handler
                [$controllerName, $actionName] = explode('@', $route['handler']);
                $controllerClass = "App\\Controllers\\{$controllerName}";

                if (!class_exists($controllerClass)) {
                    Response::error('Controller not found', 500);
                    return;
                }

                $controller = new $controllerClass();

                if (!method_exists($controller, $actionName)) {
                    Response::error('Action not found', 500);
                    return;
                }

                // Call controller action
                $controller->$actionName($params);
                return;
            }
        }

        // No route matched
        Response::error('Not found', 404, 'ROUTE_NOT_FOUND');
    }
}
