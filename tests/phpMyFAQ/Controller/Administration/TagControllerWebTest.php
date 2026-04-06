<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(TagController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class TagControllerWebTest extends ControllerWebTestCase
{
    public function testTagsPageRenders(): void
    {
        $response = $this->requestAdminGuest('GET', '/tags');

        self::assertResponseStatusCodeSame(302, $response);
        self::assertRedirectLocationContains('/login', $response);
    }
}
