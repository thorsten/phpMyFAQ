<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(BackupController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class BackupControllerWebTest extends ControllerWebTestCase
{
    public function testBackupPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/backup');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('./backup/export/content', $response);
    }
}
