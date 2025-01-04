<?php
namespace Amichiamoci\Routing;

use Amichiamoci\Controllers\Controller;
use Amichiamoci\Models\User;
use Amichiamoci\Models\StaffBase;

class Router {

    private ?\mysqli $connection = null;
    private ?User $user = null;
    private ?StaffBase $staff = null;
    private array $routes = [];

    public function AddRouteAction(
        string $route, 
        string $controller, 
        string $action
    ): void {
        $this->routes[$route] = [
            'controller' => $controller, 
            'action' => $action
        ];
    }

    public function AddController(
        string $controller,
        string $route_base
    ): void {
        $dummy_instance = new $controller(
            connection: null
        );
        if (!$dummy_instance instanceof Controller)
        {
            throw new \Exception(message: "Invalid class '$controller'");
        }

        if (!str_starts_with(haystack: $route_base, needle: '/'))
        {
            $route_base = '/' . $route_base;
        }

        $methods = get_class_methods(object_or_class: $dummy_instance);
        foreach ($methods as $method)
        {
            if (str_starts_with(haystack: $method, needle: '_') || 
                str_starts_with(haystack: $method, needle: '#'))
                continue;
            $this->AddRouteAction(
                route: $route_base === '/' ? "/$method" : "$route_base/$method", 
                controller: $controller, 
                action: $method
            );
        }

        // Default '/' to method index()
        $this->AddRouteAction(
            route: "$route_base", 
            controller: $controller, 
            action: 'index'
        );
    }

    public function Dispatch(string $uri): void {
        $path = parse_url(url: $uri, component: PHP_URL_PATH);
        if (!$path || empty($path)) {
            throw new \Exception(message: "Could not extract path from uri '$uri'.");
        }

        $method_params = [];

        if (self::IsPost()) {
            $method_params = $_POST;
        }
        elseif (self::IsGet()) {
            $query = parse_url(url: $uri, component: PHP_URL_QUERY);
            if (is_string(value: $query) && !empty($query)) {
                parse_str(string: $query, result: $method_params);
            }
        }

        if (!array_key_exists(key: $path, array: $this->routes)) {
            $controller = NotFoundTempController::class;
            $action = 'NotFoundHandler';
            
            // throw new \Exception(message: "No route found for path: $path");
        } else {
            $controller = $this->routes[$path]['controller'];
            $action = $this->routes[$path]['action'];
        }

        $controller_instance = new $controller(
            connection: $this->connection, 
            user: $this->user,
            staff: $this->staff,
            path: $uri,
        );
        call_user_func_array(callback: [$controller_instance, $action], args: $method_params);
        // $controller->$action();
    }

    public function SetDbConnection(\mysqli $connection): bool {
        $this->connection = $connection;
        return $this->connection->set_charset(charset: "utf8mb4");
    }

    public function SetUser(User $user): void {
        $this->user = $user;
    }
    
    public function SetStaff(StaffBase $staff): void {
        $this->staff = $staff;
    }
   
    public static function IsPost(): bool {
        return strtoupper(string: $_SERVER["REQUEST_METHOD"]) === 'POST';
    }
    public static function IsGet(): bool {
        return strtoupper(string: $_SERVER["REQUEST_METHOD"]) === 'GET';
    }
}