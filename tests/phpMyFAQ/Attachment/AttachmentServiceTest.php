<?php

declare(strict_types=1);

namespace phpMyFAQ\Attachment;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\PdoSqlite;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(AttachmentService::class)]
#[UsesClass(AttachmentFactory::class)]
#[UsesClass(Database::class)]
#[UsesClass(File::class)]
#[UsesClass(PdoSqlite::class)]
#[UsesClass(Translation::class)]
final class AttachmentServiceTest extends TestCase
{
    private Configuration $configuration;
    private CurrentUser $currentUser;
    private Permission $faqPermission;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->currentUser = $this->createMock(CurrentUser::class);
        $this->faqPermission = $this->createMock(Permission::class);

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();
    }

    private function createService(): AttachmentService
    {
        return new AttachmentService($this->configuration, $this->currentUser, $this->faqPermission);
    }

    public function testCanDownloadAttachmentAllowsGuestDownload(): void
    {
        $this->configuration
            ->method('get')
            ->with('records.allowDownloadsForGuests')
            ->willReturn(true);

        $attachment = $this->createMock(AbstractAttachment::class);

        $service = $this->createService();

        self::assertTrue($service->canDownloadAttachment($attachment));
    }

    public function testCanDownloadAttachmentDeniedWhenNotLoggedIn(): void
    {
        $this->configuration->method('get')->willReturn(false);

        $this->currentUser->perm = $this->createBasicPermission([], []);
        $this->currentUser->method('isLoggedIn')->willReturn(false);

        $attachment = $this->createMock(AbstractAttachment::class);
        $attachment->method('getRecordId')->willReturn(1);

        $this->faqPermission->method('get')->willReturn([1]);

        $service = $this->createService();

        self::assertFalse($service->canDownloadAttachment($attachment));
    }

    public function testCanDownloadAttachmentGrantedWithBasicPermissionAndRight(): void
    {
        $this->configuration->method('get')->willReturn(false);

        $this->currentUser->perm = $this->createBasicPermission([
            ['right_id' => 1, 'name' => 'dlattachment'],
        ], [1]);
        $this->currentUser->method('isLoggedIn')->willReturn(true);
        $this->currentUser->method('getUserId')->willReturn(42);

        $attachment = $this->createMock(AbstractAttachment::class);
        $attachment->method('getRecordId')->willReturn(1);

        $this->faqPermission->method('get')->willReturn([42]);

        $service = $this->createService();

        self::assertTrue($service->canDownloadAttachment($attachment));
    }

    public function testCanDownloadAttachmentDeniedWithoutDlattachmentRight(): void
    {
        $this->configuration->method('get')->willReturn(false);

        $this->currentUser->perm = $this->createBasicPermission([
            ['right_id' => 1, 'name' => 'someotherright'],
        ], [1]);
        $this->currentUser->method('isLoggedIn')->willReturn(true);
        $this->currentUser->method('getUserId')->willReturn(42);

        $attachment = $this->createMock(AbstractAttachment::class);
        $attachment->method('getRecordId')->willReturn(1);

        $this->faqPermission->method('get')->willReturn([42]);

        $service = $this->createService();

        self::assertFalse($service->canDownloadAttachment($attachment));
    }

    public function testCanDownloadAttachmentWithMediumPermissionAndMatchingGroup(): void
    {
        $this->configuration->method('get')->willReturn(false);

        $permission = $this->createMock(MediumPermission::class);
        $permission->method('getUserGroups')->willReturn([5, 10]);
        $permission
            ->method('getAllRightsData')
            ->willReturn([
                ['right_id' => 1, 'name' => 'dlattachment'],
            ]);
        $permission->method('getAllUserRights')->willReturn([1]);

        $this->currentUser->perm = $permission;
        $this->currentUser->method('isLoggedIn')->willReturn(true);
        $this->currentUser->method('getUserId')->willReturn(42);

        $attachment = $this->createMock(AbstractAttachment::class);
        $attachment->method('getRecordId')->willReturn(1);

        $this->faqPermission
            ->method('get')
            ->willReturnCallback(static function (string $mode) {
                return match ($mode) {
                    Permission::GROUP => [5, 20],
                    Permission::USER => [42],
                    default => [],
                };
            });

        $service = $this->createService();

        self::assertTrue($service->canDownloadAttachment($attachment));
    }

    public function testCanDownloadAttachmentDeniedWithMediumPermissionAndNoMatchingGroup(): void
    {
        $this->configuration->method('get')->willReturn(false);

        $permission = $this->createMock(MediumPermission::class);
        $permission->method('getUserGroups')->willReturn([5, 10]);
        $permission
            ->method('getAllRightsData')
            ->willReturn([
                ['right_id' => 1, 'name' => 'dlattachment'],
            ]);
        $permission->method('getAllUserRights')->willReturn([1]);

        $this->currentUser->perm = $permission;
        $this->currentUser->method('isLoggedIn')->willReturn(true);
        $this->currentUser->method('getUserId')->willReturn(42);

        $attachment = $this->createMock(AbstractAttachment::class);
        $attachment->method('getRecordId')->willReturn(1);

        $this->faqPermission
            ->method('get')
            ->willReturnCallback(static function (string $mode) {
                return match ($mode) {
                    Permission::GROUP => [99, 100],
                    Permission::USER => [42],
                    default => [],
                };
            });

        $service = $this->createService();

        self::assertFalse($service->canDownloadAttachment($attachment));
    }

    public function testCanDownloadAttachmentDeniedWithEmptyGroupPermission(): void
    {
        $this->configuration->method('get')->willReturn(false);

        $permission = $this->createMock(MediumPermission::class);
        $permission->method('getUserGroups')->willReturn([5]);
        $permission
            ->method('getAllRightsData')
            ->willReturn([
                ['right_id' => 1, 'name' => 'dlattachment'],
            ]);
        $permission->method('getAllUserRights')->willReturn([1]);

        $this->currentUser->perm = $permission;
        $this->currentUser->method('isLoggedIn')->willReturn(true);
        $this->currentUser->method('getUserId')->willReturn(42);

        $attachment = $this->createMock(AbstractAttachment::class);
        $attachment->method('getRecordId')->willReturn(1);

        $this->faqPermission
            ->method('get')
            ->willReturnCallback(static function (string $mode) {
                return match ($mode) {
                    Permission::GROUP => [],
                    Permission::USER => [42],
                    default => [],
                };
            });

        $service = $this->createService();

        self::assertFalse($service->canDownloadAttachment($attachment));
    }

    public function testGetAttachmentThrowsExceptionForInvalidStorageType(): void
    {
        // Set AttachmentFactory storage type to 1 (DATABASE) which hits the default/throw branch
        $ref = new ReflectionClass(AttachmentFactory::class);
        $storageProp = $ref->getProperty('storageType');
        $previousStorage = $storageProp->getValue(null);
        $storageProp->setValue(null, 1);

        try {
            $service = $this->createService();
            $this->expectException(AttachmentException::class);
            $service->getAttachment(999);
        } finally {
            $storageProp->setValue(null, $previousStorage);
        }
    }

    public function testGetAttachmentReturnsFileForValidStorageType(): void
    {
        // Set up Database singleton with a mock
        $dbRef = new ReflectionClass(Database::class);
        $dbProp = $dbRef->getProperty('databaseDriver');
        $previousDb = $dbProp->getValue(null);

        $mockDb = $this->createMock(DatabaseDriver::class);
        $mockDb->method('query')->willReturn(false);
        $dbProp->setValue(null, $mockDb);

        // Set factory to filesystem storage type (0) with no encryption
        $factoryRef = new ReflectionClass(AttachmentFactory::class);
        $storageProp = $factoryRef->getProperty('storageType');
        $previousStorage = $storageProp->getValue(null);
        $storageProp->setValue(null, 0);

        $encProp = $factoryRef->getProperty('encryptionEnabled');
        $previousEnc = $encProp->getValue(null);
        $encProp->setValue(null, false);

        try {
            $service = $this->createService();
            $result = $service->getAttachment(999);

            self::assertInstanceOf(File::class, $result);
        } finally {
            $storageProp->setValue(null, $previousStorage);
            $encProp->setValue(null, $previousEnc);
            $dbProp->setValue(null, $previousDb);
        }
    }

    public function testCanDownloadAttachmentWithMultipleRightsPartialMatch(): void
    {
        $this->configuration->method('get')->willReturn(false);

        // User has right_id 1 (dlattachment) but NOT right_id 2 (addattachment)
        // This exercises the continue branch in getUserRights()
        $this->currentUser->perm = $this->createBasicPermission([
            ['right_id' => 1, 'name' => 'dlattachment'],
            ['right_id' => 2, 'name' => 'addattachment'],
        ], [1]);
        $this->currentUser->method('isLoggedIn')->willReturn(true);
        $this->currentUser->method('getUserId')->willReturn(42);

        $attachment = $this->createMock(AbstractAttachment::class);
        $attachment->method('getRecordId')->willReturn(1);

        $this->faqPermission->method('get')->willReturn([42]);

        $service = $this->createService();

        self::assertTrue($service->canDownloadAttachment($attachment));
    }

    public function testGetAttachmentErrorMessage(): void
    {
        $service = $this->createService();
        $exception = new AttachmentException('file not found');

        $message = $service->getAttachmentErrorMessage($exception);

        self::assertStringContainsString('file not found', $message);
    }

    public function testGetGenericErrorMessage(): void
    {
        $service = $this->createService();

        $message = $service->getGenericErrorMessage();

        self::assertIsString($message);
        self::assertNotEmpty($message);
    }

    private function createBasicPermission(array $rightsData, array $userRights): PermissionInterface
    {
        return new class($rightsData, $userRights) implements PermissionInterface {
            public function __construct(
                private readonly array $rightsData,
                private readonly array $userRights,
            ) {
            }

            public function hasPermission(int $userId, mixed $right): bool
            {
                return false;
            }

            public function getAllRightsData(): array
            {
                return $this->rightsData;
            }

            public function getAllUserRights(int $userId): array
            {
                return $this->userRights;
            }
        };
    }
}
