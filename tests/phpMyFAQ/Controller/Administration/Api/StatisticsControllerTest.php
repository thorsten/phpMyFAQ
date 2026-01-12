<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class StatisticsControllerTest extends TestCase
{
    private Configuration $configuration;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->configuration = Configuration::getConfigurationInstance();
    }

    /**
     * @throws Exception|\JsonException
     */
    public function testTruncateSessionsRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrfToken' => 'test-token',
            'month' => '2024-01',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new StatisticsController();

        $this->expectException(\Exception::class);
        $controller->truncateSessions($request);
    }

    /**
     * @throws Exception|\JsonException
     */
    public function testTruncateSessionsWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new StatisticsController();

        $this->expectException(\Exception::class);
        $controller->truncateSessions($request);
    }

    /**
     * @throws Exception|\JsonException
     */
    public function testTruncateSearchTermsRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrfToken' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new StatisticsController();

        $this->expectException(\Exception::class);
        $controller->truncateSearchTerms($request);
    }

    /**
     * @throws Exception|\JsonException
     */
    public function testTruncateSearchTermsWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new StatisticsController();

        $this->expectException(\Exception::class);
        $controller->truncateSearchTerms($request);
    }

    /**
     * @throws \Exception
     */
    public function testClearRatingsRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrfToken' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new StatisticsController();

        $this->expectException(\Exception::class);
        $controller->clearRatings($request);
    }

    /**
     * @throws \Exception
     */
    public function testClearRatingsWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new StatisticsController();

        $this->expectException(\Exception::class);
        $controller->clearRatings($request);
    }

    /**
     * @throws \Exception
     */
    public function testClearVisitsRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrfToken' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new StatisticsController();

        $this->expectException(\Exception::class);
        $controller->clearVisits($request);
    }

    /**
     * @throws \Exception
     */
    public function testClearVisitsWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new StatisticsController();

        $this->expectException(\Exception::class);
        $controller->clearVisits($request);
    }
}
