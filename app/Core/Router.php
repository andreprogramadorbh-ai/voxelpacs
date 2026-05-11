<?php
namespace App\Core;

class Router {
    private static array $routes = [];

    // Rotas públicas que não precisam de autenticação
    private static array $publicRoutes = [
        '/login',
        '/logout',
        '/selecionar-empresa',
    ];

    public static function get(string $path, $handler): void {
        self::$routes[] = ['method' => 'GET', 'path' => $path, 'handler' => $handler];
    }

    public static function post(string $path, $handler): void {
        self::$routes[] = ['method' => 'POST', 'path' => $path, 'handler' => $handler];
    }

    public static function group(array $options, callable $callback): void {
        $callback();
    }

    /**
     * Verifica se a URI atual é uma rota pública (não exige autenticação)
     */
    private static function isPublicRoute(string $uri): bool {
        foreach (self::$publicRoutes as $pub) {
            if ($uri === $pub || strpos($uri, $pub) === 0) {
                return true;
            }
        }
        return false;
    }

    public static function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = strtok($_SERVER['REQUEST_URI'], '?');

        // Redireciona para login se não autenticado e a rota não é pública
        if (!self::isPublicRoute($uri) && !Auth::check()) {
            header('Location: /login');
            exit;
        }

        foreach (self::$routes as $route) {
            $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                array_shift($matches);

                if (is_callable($route['handler'])) {
                    try {
                        call_user_func_array($route['handler'], $matches);
                    } catch (\Throwable $e) {
                        self::handleError($e);
                    }
                    return;
                }

                [$controllerName, $action] = explode('@', $route['handler']);
                $class = "App\\Controllers\\{$controllerName}";

                if (!class_exists($class)) {
                    http_response_code(500);
                    Logger::error("Controller não encontrado: {$class}");
                    echo "<h1>Erro 500</h1><p>Controller <code>{$class}</code> não encontrado.</p>";
                    return;
                }

                try {
                    $controller = new $class();
                    call_user_func_array([$controller, $action], $matches);
                } catch (\Throwable $e) {
                    self::handleError($e);
                }
                return;
            }
        }

        http_response_code(404);
        echo '<h1>404 - Página não encontrada</h1>';
    }

    private static function handleError(\Throwable $e): void {
        http_response_code(500);
        Logger::error('Erro não tratado na rota', [
            'exception' => $e->getMessage(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
        ]);

        if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            echo "<h1>Erro 500</h1>";
            echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>Arquivo:</strong> " . htmlspecialchars($e->getFile()) . " na linha " . $e->getLine() . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        } else {
            echo "<h1>Erro 500 - Erro Interno do Servidor</h1>";
            echo "<p>Ocorreu um erro ao processar sua requisição. Tente novamente mais tarde.</p>";
        }
    }
}
