<?php
namespace Amichiamoci\Routing;

use Amichiamoci\Controllers\Controller;
use Amichiamoci\Models\User;
use Amichiamoci\Models\Staff;

class Router {

    private ?\mysqli $connection = null;
    private ?User $user = null;
    private ?Staff $staff = null;
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

    private static function ParseParameters(string $uri): array
    {
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

        $filtered_parameters = [];
        foreach ($method_params as $name => $value)
        {
            $filtered_parameters[
                str_replace(search: '-', replace: '_', subject: $name)
            ] = $value;
        }
        return $filtered_parameters;
    }

    public function Dispatch(string $uri): void {
        $path = parse_url(url: $uri, component: PHP_URL_PATH);
        if (!$path || empty($path)) {
            throw new \Exception(message: "Could not extract path from uri '$uri'.");
        }

        $method_params = self::ParseParameters(uri: $uri);

        if (!array_key_exists(key: $path, array: $this->routes)) {
            $controller = ErrorTempController::class;
            $action = 'NotFoundHandler';
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

        try {
            call_user_func_array(callback: [$controller_instance, $action], args: $method_params);
        } catch (\Throwable $ex) {
            if (headers_sent()) {
                // Cannot send an other HTTP status code, just print the error
                // It will be handled by the general handler 
                throw $ex;
            }
            (new ErrorTempController(
                connection: $this->connection, 
                user: $this->user,
                staff: $this->staff,
                path: $uri,
            ))->InternalErrorHandler(ex: $ex);
        }
    }

    public function SetDbConnection(\mysqli $connection): bool {
        $this->connection = $connection;
        return $this->connection->set_charset(charset: "utf8mb4");
    }

    public function SetUser(User $user): void {
        $this->user = $user;
    }
    
    public function SetStaff(Staff $staff): void {
        $this->staff = $staff;
    }
   
    public static function IsPost(): bool {
        return strtoupper(string: $_SERVER["REQUEST_METHOD"]) === 'POST';
    }
    public static function IsGet(): bool {
        return strtoupper(string: $_SERVER["REQUEST_METHOD"]) === 'GET';
    }
}