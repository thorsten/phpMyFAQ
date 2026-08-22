<?php

namespace phpMyFAQ\Attachment;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class AttachmentPermissionTest
 *
 * Regression coverage for the attachment download authorization bypass:
 * the -1 "all users/groups" sentinel must be honored, group restrictions must
 * not default to "allowed" in basic mode, and records.allowDownloadsForGuests
 * must never widen the per-record ACL.
 *
 * @package phpMyFAQ\Attachment
 */
class AttachmentPermissionTest extends TestCase
{
    /**
     * @param int[] $userPermission
     * @param int[] $groupPermission
     * @param int[] $userGroups
     */
    #[DataProvider('recordAccessProvider')]
    public function testHasRecordAccess(
        string $case,
        bool $expected,
        int $userId,
        array $userPermission,
        array $groupPermission,
        array $userGroups,
        bool $groupSupport,
    ): void {
        $this->assertSame(
            $expected,
            AttachmentPermission::hasRecordAccess(
                $userId,
                $userPermission,
                $groupPermission,
                $userGroups,
                $groupSupport,
            ),
            $case,
        );
    }

    /**
     * @return array<int, array{0: string, 1: bool, 2: int, 3: int[], 4: int[], 5: int[], 6: bool}>
     */
    public static function recordAccessProvider(): array
    {
        return [
            // case, expected, userId, userPermission, groupPermission, userGroups, groupSupport

            // Public FAQ (-1 = all users): everyone including guests (userId -1) may access.
            ['guest, public FAQ', true, -1, [-1], [-1], [], false],
            ['user, public FAQ (basic)', true, 5, [-1], [], [], false],
            ['user, public FAQ (medium)', true, 5, [-1], [-1], [2], true],

            // FAQ restricted to user 1: only user 1 passes, not another user, not a guest.
            ['owner, user-restricted FAQ', true, 1, [1], [], [], false],
            ['other user, user-restricted FAQ (basic)', false, 5, [1], [], [], false],
            ['guest, user-restricted FAQ', false, -1, [1], [], [], false],

            // Group restrictions are only enforced when the layer supports groups.
            ['member, group-restricted FAQ (medium)', true, 5, [], [7], [7, 9], true],
            ['non-member, group-restricted FAQ (medium)', false, 5, [], [7], [3, 9], true],
            ['all-groups sentinel, member (medium)', true, 5, [], [-1], [3], true],
            // Basic mode ignores group rows entirely - it must NOT default to allowed.
            ['group-restricted FAQ in basic mode', false, 5, [], [7], [], false],
            // No group support but a matching group id must still be ignored.
            ['group match but no group support', false, 5, [], [7], [7], false],

            // Empty ACL rows grant nobody (except via the sentinel, absent here).
            ['no permissions at all', false, 5, [], [], [], true],
        ];
    }

    #[DataProvider('downloadRightProvider')]
    public function testHasDownloadRight(
        string $case,
        bool $expected,
        bool $isLoggedIn,
        bool $hasDownloadAttachmentRight,
        bool $allowDownloadsForGuests,
    ): void {
        $this->assertSame(
            $expected,
            AttachmentPermission::hasDownloadRight($isLoggedIn, $hasDownloadAttachmentRight, $allowDownloadsForGuests),
            $case,
        );
    }

    /**
     * @return array<int, array{0: string, 1: bool, 2: bool, 3: bool, 4: bool}>
     */
    public static function downloadRightProvider(): array
    {
        return [
            // case, expected, isLoggedIn, hasDownloadAttachmentRight, allowDownloadsForGuests
            ['logged-in with dlattachment right',    true,  true,  true,  false],
            ['logged-in without dlattachment right', false, true,  false, true],
            ['guest, guest downloads disabled',      false, false, false, false],
            ['guest, guest downloads enabled',       true,  false, false, true],
        ];
    }
}
