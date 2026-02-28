<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(RatingController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class RatingControllerWebTest extends ControllerWebTestCase
{
    public function testRatingsPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/statistics/ratings');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="pmf-admin-clear-ratings"', $response);
    }
}
