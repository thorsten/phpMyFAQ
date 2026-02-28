<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(AttachmentsController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class AttachmentsControllerWebTest extends ControllerWebTestCase
{
    public function testAttachmentsPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/attachments');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="attachment-table"', $response);
    }
}
