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
class GroupControllerTest extends TestCase
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
    public function testListGroupsRequiresAuthentication(): void
    {
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->listGroups();
    }

    /**
     * @throws \Exception
     */
    public function testListUsersRequiresAuthentication(): void
    {
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->listUsers();
    }

    /**
     * @throws \Exception
     */
    public function testGroupDataRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->groupData($request);
    }

    /**
     * @throws \Exception
     */
    public function testListMembersRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->listMembers($request);
    }

    /**
     * @throws \Exception
     */
    public function testListPermissionsRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->listPermissions($request);
    }
}
