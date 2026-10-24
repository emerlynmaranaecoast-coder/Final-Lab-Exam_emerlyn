<?php
/**
 * Front controller - every request enters here.
 *
 * URL format:  index.php?r=<controller>/<action>&id=1
 * Example:     index.php?r=students/show&id=4   ->  StudentController::show()
 *
 * Query-string routing is used on purpose: it works on any shared host
 * (including InfinityFree) without needing mod_rewrite.
 */

require __DIR__ . '/app/core/bootstrap.php';

// Whitelist of routable controllers (URL segment => class name).
const ROUTES = [
    'auth'        => 'AuthController',
    'dashboard'   => 'DashboardController',
    'departments' => 'DepartmentController',
    'courses'     => 'CourseController',
    'students'    => 'StudentController',
    'academics'   => 'AcademicController',
];

// Routes reachable without logging in.
const PUBLIC_ROUTES = ['auth/login', 'auth/logout'];

$route = $_GET['r'] ?? 'dashboard/index';
if (!is_string($route) || !preg_match('/^[a-z]+\/[a-z]+$/', $route)) {
    $route = 'dashboard/index';
}
[$segment, $action] = explode('/', $route);

$guard = new class extends Controller {
    public function deny(int $s, string $t, string $m): never { $this->error($s, $t, $m); }
};

if (!isset(ROUTES[$segment])) {
    $guard->deny(404, 'Page not found', 'The page you requested does not exist.');
}

if (!Auth::check() && !in_array($route, PUBLIC_ROUTES, true)) {
    $_SESSION['intended'] = ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' ? $route : null;
    header('Location: ' . url('auth/login'));
    exit;
}

$class = ROUTES[$segment];
$controller = new $class();

// Only public, non-inherited methods can be called as actions.
$method = new ReflectionClass($controller);
if (!$method->hasMethod($action)
    || !$method->getMethod($action)->isPublic()
    || $method->getMethod($action)->getDeclaringClass()->getName() !== $class) {
    $guard->deny(404, 'Page not found', 'The page you requested does not exist.');
}

$controller->$action();
