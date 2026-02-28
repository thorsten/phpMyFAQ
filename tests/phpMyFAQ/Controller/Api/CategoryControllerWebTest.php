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
        $this->overrideConfigurationValues(['api.enableAccess' => false], 'api');

        $response = $this->requestApi('GET', '/v3.2/categories');

        self::assertResponseStatusCodeSame(401, $response);
        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }

    public function testCategoriesEndpointReturnsJson(): void
    {
        $response = $this->requestApi('GET', '/v3.2/categories');

        self::assertResponseIsSuccessful($response);
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }

    public function testCreateWithoutTokenReturnsUnauthorizedJson(): void
    {
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
        $this->overrideConfigurationValues([
            'api.enableAccess' => true,
            'api.apiClientToken' => 'test-token',
        ], 'api');

        $response = $this->requestApiJson('POST', '/v3.2/category', [
            'language' => 'en',
            'parent-id' => 0,
            'parent-category-name' => 'definitely-missing-parent-category',
            'category-name' => 'Test Category',
            'description' => 'Test Description',
            'user-id' => 1,
            'group-id' => -1,
            'is-active' => true,
            'show-on-homepage' => false,
        ], [
            'HTTP_X_PMF_TOKEN' => 'test-token',
        ]);

        self::assertResponseStatusCodeSame(409, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
        self::assertStringContainsString('parent category name was not found', (string) $response->getContent());
    }
}
