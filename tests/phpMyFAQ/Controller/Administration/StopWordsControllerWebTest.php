<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(StopWordsController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class StopWordsControllerWebTest extends ControllerWebTestCase
{
    public function testStopWordsPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/stopwords');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Stop Words', $response);
    }
}
