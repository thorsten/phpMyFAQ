<?php

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Plugin\PluginException;
use PHPUnit\Framework\TestCase;

class CurrentUserTraitsTest extends TestCase
{
    public function testCurrentUserClassUsesNewTraits(): void
    {
        $usedTraits = class_uses(CurrentUser::class);

        $this->assertContains(CurrentUserSessionLookupTrait::class, $usedTraits);
        $this->assertContains(CurrentUserAccountStateTrait::class, $usedTraits);
    }

    public function testGetCurrentUserGroupIdWithNullUserReturnsDefaultValues(): void
    {
        $result = CurrentUser::getCurrentUserGroupId(null);

        $this->assertSame([-1, [-1]], $result);
    }

    /**
     * @throws PluginException
     * @throws Exception
     * @throws \Exception
     */
    public function testGetCurrentUserReturnsCurrentUserInstance(): void
    {
        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);

        $result = CurrentUser::getCurrentUser($configuration);

        $this->assertInstanceOf(CurrentUser::class, $result);
    }
}
