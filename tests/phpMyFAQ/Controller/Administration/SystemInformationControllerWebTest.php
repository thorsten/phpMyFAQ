<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(SystemInformationController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class SystemInformationControllerWebTest extends ControllerWebTestCase
{
    public function testSystemPageRenders(): void
    {
        $this->overrideConfigurationValues([
            'search.enableElasticsearch' => false,
            'search.enableOpenSearch' => false,
            'storage.useRedisForConfiguration' => false,
        ], 'admin');

        $response = $this->requestAdmin('GET', '/system');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="pmf-system-information"', $response);
    }
}
