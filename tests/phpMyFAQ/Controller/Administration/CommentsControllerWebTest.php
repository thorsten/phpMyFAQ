<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(CommentsController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class CommentsControllerWebTest extends ControllerWebTestCase
{
    public function testCommentsPageRenders(): void
    {
        $response = $this->requestAdmin('GET', '/comments');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="commentsContent"', $response);
    }
}
