<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(PasswordChangeController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class PasswordChangeControllerWebTest extends ControllerWebTestCase
{
    public function testPasswordChangePageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/password/change');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="faqpassword_old"', $response);
    }
}
