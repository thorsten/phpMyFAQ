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
    /**
     * An anonymous request for an FAQ that does not exist, or that the requester may not
     * read, must not produce a PDF: the export used to render the access-denied
     * placeholder record, which still carried the title, solution id and author.
     */
    public function testPdfRouteReturnsNotFoundForUnknownFaq(): void
    {
        $response = $this->requestPublic('GET', '/pdf/999999/999999/en');

        self::assertResponseStatusCodeSame(404, $response);
        self::assertSame('', (string) $response->getContent());
    }
}
