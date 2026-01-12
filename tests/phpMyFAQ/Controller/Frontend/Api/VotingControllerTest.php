<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class VotingControllerTest extends TestCase
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
     * @throws Exception
     */
    public function testCreateWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new VotingController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     */
    public function testCreateWithMissingIdThrowsException(): void
    {
        $requestData = json_encode([
            'value' => 5,
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new VotingController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     */
    public function testCreateWithMissingValueThrowsException(): void
    {
        $requestData = json_encode([
            'id' => 1,
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new VotingController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     */
    public function testCreateWithInvalidVoteValueThrowsException(): void
    {
        $requestData = json_encode([
            'id' => 1,
            'value' => 10,
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $controller = new VotingController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     */
    public function testCreateWithNegativeVoteValueThrowsException(): void
    {
        $requestData = json_encode([
            'id' => 1,
            'value' => -1,
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $controller = new VotingController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     */
    public function testCreateWithZeroVoteValueThrowsException(): void
    {
        $requestData = json_encode([
            'id' => 1,
            'value' => 0,
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $controller = new VotingController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     */
    public function testCreateWithValidVoteValueThrowsException(): void
    {
        $requestData = json_encode([
            'id' => 1,
            'value' => 3,
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $controller = new VotingController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }
}
