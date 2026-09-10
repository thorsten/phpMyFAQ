<?php

/**
 * Attachment service for handling attachment retrieval and permissions.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-01-01
 */

declare(strict_types=1);

namespace phpMyFAQ\Attachment;

use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

/**
 * Service for attachment operations and permission checks.
 */
final readonly class AttachmentService
{
    public function __construct(
        private Configuration $configuration,
        private CurrentUser $currentUser,
        private Permission $faqPermission,
        private Faq $faq,
    ) {
    }

    /**
     * Retrieves an attachment by ID.
     *
     * @throws AttachmentException
     */
    public function getAttachment(int $attachmentId): File
    {
        return AttachmentFactory::create($attachmentId);
    }

    /**
     * Checks if the current user has permission to download an attachment.
     *
     * The parent FAQ record must be visible to the requester (published, within
     * its date window, and permitted by its ACL) — an attachment must never be
     * retrievable while its parent FAQ answers with 404. The per-record ACL
     * (group and user permission) is then enforced explicitly as well.
     * records.allowDownloadsForGuests only ever waives the "dlattachment" right
     * requirement for anonymous visitors — it must never bypass the ACL itself,
     * and it must never affect logged-in users, who always need their own right.
     */
    public function canDownloadAttachment(AbstractAttachment $attachment): bool
    {
        if (!$this->isParentFaqVisible($attachment)) {
            return false;
        }

        if (!$this->checkGroupPermission($attachment) || !$this->checkUserPermission($attachment)) {
            return false;
        }

        if (!$this->currentUser->isLoggedIn()) {
            return $this->configuration->get('records.allowDownloadsForGuests') === true;
        }

        $userRights = $this->getUserRights();

        return $userRights['dlattachment'] ?? false;
    }

    /**
     * Checks whether the FAQ record the attachment belongs to is visible to the
     * current user, applying the same publication-state, date window and ACL
     * rules as the FAQ page and the public API.
     */
    private function isParentFaqVisible(AbstractAttachment $attachment): bool
    {
        [$userId, $groups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $this->faq->setUser($userId);
        $this->faq->setGroups($groups);

        return $this->faq->isFaqAccessibleForUser($attachment->getRecordId(), $attachment->getRecordLang());
    }

    /**
     * Checks group permission for an attachment.
     */
    private function checkGroupPermission(AbstractAttachment $attachment): bool
    {
        if (!$this->currentUser->perm instanceof MediumPermission) {
            return true;
        }

        $groupPermission = $this->faqPermission->get(Permission::GROUP, $attachment->getRecordId());

        if ($groupPermission === []) {
            return false;
        }

        // -1 means "all groups"
        if (in_array(-1, $groupPermission, strict: true)) {
            return true;
        }

        foreach ($this->currentUser->perm->getUserGroups($this->currentUser->getUserId()) as $userGroup) {
            if (!in_array($userGroup, $groupPermission, strict: true)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Checks user permission for an attachment.
     */
    private function checkUserPermission(AbstractAttachment $attachment): bool
    {
        $userPermission = $this->faqPermission->get(Permission::USER, $attachment->getRecordId());

        // -1 means "all users"
        if (in_array(-1, $userPermission, strict: true)) {
            return true;
        }

        return in_array($this->currentUser->getUserId(), $userPermission, strict: true);
    }

    /**
     * Gets all user rights.
     *
     * @return array<string, bool>
     */
    private function getUserRights(): array
    {
        $permission = [];

        if (!$this->currentUser->isLoggedIn()) {
            return $permission;
        }

        // Read all rights, set false
        $allRights = $this->currentUser->perm->getAllRightsData();
        foreach ($allRights as $right) {
            $permission[(string) $right['name']] = false;
        }

        // Check user rights, set true
        $allUserRights = $this->currentUser->perm->getAllUserRights($this->currentUser->getUserId());
        foreach ($allRights as $allRight) {
            if (!in_array($allRight['right_id'], $allUserRights, strict: true)) {
                continue;
            }

            $permission[(string) $allRight['name']] = true;
        }

        return $permission;
    }

    /**
     * Gets an error message for attachment exceptions.
     */
    public function getAttachmentErrorMessage(AttachmentException $attachmentException): string
    {
        return Translation::getString(key: 'msgAttachmentInvalid') . ' (' . $attachmentException->getMessage() . ')';
    }

    /**
     * Gets generic attachment error message.
     */
    public function getGenericErrorMessage(): string
    {
        $message = Translation::get(key: 'msgAttachmentInvalid');

        return is_string($message) ? $message : '';
    }
}
