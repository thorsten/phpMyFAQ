<?php

declare(strict_types=1);

/**
 * The abstract Administration API controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-06
 */

namespace phpMyFAQ\Controller\Administration\Api;

use Override;
use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Controller\AbstractController;

abstract class AbstractAdministrationApiController extends AbstractController
{
    protected AdminLog $adminLog;

    #[Override]
    protected function initializeFromContainer(): void
    {
        parent::initializeFromContainer();

        $adminLog = $this->container->get(id: 'phpmyfaq.admin.admin-log');
        if (!$adminLog instanceof AdminLog) {
            throw new \LogicException('AdminLog service not found in container.');
        }

        $this->adminLog = $adminLog;
    }

    /**
     * Returns true if the acting user may scope the given right to exactly the
     * given languages.
     *
     * An empty restriction set means "unrestricted", so writing one is a
     * privilege *grant*, not a narrowing. A non-SuperAdmin whose own set for the
     * right is restricted may therefore only write a non-empty subset of that
     * set — never an empty list, and never a language they do not hold
     * themselves. SuperAdmins and acting users whose own right is unrestricted
     * (`getAllowedLanguagesForRight()` returns null) are unaffected.
     *
     * @param array<string> $languages
     * @throws \phpMyFAQ\Core\Exception
     */
    protected function mayAssignLanguages(int $rightId, array $languages): bool
    {
        if ($this->currentUser->isSuperAdmin()) {
            return true;
        }

        $allowedLanguages = $this->currentUser->perm->getAllowedLanguagesForRight(
            $this->currentUser->getUserId(),
            $rightId,
        );

        if ($allowedLanguages === null) {
            return true;
        }

        // Clearing the restrictions would widen the right to every language,
        // including ones the acting user does not hold.
        if ($languages === []) {
            return false;
        }

        foreach ($languages as $language) {
            if (!in_array($language, $allowedLanguages, strict: true)) {
                return false;
            }
        }

        return true;
    }
}
