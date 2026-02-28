<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(NewsController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class NewsControllerWebTest extends ControllerWebTestCase
{
    public function testNewsPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/news');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="confirmDeleteNewsModal"', $response);
    }
}
