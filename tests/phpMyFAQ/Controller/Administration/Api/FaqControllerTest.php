<?php

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class FaqControllerTest extends TestCase
{
    private const int USER_ID = 23;

    private function resolveActiveState(mixed $active, bool $canApprove): string
    {
        $reflection = new ReflectionClass(FaqController::class);
        $controller = $reflection->newInstanceWithoutConstructor();

        // A user holding every right except, optionally, the "approve FAQ" right.
        $permission = $this->createMock(BasicPermission::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static fn(int $userId, mixed $right) => $canApprove || $right !== PermissionType::FAQ_APPROVE->value,
            );

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('getUserId')->willReturn(self::USER_ID);
        $currentUser->perm = $permission;

        $reflection->getParentClass()->getProperty('currentUser')->setValue($controller, $currentUser);

        return $reflection->getMethod('resolveActiveState')->invoke($controller, $active);
    }

    /**
     * Security regression: the create and update endpoints must not publish a FAQ for users
     * who only hold the "add FAQ" or "edit FAQ" right, publishing requires "approve FAQ".
     */
    #[DataProvider('activeStateProvider')]
    public function testResolveActiveStateRequiresApprovePermission(
        mixed $active,
        bool $canApprove,
        string $expected,
    ): void {
        $this->assertSame($expected, $this->resolveActiveState($active, $canApprove));
    }

    public static function activeStateProvider(): array
    {
        return [
            'publish without approve right is refused' => ['yes', false, 'no'],
            'publish with approve right is kept' => ['yes', true, 'yes'],
            'inactive without approve right stays inactive' => ['no', false, 'no'],
            'inactive with approve right stays inactive' => ['no', true, 'no'],
            'unknown value is treated as inactive' => ['1', true, 'no'],
            'missing value is treated as inactive' => [null, true, 'no'],
        ];
    }
}
