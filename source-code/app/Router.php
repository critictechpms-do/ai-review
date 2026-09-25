<?php

class Router
{
    private static $routes = [];

    public static function add($method, $route, $file)
    {
        self::$routes[] = [
            'method' => $method,
            'route' => $route,
            'file' => $file
        ];
    }

    public static function get($route, $file)
    {
        self::add('GET', $route, $file);
    }

    public static function post($route, $file)
    {
        self::add('POST', $route, $file);
    }

    public static function put($route, $file)
    {
        self::add('PUT', $route, $file);
    }

    public static function delete($route, $file)
    {
        self::add('DELETE', $route, $file);
    }

 public static function dispatch()
{
    $url = $_GET['url'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Skip routing for static files and existing files
    $filePath = __DIR__ . '/../' . $url;
    if (file_exists($filePath) && !is_dir($filePath)) {
        return false; // Let the web server handle it
    }
    
    // Skip routing for uploads, assets, etc.
    if (preg_match('/\.(jpg|jpeg|png|gif|webp|css|js|ico|svg|woff|woff2|ttf|eot)$/i', $url)) {
        return false;
    }
    
    // Handle special case for PUT/DELETE requests from forms
    if ($method === 'POST' && isset($_POST['_method'])) {
        $method = strtoupper($_POST['_method']);
    }

    foreach (self::$routes as $route) {
        if ($route['method'] !== $method) {
            continue;
        }

        $pattern = self::convertRouteToRegex($route['route']);
        
        if (preg_match($pattern, $url, $matches)) {
            // Extract parameters
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            
            // Store params for use in the file
            $_GET['route_params'] = $params;
            
            require $route['file'];
            exit;
        }
    }

    // Route not found
    http_response_code(404);
    echo "404 - Page not found";
}

    private static function convertRouteToRegex($route)
    {
        // Convert {param} to named regex groups
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $route);
        return '#^' . trim($pattern, '/') . '$#';
    }
}
?>