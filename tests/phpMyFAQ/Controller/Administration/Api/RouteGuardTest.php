<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Structural regression test for the Administration API.
 *
 * The Administration namespace gate only requires a logged-in session, so the
 * per-endpoint guard calls inside each controller method are the only barrier
 * between a zero-rights account and admin data (CWE-862). This test asserts
 * that every routed method in the Administration\Api namespace invokes one of
 * the guards from AbstractController, so a newly added endpoint without a
 * guard fails CI instead of shipping.
 */
#[CoversNothing]
final class RouteGuardTest extends TestCase
{
    /**
     * Guard methods on AbstractController that authenticate or authorize the
     * current user and throw when the check fails.
     */
    private const array GUARD_METHODS = [
        'userIsAuthenticated',
        'userIsSuperAdmin',
        'userHasGroupPermission',
        'userHasUserPermission',
        'userHasPermission',
        'userHasPermissionForCategories',
        'userHasPermissionForLanguage',
        'userMayPublish',
        'userHasAnyPermission',
        'hasValidToken',
    ];

    private const string CONTROLLER_DIR = PMF_SRC_DIR . '/phpMyFAQ/Controller/Administration/Api';

    public function testGuardListMatchesAbstractControllerMethods(): void
    {
        $abstractController = new ReflectionClass(\phpMyFAQ\Controller\AbstractController::class);

        foreach (self::GUARD_METHODS as $guardMethod) {
            $this->assertTrue(
                $abstractController->hasMethod($guardMethod),
                sprintf(
                    'Guard "%s" no longer exists on AbstractController — update GUARD_METHODS in %s ' .
                    'so the route guard check keeps matching real guards.',
                    $guardMethod,
                    self::class,
                ),
            );
        }
    }

    public function testEveryRoutedAdministrationApiMethodInvokesAGuard(): void
    {
        $routedMethods = 0;
        $unguarded = [];

        foreach ($this->administrationApiControllers() as $controller) {
            foreach ($controller->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getAttributes(Route::class) === []) {
                    continue;
                }

                ++$routedMethods;

                if (!$this->methodInvokesAGuard($method)) {
                    $unguarded[] = sprintf(
                        '%s::%s() (%s:%d)',
                        $controller->getShortName(),
                        $method->getName(),
                        $method->getFileName(),
                        $method->getStartLine(),
                    );
                }
            }
        }

        $this->assertGreaterThan(0, $routedMethods, 'No routed Administration API methods found — discovery is broken.');
        $this->assertSame(
            [],
            $unguarded,
            "The following Administration API endpoints enforce no permission check (CWE-862):\n  - " .
            implode("\n  - ", $unguarded) .
            "\nAdd a guard call such as \$this->userHasPermission(PermissionType::...) as the first statement.",
        );
    }

    /**
     * @return list<ReflectionClass<object>>
     */
    private function administrationApiControllers(): array
    {
        $files = glob(self::CONTROLLER_DIR . '/*.php');
        $this->assertNotFalse($files);
        $this->assertNotEmpty($files, 'No controllers found in ' . self::CONTROLLER_DIR);

        $controllers = [];
        foreach ($files as $file) {
            $class = 'phpMyFAQ\Controller\Administration\Api\\' . basename($file, '.php');
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

    private function methodInvokesAGuard(ReflectionMethod $method): bool
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

        foreach (self::GUARD_METHODS as $guardMethod) {
            if (str_contains($body, '$this->' . $guardMethod . '(')) {
                return true;
            }
        }

        return false;
    }
}
