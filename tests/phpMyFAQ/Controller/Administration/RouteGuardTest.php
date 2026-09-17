<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Structural regression test for the Administration page controllers.
 *
 * The Administration namespace gate only requires a logged-in session, and any
 * activated front-end account owns one. The per-route authorization calls
 * inside each controller method are therefore the only barrier between a
 * zero-rights account and admin data (CWE-862). This test asserts that every
 * routed page method outside the authentication endpoints invokes at least one
 * permission check — a mere userIsAuthenticated() does not count — so a page
 * that stops at "logged in" fails CI instead of shipping.
 *
 * @see Api\RouteGuardTest for the Administration API namespace
 */
#[CoversNothing]
final class RouteGuardTest extends TestCase
{
    /**
     * Methods on AbstractController that check a permission of the current
     * user, either throwing on failure or reporting the outcome as a boolean.
     */
    private const array PERMISSION_CHECK_METHODS = [
        'userIsSuperAdmin',
        'userHasGroupPermission',
        'userHasUserPermission',
        'userHasPermission',
        'userHasPermissionForCategories',
        'userHasPermissionForLanguage',
        'userMayPublish',
        'userMayPublishIn',
        'userHasAnyPermission',
        'userMay',
    ];

    /**
     * Routes that by design only need a session, or none at all: the login
     * flow itself and the session keep-alive ping. Nothing else belongs here.
     */
    private const array AUTHENTICATION_ONLY_ROUTES = [
        'AuthenticationController::authenticate',
        'AuthenticationController::check',
        'AuthenticationController::login',
        'AuthenticationController::logout',
        'AuthenticationController::token',
        'SessionKeepAliveController::index',
    ];

    private const string CONTROLLER_DIR = PMF_SRC_DIR . '/phpMyFAQ/Controller/Administration';

    public function testPermissionCheckListMatchesAbstractControllerMethods(): void
    {
        $abstractController = new ReflectionClass(\phpMyFAQ\Controller\AbstractController::class);

        foreach (self::PERMISSION_CHECK_METHODS as $checkMethod) {
            $this->assertTrue(
                $abstractController->hasMethod($checkMethod),
                sprintf(
                    'Permission check "%s" no longer exists on AbstractController — update ' .
                    'PERMISSION_CHECK_METHODS in %s so the route guard check keeps matching real checks.',
                    $checkMethod,
                    self::class,
                ),
            );
        }
    }

    public function testEveryRoutedAdministrationPageMethodChecksAPermission(): void
    {
        $routedMethods = 0;
        $unguarded = [];

        foreach ($this->administrationPageControllers() as $controller) {
            foreach ($controller->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getAttributes(Route::class) === []) {
                    continue;
                }

                $routeKey = $controller->getShortName() . '::' . $method->getName();
                if (in_array($routeKey, self::AUTHENTICATION_ONLY_ROUTES, true)) {
                    continue;
                }

                ++$routedMethods;

                if (!$this->methodChecksAPermission($method)) {
                    $unguarded[] = sprintf('%s() (%s:%d)', $routeKey, $method->getFileName(), $method->getStartLine());
                }
            }
        }

        $this->assertGreaterThan(0, $routedMethods, 'No routed Administration page methods found — discovery is broken.');
        $this->assertSame(
            [],
            $unguarded,
            "The following Administration pages enforce no permission check (CWE-862):\n  - " .
            implode("\n  - ", $unguarded) .
            "\nAdd a check such as \$this->userHasPermission(PermissionType::...) or gate every " .
            "data element individually with \$this->userMay(PermissionType::...).",
        );
    }

    /**
     * @return list<ReflectionClass<object>>
     */
    private function administrationPageControllers(): array
    {
        $files = glob(self::CONTROLLER_DIR . '/*.php');
        $this->assertNotFalse($files);
        $this->assertNotEmpty($files, 'No controllers found in ' . self::CONTROLLER_DIR);

        $controllers = [];
        foreach ($files as $file) {
            $class = 'phpMyFAQ\Controller\Administration\\' . basename($file, '.php');
            if (interface_exists($class) || trait_exists($class)) {
                continue;
            }

            $this->assertTrue(
                class_exists($class),
                sprintf('File %s does not contain the expected class %s.', $file, $class),
            );

            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract()) {
                continue;
            }

            $controllers[] = $reflection;
        }

        return $controllers;
    }

    private function methodChecksAPermission(ReflectionMethod $method): bool
    {
        $fileName = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        if ($fileName === false || $startLine === false || $endLine === false) {
            return false;
        }

        $lines = file($fileName);
        if ($lines === false) {
            return false;
        }

        $body = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        foreach (self::PERMISSION_CHECK_METHODS as $checkMethod) {
            if (str_contains($body, '$this->' . $checkMethod . '(')) {
                return true;
            }
        }

        return false;
    }
}
