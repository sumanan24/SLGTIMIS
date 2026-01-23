<?php
/**
 * Router Class
 */

class Router {
    private $routes = [];
    
    public function __construct() {
        $this->routes = require BASE_PATH . '/config/routes.php';
    }
    
    /**
     * Route the request
     */
    public function route($uri) {
        // Remove query string
        $uri = strtok($uri, '?');
        
        // Remove leading/trailing slashes
        $uri = trim($uri, '/');
        
        // Check if route exists
        if (isset($this->routes[$uri])) {
            $route = $this->routes[$uri];
            return $this->dispatch($route);
        }
        
        // Try to match dynamic routes
        foreach ($this->routes as $pattern => $route) {
            $regex = '#^' . preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern) . '$#';
            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches);
                return $this->dispatch($route, $matches);
            }
        }
        
        // 404 Not Found
        http_response_code(404);
        return $this->dispatch('HomeController@notFound');
    }
    
    /**
     * Dispatch the route
     */
    private function dispatch($route, $params = []) {
        list($controller, $method) = explode('@', $route);
        
        $controllerFile = BASE_PATH . '/controllers/' . $controller . '.php';
        
        if (!file_exists($controllerFile)) {
            throw new Exception("Controller not found: $controller");
        }
        
        require_once $controllerFile;
        
        if (!class_exists($controller)) {
            throw new Exception("Controller class not found: $controller");
        }
        
        $controllerInstance = new $controller();
        
        if (!method_exists($controllerInstance, $method)) {
            throw new Exception("Method not found: $controller::$method");
        }
        
        $result = call_user_func_array([$controllerInstance, $method], $params);
        
        return $result;
    }
}

