<?php

/**
 * Authorization decisions for attachment downloads.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    phpMyFAQ Team
 * @since     2026-08-22
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @copyright 2026 phpMyFAQ Team
 */

declare(strict_types=1);

namespace phpMyFAQ\Attachment;

/**
 * Class AttachmentPermission
 *
 * Encapsulates the two independent gates that guard an attachment download so
 * they can be reasoned about (and tested) in isolation:
 *
 *  1. the per-record ACL (which users/groups may see the FAQ the attachment
 *     belongs to), and
 *  2. the download-right gate (a logged-in user needs the "dlattachment" right,
 *     an anonymous visitor needs records.allowDownloadsForGuests).
 *
 * Both gates must be satisfied for a download to be served; the guests flag
 * only relaxes gate 2 and must never widen gate 1.
 *
 * @package phpMyFAQ\Attachment
 */
class AttachmentPermission
{
    /**
     * Sentinel used throughout phpMyFAQ to mean "all users" / "all groups"
     * in faqdata_user / faqdata_group rows.
     */
    private const int ALL_PERMISSION = -1;

    /**
     * Decides whether the given user may see the record the attachment belongs
     * to, mirroring the canonical semantics of Faq\QueryHelper::queryPermission()
     * and Search\SearchResultSet (including the -1 "all" sentinel).
     *
     * @param int   $userId          Current user's ID (-1 for anonymous users)
     * @param int[] $userPermission  User IDs allowed on the record (-1 = all users)
     * @param int[] $groupPermission Group IDs allowed on the record (-1 = all groups)
     * @param int[] $userGroups      Groups the current user belongs to
     * @param bool  $groupSupport    Whether the permission layer supports groups
     *                               (security.permLevel other than "basic")
     */
    public static function hasRecordAccess(
        int $userId,
        array $userPermission,
        array $groupPermission,
        array $userGroups,
        bool $groupSupport,
    ): bool {
        if (
            in_array(self::ALL_PERMISSION, $userPermission, strict: true)
            || in_array($userId, $userPermission, strict: true)
        ) {
            return true;
        }

        if (!$groupSupport || $groupPermission === []) {
            return false;
        }

        if (in_array(self::ALL_PERMISSION, $groupPermission, strict: true)) {
            return true;
        }

        foreach ($userGroups as $userGroup) {
            if (in_array($userGroup, $groupPermission, strict: true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Decides whether the current visitor passes the download-right gate.
     *
     * Logged-in users need the "dlattachment" right; anonymous visitors are only
     * allowed when records.allowDownloadsForGuests is enabled. This never
     * overrides the per-record ACL enforced by hasRecordAccess().
     */
    public static function hasDownloadRight(
        bool $isLoggedIn,
        bool $hasDownloadAttachmentRight,
        bool $allowDownloadsForGuests,
    ): bool {
        return $isLoggedIn ? $hasDownloadAttachmentRight : $allowDownloadsForGuests;
    }
}
