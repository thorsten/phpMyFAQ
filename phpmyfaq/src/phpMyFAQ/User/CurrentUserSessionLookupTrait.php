<?php

/**
 * Trait for looking up the current user from session or cookie
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
 * @since     2026-02-24
 */

declare(strict_types=1);

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Session\SessionWrapper;
use Symfony\Component\HttpFoundation\Request;

trait CurrentUserSessionLookupTrait
{
    /**
     * Returns the current user object from cookie or session
     *
     * @throws Exception
     */
    public static function getCurrentUser(Configuration $configuration): CurrentUser
    {
        $user = self::getFromCookie($configuration);

        if (!$user instanceof CurrentUser) {
            $user = self::getFromSession($configuration);
        }

        if ($user instanceof CurrentUser) {
            $user->setLoggedIn(true);
        } else {
            $user = new CurrentUser($configuration);
        }

        return $user;
    }

    /**
     * Returns the current user ID and group IDs as an array, default values are -1
     *
     * @return array{0: int, 1: int[]}
     */
    public static function getCurrentUserGroupId(?CurrentUser $user = null): array
    {
        if ($user !== null) {
            $currentUser = $user->getUserId();
            if ($user->perm instanceof MediumPermission) {
                $currentGroups = $user->perm->getUserGroups($currentUser);
            } else {
                $currentGroups = [-1];
            }

            if ($currentGroups === []) {
                $currentGroups = [-1];
            }
        } else {
            $currentUser = -1;
            $currentGroups = [-1];
        }

        return [$currentUser, $currentGroups];
    }

    /**
     * This static method returns a valid CurrentUser object if there is one
     * in the session that is not timed out. The session-ID is updated if
     * necessary. The CurrentUser will be removed from the session if it is
     * timed out. If there is no valid CurrentUser in the session or the
     * session is timed out, null will be returned. If the session data is
     * correct, but there is no user found in the user table, false will be
     * returned. On success, a valid CurrentUser object is returned.
     */
    public static function getFromSession(Configuration $configuration): ?CurrentUser
    {
        $sessionWrapper = new SessionWrapper();
        // there is no valid user object in the session
        if (!$sessionWrapper->has(SESSION_CURRENT_USER) || !$sessionWrapper->has(SESSION_ID_TIMESTAMP)) {
            return null;
        }

        // create a new CurrentUser object
        $user = new self($configuration);
        $user->getUserById($sessionWrapper->get(SESSION_CURRENT_USER));

        // user object is timed out
        if ($user->sessionIsTimedOut()) {
            $user->deleteFromSession();
            $user->errors[] = 'Session timed out.';

            return null;
        }

        // session-id isn't found in the user table
        $sessionInfo = $user->getSessionInfo();
        $sessionId = $sessionInfo['session_id'] ?? '';
        if ($sessionId === '' || $sessionId !== session_id()) {
            return null;
        }

        // check ip
        if (
            $configuration->get('security.ipCheck')
            && $sessionInfo['ip'] !== Request::createFromGlobals()->getClientIp()
        ) {
            return null;
        }

        // session-id needs to be updated
        if ($user->sessionIdIsTimedOut()) {
            $user->updateSessionId();
        }

        // user is now logged in
        $user->loggedIn = true;
        // save the current user to the session and return the instance
        $user->saveToSession();

        return $user;
    }

    /**
     * This static method returns a valid CurrentUser object if there is one
     * in the cookie that is not timed out. The session-ID is updated then.
     * The CurrentUser will be removed from the session if it is
     * timed out. If there is no valid CurrentUser in the cookie or the
     * cookie is timed out, null will be returned. If the cookie is correct,
     * but there is no user found in the user table, false will be returned.
     * On success, a valid CurrentUser object is returned.
     *
     * @throws Exception
     */
    public static function getFromCookie(Configuration $configuration): ?CurrentUser
    {
        $request = Request::createFromGlobals();
        if ($request->cookies->get(UserSession::COOKIE_NAME_REMEMBER_ME) === null) {
            return null;
        }

        // create a new CurrentUser object
        $user = new self($configuration);
        $user->getUserByCookie($request->cookies->get(UserSession::COOKIE_NAME_REMEMBER_ME));

        if (-1 === $user->getUserId()) {
            return null;
        }

        // sessionId needs to be updated
        $user->updateSessionId(true);
        // user is now logged in
        $user->loggedIn = true;
        // save current user to session and return the instance
        $user->saveToSession();

        return $user;
    }
}
