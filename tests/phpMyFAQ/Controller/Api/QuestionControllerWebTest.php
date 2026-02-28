<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use phpMyFAQ\Functional\ControllerWebTestCase;

#[CoversClass(QuestionController::class)]
#[UsesNamespace('phpMyFAQ')]
final class QuestionControllerWebTest extends ControllerWebTestCase
{
    public function testCreateWithoutTokenReturnsUnauthorizedJson(): void
    {
        $this->overrideConfigurationValues(['api.enableAccess' => true], 'api');

        $response = $this->requestApiJson('POST', '/v3.2/question', [
            'category-id' => 1,
            'question' => 'Test question',
            'author' => 'Test Author',
            'email' => 'test@example.com',
        ]);

        self::assertResponseStatusCodeSame(401, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
