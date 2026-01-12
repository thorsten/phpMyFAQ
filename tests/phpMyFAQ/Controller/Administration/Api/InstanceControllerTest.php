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
class InstanceControllerTest extends TestCase
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
     * @throws \Exception
     */
    public function testAddRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrf' => 'test-token',
            'url' => 'test.example.com',
            'instance' => 'test',
            'comment' => 'Test instance',
            'email' => 'test@example.com',
            'admin' => 'admin',
            'password' => 'password123',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new InstanceController();

        $this->expectException(\Exception::class);
        $controller->add($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token', 'instanceId' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new InstanceController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testAddWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new InstanceController();

        $this->expectException(\Exception::class);
        $controller->add($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new InstanceController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testAddWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode([
            'url' => 'test.example.com',
            'instance' => 'test',
            'comment' => 'Test instance',
            'email' => 'test@example.com',
            'admin' => 'admin',
            'password' => 'password123',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new InstanceController();

        $this->expectException(\Exception::class);
        $controller->add($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode(['instanceId' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new InstanceController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }
}
