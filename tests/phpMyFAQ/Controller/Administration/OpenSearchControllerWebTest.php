<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(OpenSearchController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractAdministrationController::class)]
final class OpenSearchControllerWebTest extends ControllerWebTestCase
{
    public function testOpenSearchPageRendersWhenFeatureEnabled(): void
    {
        $this->overrideConfigurationValues(['search.enableOpenSearch' => true], 'admin');

        $response = $this->requestAdmin('GET', '/opensearch');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('id="pmf-opensearch-result"', $response);
    }
}
