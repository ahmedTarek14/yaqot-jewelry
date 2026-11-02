<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Mcamara\LaravelLocalization\LaravelLocalization;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The admin is locked down by construction, not by habit: every admin route sits behind
 * auth:admin (the login and the logout form aside), and every admin write that carries input
 * validates it with a Form Request. A new admin route that forgets either fails here.
 */
class AdminRoutesTest extends TestCase
{
    /** Admin routes that must be reachable without a session: the login form and its submission. */
    private const PUBLIC = ['admin.login', 'admin.login.submit'];

    /**
     * Writes that take no user input beyond the URL (a delete, a switch flip is validated inline
     * against its fixed value, a restore, a logout): nothing for a Form Request to validate.
     * Anything else must type-hint one.
     */
    private const NO_PAYLOAD = [
        'admin.logout',
        'admin.analytics.prune',
        'admin.system.migrate',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Reboots once: refreshApplicationWithLocale() runs setUp() again with the key set.
        if (getenv(LaravelLocalization::ENV_ROUTE_KEY) === false) {
            $this->refreshApplicationWithLocale('en');
        }
    }

    /** @return list<Route> */
    private function adminRoutes(): array
    {
        $routes = array_values(array_filter(
            RouteFacade::getRoutes()->getRoutes(),
            fn (Route $route) => str_starts_with((string) $route->getName(), 'admin.'),
        ));

        $this->assertGreaterThan(40, count($routes), 'the admin routes were not found');

        return $routes;
    }

    public function test_every_admin_route_is_behind_the_admin_guard(): void
    {
        $open = [];

        foreach ($this->adminRoutes() as $route) {
            if (in_array($route->getName(), self::PUBLIC, true)) {
                continue;
            }

            if (! in_array('auth:admin', $route->gatherMiddleware(), true)) {
                $open[] = $route->getName().' ('.implode('|', $route->methods()).' '.$route->uri().')';
            }
        }

        $this->assertSame([], $open, 'admin routes without auth:admin');
    }

    public function test_there_is_no_registration_or_password_reset_route(): void
    {
        foreach (RouteFacade::getRoutes()->getRoutes() as $route) {
            $this->assertDoesNotMatchRegularExpression('#(^|/)(register|password)(/|$)#', $route->uri(), $route->uri());
        }
    }

    public function test_every_admin_write_that_takes_input_uses_a_form_request(): void
    {
        $without = [];

        foreach ($this->adminRoutes() as $route) {
            if (array_diff($route->methods(), ['GET', 'HEAD']) === []) {
                continue;
            }

            if (in_array($route->getName(), self::PUBLIC, true) || in_array($route->getName(), self::NO_PAYLOAD, true)) {
                continue;
            }

            $action = $route->getAction('controller');

            if (! is_string($action) || ! str_contains($action, '@')) {
                $without[] = $route->getName().' (not a controller action)';

                continue;
            }

            [$class, $method] = explode('@', $action);
            $takesFormRequest = false;

            foreach ((new ReflectionMethod($class, $method))->getParameters() as $parameter) {
                $type = $parameter->getType()?->getName();

                if ($type !== null && is_subclass_of($type, FormRequest::class)) {
                    $takesFormRequest = true;
                }
            }

            // Deletes, restores and flag switches have no body to validate; they are listed
            // explicitly so that a new kind of write has to be looked at.
            $payloadFree = str_ends_with($route->getName(), '.destroy')
                || str_ends_with($route->getName(), '.restore')
                || str_ends_with($route->getName(), '.force')
                || str_ends_with($route->getName(), '.force-destroy')
                || str_ends_with($route->getName(), '.force-delete');

            if (! $takesFormRequest && ! $payloadFree && ! in_array('PATCH', $route->methods(), true)) {
                $without[] = $route->getName().' ('.$action.')';
            }
        }

        $this->assertSame([], $without, 'admin writes without a Form Request');
    }
}
