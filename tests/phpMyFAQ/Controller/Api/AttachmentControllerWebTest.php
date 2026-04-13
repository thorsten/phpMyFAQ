<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(AttachmentController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractApiController::class)]
#[UsesClass(PaginatedResponseOptions::class)]
final class AttachmentControllerWebTest extends ControllerWebTestCase
{
    public function testAttachmentsEndpointReturnsJson(): void
    {
        $this->overrideConfigurationValues(['api.enableAccess' => true], 'api');

        $response = $this->requestApi('GET', '/v4.0/attachments/1');

        self::assertResponseIsSuccessful($response);
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
