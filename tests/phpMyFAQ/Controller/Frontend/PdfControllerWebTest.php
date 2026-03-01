<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(PdfController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(\phpMyFAQ\Export\Pdf::class)]
#[UsesClass(\phpMyFAQ\Export\Pdf\Wrapper::class)]
final class PdfControllerWebTest extends ControllerWebTestCase
{
    public function testPdfRouteReturnsPdfResponseForUnknownFaq(): void
    {
        $response = $this->requestPublic('GET', '/pdf/999999/999999/en');

        self::assertResponseStatusCodeSame(200, $response);
        self::assertSame('application/pdf', $response->headers->get('Content-Type'));
        self::assertStringStartsWith('%PDF-', (string) $response->getContent());
    }
}
