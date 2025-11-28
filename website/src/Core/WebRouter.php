<?php

declare(strict_types=1);

namespace Website\Core;

/**
 * Simple web router for public website
 */
class WebRouter
{
    private array $routes = [];
    private View $view;
    private array $params = [];

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    /**
     * Register GET route
     */
    public function get(string $path, string $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register POST route
     */
    public function post(string $path, string $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Add route
     */
    private function addRoute(string $method, string $path, string $handler): void
    {
        // Convert path parameters to regex
        $pattern = preg_replace('/\{([a-zA-Z]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    /**
     * Run router
     */
    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rawurldecode($uri);

        // Remove trailing slash
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            header('Location: ' . rtrim($uri, '/'), true, 301);
            exit;
        }

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters
                $this->params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Call handler
                $this->dispatch($route['handler']);
                return;
            }
        }

        // 404 Not Found
        $this->notFound();
    }

    /**
     * Dispatch to controller
     */
    private function dispatch(string $handler): void
    {
        [$controllerName, $method] = explode('@', $handler);

        $controllerClass = 'Website\\Controllers\\' . $controllerName;
        $controllerFile = WEBSITE_PATH . '/src/Controllers/' . $controllerName . '.php';

        if (!file_exists($controllerFile)) {
            $this->notFound();
            return;
        }

        require_once $controllerFile;

        if (!class_exists($controllerClass)) {
            $this->notFound();
            return;
        }

        $controller = new $controllerClass($this->view);

        if (!method_exists($controller, $method)) {
            $this->notFound();
            return;
        }

        // Call controller method with params
        $controller->$method($this->params);
    }

    /**
     * Get route parameter
     */
    public function getParam(string $name, mixed $default = null): mixed
    {
        return $this->params[$name] ?? $default;
    }

    /**
     * Handle 404
     */
    private function notFound(): void
    {
        http_response_code(404);
        $this->view->display('errors/404');
    }

    /**
     * Redirect
     */
    public static function redirect(string $url, int $code = 302): void
    {
        header('Location: ' . $url, true, $code);
        exit;
    }
}
