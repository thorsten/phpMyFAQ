<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(ElasticsearchController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class ElasticsearchControllerWebTest extends ControllerWebTestCase
{
    public function testElasticsearchPageHandlesAnonymousAccess(): void
    {
        $this->overrideConfigurationValues(['search.enableElasticsearch' => true], 'admin');

        $response = $this->requestAdmin('GET', '/elasticsearch');

        self::assertContains($response->getStatusCode(), [200, 302]);

        if ($response->getStatusCode() === 302) {
            self::assertRedirectLocationContains('/login', $response);
            return;
        }

        self::assertResponseContains('id="pmf-elasticsearch-result"', $response);
    }
}
