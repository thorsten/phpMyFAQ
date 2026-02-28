<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(AutoCompleteController::class)]
#[UsesNamespace('phpMyFAQ')]
final class AutoCompleteControllerWebTest extends ControllerWebTestCase
{
    public function testAutocompleteReturnsNotFoundForMissingSearchTerm(): void
    {
        $response = $this->requestApi('GET', '/autocomplete');

        self::assertResponseStatusCodeSame(404, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
        self::assertSame('[]', trim((string) $response->getContent()));
    }
}
