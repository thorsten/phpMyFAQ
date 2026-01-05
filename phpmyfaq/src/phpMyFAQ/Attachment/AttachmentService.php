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
    ) {
    }

    /**
     * Retrieves an attachment by ID.
     *
     * @throws AttachmentException
     */
    public function getAttachment(int $attachmentId): \phpMyFAQ\Attachment\File
    {
        return AttachmentFactory::create($attachmentId);
    }

    /**
     * Checks if the current user has permission to download an attachment.
     */
    public function canDownloadAttachment(AbstractAttachment $attachment): bool
    {
        // Allow downloads for guests if configured
        if ($this->configuration->get('records.allowDownloadsForGuests')) {
            return true;
        }

        // Check group and user permissions
        $hasGroupPermission = $this->checkGroupPermission($attachment);
        $hasUserPermission = $this->checkUserPermission($attachment);
        $userRights = $this->getUserRights();

        return (
            ($hasGroupPermission || $hasGroupPermission && $hasUserPermission)
            && isset($userRights['dlattachment'])
            && $userRights['dlattachment']
        );
    }

    /**
     * Checks group permission for an attachment.
     */
    private function checkGroupPermission(AbstractAttachment $attachment): bool
    {
        $groupPermission = $this->faqPermission->get(Permission::GROUP, $attachment->getRecordId());

        if (!$this->currentUser->perm instanceof MediumPermission) {
            return true;
        }

        if ($groupPermission === []) {
            return false;
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
            $permission[$right['name']] = false;
        }

        // Check user rights, set true
        $allUserRights = $this->currentUser->perm->getAllUserRights($this->currentUser->getUserId());
        foreach ($allRights as $allRight) {
            if (!in_array($allRight['right_id'], $allUserRights, strict: true)) {
                continue;
            }

            $permission[$allRight['name']] = true;
        }

        return $permission;
    }

    /**
     * Gets an error message for attachment exceptions.
     */
    public function getAttachmentErrorMessage(AttachmentException $attachmentException): string
    {
        return Translation::get(key: 'msgAttachmentInvalid') . ' (' . $attachmentException->getMessage() . ')';
    }

    /**
     * Gets generic attachment error message.
     */
    public function getGenericErrorMessage(): string
    {
        return Translation::get(key: 'msgAttachmentInvalid');
    }
}
