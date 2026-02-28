<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(ContactController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ContactControllerWebTest extends ControllerWebTestCase
{
    public function testContactPageRenders(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);

        $response = $this->requestPublic('GET', '/contact.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Contact', $response);
    }
}
