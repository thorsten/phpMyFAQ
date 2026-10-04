<?php

declare(strict_types=1);

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;

/**
 * Saves user data against a private copy of the SQLite test database, including the
 * fallback for installations whose faquserdata table predates the identity link columns.
 */
#[CoversClass(UserData::class)]
#[UsesNamespace('phpMyFAQ')]
final class UserDataSaveTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();
    }

    private function dropIdentityLinkColumns(): void
    {
        $db = $this->configuration->getDb();
        foreach (['keycloak_sub', 'entra_oid'] as $column) {
            $this->assertNotFalse($db->query(sprintf(
                'ALTER TABLE %sfaquserdata DROP COLUMN %s',
                Database::getTablePrefix(),
                $column,
            )));
        }
    }

    private function storedDisplayName(int $userId): string
    {
        $db = $this->configuration->getDb();
        $row = $db->fetchArray($db->query(sprintf(
            'SELECT display_name FROM %sfaquserdata WHERE user_id = %d',
            Database::getTablePrefix(),
            $userId,
        )));

        return (string) ($row['display_name'] ?? '');
    }

    public function testSaveStoresTheIdentityLinkOnACurrentSchema(): void
    {
        $userData = new UserData($this->configuration);
        $this->assertTrue($userData->load(1));

        $this->assertTrue($userData->set(['display_name', 'keycloak_sub'], ['Linked Admin', 'kc-subject-1']));

        $reloaded = new UserData($this->configuration);
        $this->assertTrue($reloaded->load(1));
        $this->assertSame('Linked Admin', $reloaded->get('display_name'));
        $this->assertSame('kc-subject-1', $reloaded->get('keycloak_sub'));
    }

    public function testSaveFallsBackToTheLegacyColumnsWithoutAnIdentityLink(): void
    {
        $userData = new UserData($this->configuration);
        $this->assertTrue($userData->load(1));
        $this->dropIdentityLinkColumns();

        $this->assertTrue($userData->set(['display_name', 'email'], ['Legacy Admin', 'legacy@example.org']));
        $this->assertSame('Legacy Admin', $this->storedDisplayName(1));
    }

    public function testSaveRefusesToDropAnIdentityLinkOnALegacySchema(): void
    {
        $userData = new UserData($this->configuration);
        $this->assertTrue($userData->load(1));
        $this->dropIdentityLinkColumns();

        $this->assertFalse($userData->set(['display_name', 'entra_oid'], ['Linked Admin', 'entra-object-1']));
        $this->assertNotSame('Linked Admin', $this->storedDisplayName(1));
    }
}
