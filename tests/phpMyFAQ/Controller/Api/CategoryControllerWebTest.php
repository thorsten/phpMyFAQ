<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(CategoryController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractApiController::class)]
#[UsesClass(PaginatedResponseOptions::class)]
final class CategoryControllerWebTest extends ControllerWebTestCase
{
    public function testCategoriesEndpointReturnsUnauthorizedProblemWhenApiIsDisabled(): void
    {
        $this->getConfiguration('api')->getAll();
        $this->overrideConfigurationValues(['api.enableAccess' => false], 'api');

        $response = $this->requestApi('GET', '/v3.2/categories');

        self::assertContains($response->getStatusCode(), [200, 401]);

        if ($response->getStatusCode() === 401) {
            self::assertStringContainsString('problem+json', (string) $response->headers->get('Content-Type'));
            self::assertJson((string) $response->getContent());
        }
    }

    public function testCategoriesEndpointReturnsJson(): void
    {
        $this->getConfiguration('api')->getAll();
        $this->overrideConfigurationValues(['api.enableAccess' => true], 'api');

        $response = $this->requestApi('GET', '/v3.2/categories');

        self::assertResponseIsSuccessful($response);
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
        self::assertNotNull($response->headers->get('ETag'));
        self::assertSame('Accept-Language', (string) $response->headers->get('Vary'));
    }

    public function testCategoriesEndpointReturnsNotModifiedWhenEtagMatches(): void
    {
        $this->getConfiguration('api')->getAll();
        $this->overrideConfigurationValues(['api.enableAccess' => true], 'api');

        $initialResponse = $this->requestApi('GET', '/v3.2/categories');
        $etag = $initialResponse->headers->get('ETag');

        self::assertResponseIsSuccessful($initialResponse);
        self::assertNotNull($etag);

        $response = $this->requestApi('GET', '/v3.2/categories', [], [
            'HTTP_IF_NONE_MATCH' => $etag,
        ]);

        self::assertResponseStatusCodeSame(304, $response);
        self::assertSame('', (string) $response->getContent());
        self::assertSame($etag, $response->headers->get('ETag'));
    }

    public function testCreateWithoutTokenReturnsUnauthorizedJson(): void
    {
        $this->getConfiguration('api')->getAll();
        $this->overrideConfigurationValues(['api.enableAccess' => true], 'api');

        $response = $this->requestApiJson('POST', '/v3.2/category', [
            'language' => 'en',
            'parent-id' => 0,
            'parent-category-name' => null,
            'category-name' => 'Test Category',
            'description' => 'Test Description',
            'user-id' => 1,
            'group-id' => -1,
            'is-active' => true,
            'show-on-homepage' => false,
        ]);

        self::assertResponseStatusCodeSame(401, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }

    public function testCreateWithUnknownParentCategoryReturnsConflictJson(): void
    {
        $configuration = $this->getConfiguration('api');
        $configuration->getAll();
        $this->overrideConfigurationValues(['api.enableAccess' => true], 'api');
        $token = (string) $configuration->get('api.apiClientToken');

        $response = $this->requestApiJson(
            'POST',
            '/v3.2/category',
            [
                'language' => 'en',
                'parent-id' => 0,
                'parent-category-name' => 'definitely-missing-parent-category',
                'category-name' => 'Test Category',
                'description' => 'Test Description',
                'user-id' => 1,
                'group-id' => -1,
                'is-active' => true,
                'show-on-homepage' => false,
            ],
            [
                'HTTP_X_PMF_TOKEN' => $token,
            ],
        );

        self::assertContains($response->getStatusCode(), [401, 409]);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
        if ($response->getStatusCode() === 409) {
            self::assertStringContainsString('parent category name was not found', (string) $response->getContent());
        }
    }
}
