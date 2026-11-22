<?php

/**
 * The Admin Group Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-27
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\Category;
use phpMyFAQ\Category\Order;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AdminLogType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Permission\RightGrant;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/* @mago-expect lint:cyclomatic-complexity - each endpoint validates its payload and the acting user's scope inline; split planned with the admin API rework */
/* @mago-expect lint:kan-defect - permission-level guards on every endpoint raise the score; split planned with the admin API rework */
final class GroupController extends AbstractAdministrationApiController
{
    /**
     * @throws Exception
     */
    #[Route(path: 'group/groups', name: 'admin.api.group.groups', methods: ['GET'])]
    public function listGroups(): JsonResponse
    {
        $this->userHasGroupPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $groups = [];
        $permission = $currentUser->perm;
        if ($permission instanceof MediumPermission) {
            foreach ($permission->getAllGroups($currentUser) as $groupId) {
                $data = $permission->getGroupData((int) $groupId);
                $groups[] = [
                    'group_id' => (int) ($data['group_id'] ?? 0),
                    'name' => (string) ($data['name'] ?? ''),
                ];
            }
        }

        return $this->json($groups, Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/users', name: 'admin.api.group.users', methods: ['GET'])]
    public function listUsers(): JsonResponse
    {
        $this->userHasGroupPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $users = [];
        foreach ($currentUser->getAllUsers(true, false) as $singleUser) {
            $currentUser->getUserById($singleUser, true);
            $users[] = [
                'user_id' => $currentUser->getUserId(),
                'login' => $currentUser->getLogin(),
            ];
        }

        return $this->json($users, Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/data/{groupId}', name: 'admin.api.group.data', methods: ['GET'])]
    public function groupData(Request $request): JsonResponse
    {
        $this->userHasGroupPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $groupId = (int) $request->attributes->get('groupId');

        if (!$currentUser->perm instanceof MediumPermission) {
            return $this->json(['error' => 'Group permissions are not enabled.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($currentUser->perm->getGroupData($groupId), Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/members/{groupId}', name: 'admin.api.group.members', methods: ['GET'])]
    public function listMembers(Request $request): JsonResponse
    {
        $this->userHasGroupPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $groupId = (int) $request->attributes->get('groupId');

        if (!$currentUser->perm instanceof MediumPermission) {
            return $this->json(['error' => 'Group permissions are not enabled.'], Response::HTTP_BAD_REQUEST);
        }

        $members = [];
        foreach ($currentUser->perm->getGroupMembers($groupId) as $groupMember) {
            $currentUser->getUserById((int) $groupMember, true);
            $members[] = [
                'user_id' => $currentUser->getUserId(),
                'login' => $currentUser->getLogin(),
            ];
        }

        return $this->json($members, Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/permissions/{groupId}', name: 'admin.api.group.permissions', methods: ['GET'])]
    public function listPermissions(Request $request): JsonResponse
    {
        $this->userHasGroupPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $groupId = (int) $request->attributes->get('groupId');

        if (!$currentUser->perm instanceof MediumPermission) {
            return $this->json(['error' => 'Group permissions are not enabled.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($currentUser->perm->getGroupRights($groupId), Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(
        path: 'group/category-restrictions/{groupId}',
        name: 'admin.api.group.category-restrictions',
        methods: ['GET'],
    )]
    public function listCategoryRestrictions(Request $request): JsonResponse
    {
        $this->userHasGroupPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $groupId = (int) $request->attributes->get('groupId');

        if (!$currentUser->perm instanceof MediumPermission) {
            return $this->json(new \stdClass(), Response::HTTP_OK);
        }

        $restrictions = $currentUser->perm->getAllCategoryRestrictions($groupId);

        return $this->json($restrictions === [] ? new \stdClass() : $restrictions, Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/category-restrictions', name: 'admin.api.group.category-restrictions.save', methods: ['POST'])]
    public function saveCategoryRestrictions(Request $request): JsonResponse
    {
        $this->userHasGroupPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $permission = $currentUser->perm;
        if (!$permission instanceof MediumPermission) {
            return $this->json(['error' => 'Group permissions are not enabled.'], Response::HTTP_BAD_REQUEST);
        }

        $data = $this->decodeJsonArray($request);
        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        if (!Token::getInstance($this->session)->verifyToken(
            'save-category-restrictions',
            (string) ($data['csrfToken'] ?? ''),
        )) {
            return $this->json(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $groupId = (int) ($data['groupId'] ?? 0);
        $rightId = (int) ($data['rightId'] ?? 0);

        if ($groupId <= 0 || $rightId <= 0) {
            return $this->json(['error' => 'Invalid group or right ID.'], Response::HTTP_BAD_REQUEST);
        }

        $rawCategoryIds = $data['categoryIds'] ?? [];
        if (!is_array($rawCategoryIds)) {
            return $this->json(['error' => 'categoryIds must be an array.'], Response::HTTP_BAD_REQUEST);
        }

        $categoryIds = array_values(array_unique(array_filter(
            array_map('intval', $rawCategoryIds),
            static fn(int $id): bool => $id > 0,
        )));

        // An empty set means "unrestricted". Dropping every invalid ID would silently
        // turn a narrowing request into a grant, so refuse it instead.
        if ($rawCategoryIds !== [] && $categoryIds === []) {
            return $this->json([
                'error' => Translation::get(key: 'ad_category_restrictions_no_category'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // A non-SuperAdmin may only scope a group's right to categories they hold that right in
        // themselves, for the languages the group's right applies to. Group rights are inherited
        // by every member, so without this an administrator could grant the group category
        // access they do not possess (privilege escalation), including via an empty
        // "unrestricted" list.
        //
        // A right the group already holds is judged by what the save changes instead: the
        // categories added and removed must lie within the acting user's scope, and the rest of
        // the group's restriction, which may exceed that scope, is submitted back unchanged.
        $languages = $permission->getLanguageRestrictions($groupId, $rightId);
        $storedCategoryIds = $permission->getCategoryRestrictions($groupId, $rightId);
        $groupHoldsRight = in_array($rightId, $permission->getGroupRights($groupId), strict: true);

        $maySave = $groupHoldsRight
            ? $this->mayChangeGrant(
                $rightId,
                RightGrant::fromRestrictions($storedCategoryIds, $languages),
                RightGrant::fromRestrictions($categoryIds, $languages),
            )
            : $this->mayAssignScope($rightId, $categoryIds, $languages);

        if (!$maySave && $categoryIds === [] && $storedCategoryIds !== []) {
            return $this->storedRestrictionsStay(
                'ad_category_restrictions_kept',
                $permission,
                $rightId,
                $this->categoryNames($storedCategoryIds),
            );
        }

        if (!$maySave) {
            return $this->json([
                'error' => Translation::get(key: $groupHoldsRight ? 'ad_permission_scope_exceeded' : 'msgNoPermission'),
            ], Response::HTTP_FORBIDDEN);
        }

        $success = $permission->setCategoryRestrictions($groupId, $rightId, $categoryIds);

        if (!$success) {
            return $this->json([
                'error' => 'Failed to save category restrictions.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['success' => true], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(
        path: 'group/language-restrictions/{groupId}',
        name: 'admin.api.group.language-restrictions',
        methods: ['GET'],
    )]
    public function listLanguageRestrictions(Request $request): JsonResponse
    {
        $this->userHasGroupPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $groupId = (int) $request->attributes->get('groupId');

        if (!$currentUser->perm instanceof MediumPermission) {
            return $this->json(new \stdClass(), Response::HTTP_OK);
        }

        $restrictions = $currentUser->perm->getAllLanguageRestrictions($groupId);

        return $this->json($restrictions === [] ? new \stdClass() : $restrictions, Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/language-restrictions', name: 'admin.api.group.language-restrictions.save', methods: ['POST'])]
    public function saveLanguageRestrictions(Request $request): JsonResponse
    {
        $this->userHasGroupPermission();

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $permission = $currentUser->perm;
        if (!$permission instanceof MediumPermission) {
            return $this->json(['error' => 'Group permissions are not enabled.'], Response::HTTP_BAD_REQUEST);
        }

        $data = $this->decodeJsonArray($request);
        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        if (!Token::getInstance($this->session)->verifyToken(
            'save-language-restrictions',
            (string) ($data['csrfToken'] ?? ''),
        )) {
            return $this->json(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $groupId = (int) ($data['groupId'] ?? 0);
        $rightId = (int) ($data['rightId'] ?? 0);

        if ($groupId <= 0 || $rightId <= 0) {
            return $this->json(['error' => 'Invalid group or right ID.'], Response::HTTP_BAD_REQUEST);
        }

        $rawLanguages = $data['languages'] ?? [];
        if (!is_array($rawLanguages)) {
            return $this->json(['error' => 'languages must be an array.'], Response::HTTP_BAD_REQUEST);
        }

        $languages = array_values(array_filter(
            array_map('strval', $rawLanguages),
            Language::isASupportedLanguage(...),
        ));

        // An empty set means "unrestricted". Dropping every unsupported code would
        // silently turn a narrowing request into a grant, so refuse it instead.
        if ($rawLanguages !== [] && $languages === []) {
            return $this->json([
                'error' => Translation::get(key: 'ad_language_restrictions_no_language'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // A non-SuperAdmin may only scope a group's right to languages they hold themselves, for
        // the categories the group's right applies to. Group rights are inherited by every
        // member, so without this an administrator could grant the group language access they
        // do not possess (privilege escalation), including via an empty "unrestricted" list.
        //
        // A right the group already holds is judged by what the save changes instead: the
        // languages added and removed must lie within the acting user's scope, and the rest of
        // the group's restriction, which may exceed that scope, is submitted back unchanged.
        $categoryIds = $permission->getCategoryRestrictions($groupId, $rightId);
        $storedLanguages = $permission->getLanguageRestrictions($groupId, $rightId);
        $groupHoldsRight = in_array($rightId, $permission->getGroupRights($groupId), strict: true);

        $maySave = $groupHoldsRight
            ? $this->mayChangeGrant(
                $rightId,
                RightGrant::fromRestrictions($categoryIds, $storedLanguages),
                RightGrant::fromRestrictions($categoryIds, $languages),
            )
            : $this->mayAssignScope($rightId, $categoryIds, $languages);

        if (!$maySave && $languages === [] && $storedLanguages !== []) {
            return $this->storedRestrictionsStay(
                'ad_language_restrictions_kept',
                $permission,
                $rightId,
                $this->languageNames($storedLanguages),
            );
        }

        if (!$maySave) {
            return $this->json([
                'error' => Translation::get(key: $groupHoldsRight ? 'ad_permission_scope_exceeded' : 'msgNoPermission'),
            ], Response::HTTP_FORBIDDEN);
        }

        $success = $permission->setLanguageRestrictions($groupId, $rightId, $languages);

        if (!$success) {
            return $this->json([
                'error' => 'Failed to save language restrictions.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['success' => true], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/languages', name: 'admin.api.group.languages', methods: ['GET'])]
    public function listLanguages(): JsonResponse
    {
        $this->userHasGroupPermission();

        $languages = [];
        foreach (LanguageHelper::getAvailableLanguages() as $code => $label) {
            $languages[] = ['code' => $code, 'label' => $label];
        }

        return $this->json($languages, Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/update', name: 'admin.api.group.update', methods: ['POST'])]
    public function updateGroup(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::GROUP_EDIT);

        $data = $this->decodeJsonArray($request);
        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        if (!Token::getInstance($this->session)->verifyToken('update-group', (string) ($data['csrfToken'] ?? ''))) {
            return $this->json(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $groupId = (int) ($data['groupId'] ?? 0);
        if ($groupId <= 0) {
            return $this->json(['error' => 'Invalid group ID.'], Response::HTTP_BAD_REQUEST);
        }

        $name = Filter::filterVar($data['name'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        if (trim($name) === '') {
            return $this->json(['error' => Translation::get('ad_group_error_noName')], Response::HTTP_BAD_REQUEST);
        }

        $autoJoin = (bool) ($data['autoJoin'] ?? false);

        // Enabling auto-join makes every newly registered user inherit this group's rights,
        // so a non-SuperAdmin may only enable it on a group whose rights they fully hold
        // (same escalation rule as membership management, fail closed).
        if ($autoJoin) {
            $denied = $this->denyUnlessGroupRightsHeld(
                $groupId,
                'Cannot enable auto-join without group permission support.',
                'Cannot enable auto-join on a group whose rights you do not hold.',
            );
            if ($denied instanceof JsonResponse) {
                return $denied;
            }
        }

        $currentUser = CurrentUser::getCurrentUser($this->configuration);
        if (!$currentUser->perm instanceof MediumPermission) {
            return $this->json(['error' => 'Group permissions are not enabled.'], Response::HTTP_BAD_REQUEST);
        }

        $groupData = [
            'name' => $name,
            'description' => Filter::filterVar($data['description'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS, ''),
            'auto_join' => $autoJoin,
        ];

        if (!$currentUser->perm->changeGroup($groupId, $groupData)) {
            return $this->json(['error' => Translation::get('ad_msg_mysqlerr')], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->adminLog?->log($this->currentUser, AdminLogType::GROUP_EDIT->value . ':' . $groupId);

        return $this->json([
            'success' => sprintf(
                '%s %s %s',
                Translation::getString('ad_msg_savedsuc_1'),
                $currentUser->perm->getGroupName($groupId),
                Translation::getString('ad_msg_savedsuc_2'),
            ),
        ], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/members', name: 'admin.api.group.members.update', methods: ['POST'])]
    public function updateMembers(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::GROUP_EDIT);

        $data = $this->decodeJsonArray($request);
        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        if (!Token::getInstance($this->session)->verifyToken(
            'update-group-members',
            (string) ($data['csrfToken'] ?? ''),
        )) {
            return $this->json(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $groupId = (int) ($data['groupId'] ?? 0);
        if ($groupId <= 0) {
            return $this->json(['error' => 'Invalid group ID.'], Response::HTTP_BAD_REQUEST);
        }

        $rawMemberIds = $data['memberIds'] ?? [];
        if (!is_array($rawMemberIds)) {
            return $this->json(['error' => 'memberIds must be an array.'], Response::HTTP_BAD_REQUEST);
        }

        $memberIds = array_values(array_filter(
            array_map('intval', $rawMemberIds),
            static fn(int $id): bool => $id > 0,
        ));

        // A non-SuperAdmin may only manage membership of a group whose rights they fully hold
        // themselves, in at least the language and category scope the group holds them in.
        // Otherwise an administrator with the delegable GROUP_EDIT right could join themselves
        // (or anyone else) to a privileged group and inherit rights, or a wider scope for a
        // right, they do not possess (privilege escalation via group membership inheritance).
        $denied = $this->denyUnlessGroupRightsHeld(
            $groupId,
            'Cannot manage group membership without group permission support.',
            'Cannot manage a group whose rights you do not hold.',
        );
        if ($denied instanceof JsonResponse) {
            return $denied;
        }

        $currentUser = CurrentUser::getCurrentUser($this->configuration);
        if (!$currentUser->perm instanceof MediumPermission) {
            return $this->json(['error' => 'Group permissions are not enabled.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$currentUser->perm->removeAllUsersFromGroup($groupId)) {
            return $this->json(['error' => Translation::get('ad_msg_mysqlerr')], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $failed = false;
        foreach ($memberIds as $memberId) {
            if ($currentUser->perm->addToGroup($memberId, $groupId)) {
                continue;
            }

            $failed = true;
        }

        if ($failed) {
            return $this->json(['error' => Translation::get('ad_msg_mysqlerr')], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->adminLog?->log($this->currentUser, AdminLogType::GROUP_EDIT->value . ' (members):' . $groupId);

        return $this->json([
            'success' => sprintf(
                '%s %s %s',
                Translation::getString('ad_msg_savedsuc_1'),
                $currentUser->perm->getGroupName($groupId),
                Translation::getString('ad_msg_savedsuc_2'),
            ),
        ], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/permissions', name: 'admin.api.group.permissions.update', methods: ['POST'])]
    public function updatePermissions(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::GROUP_EDIT);

        $data = $this->decodeJsonArray($request);
        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        if (!Token::getInstance($this->session)->verifyToken(
            'update-group-permissions',
            (string) ($data['csrfToken'] ?? ''),
        )) {
            return $this->json(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $groupId = (int) ($data['groupId'] ?? 0);
        if ($groupId <= 0) {
            return $this->json(['error' => 'Invalid group ID.'], Response::HTTP_BAD_REQUEST);
        }

        /** @var mixed $rawRightIds */
        $rawRightIds = $data['rightIds'] ?? [];
        if (!is_array($rawRightIds)) {
            return $this->json(['error' => 'rightIds must be an array.'], Response::HTTP_BAD_REQUEST);
        }

        $rightIds = array_values(array_filter(array_map('intval', $rawRightIds), static fn(int $id): bool => $id > 0));

        // A non-SuperAdmin may only assign rights they hold themselves. This prevents an
        // administrator with the delegable GROUP_EDIT right from granting privileges they do not
        // possess to a group (privilege escalation via group membership inheritance).
        //
        // Holding a right is not enough: a right held only for some languages or categories must
        // not be granted to the group in a wider scope, or the acting user could widen their own
        // scope by joining the group. The acting user's scope per right is captured here, before
        // any write, because it may itself derive from the group being edited.
        //
        // A right the group already holds is not granted again: it stays in the scope the group
        // holds it in, so only the rights this save adds are measured against that scope.
        $actingPermission = $this->currentUser->perm;
        $heldRightIds = $actingPermission instanceof MediumPermission
            ? $actingPermission->getGroupRights($groupId)
            : [];

        $ownScopes = [];
        if (!$this->currentUser->isSuperAdmin()) {
            $actingUserId = $this->currentUser->getUserId();
            foreach (array_diff($rightIds, $heldRightIds) as $rightId) {
                if (!$this->currentUser->perm->hasPermission($actingUserId, $rightId)) {
                    return $this->json(['error' => 'Cannot grant a right you do not hold.'], Response::HTTP_FORBIDDEN);
                }

                $ownLanguages = $this->currentUser
                    ->perm
                    ->getScopeForRight($actingUserId, $rightId)
                    ->languagesInAnyCategory();
                $ownCategories = $this->currentUser
                    ->perm
                    ->getScopeForRight($actingUserId, $rightId)
                    ->categoryIdsInAnyLanguage();

                // A group grant carries one set of languages and one set of categories. Every
                // language and category the acting user holds is only safe to hand out together
                // if they hold each combination; holding them through separate grants (one
                // limited by language, another by category) must not merge into a wider grant.
                if (!$this->currentUser->perm->getScopeForRight($actingUserId, $rightId)->covers(
                    $ownCategories,
                    $ownLanguages,
                )) {
                    return $this->json([
                        'error' => sprintf(
                            'Cannot grant right %d: you hold it in different scopes through separate grants.',
                            $rightId,
                        ),
                    ], Response::HTTP_FORBIDDEN);
                }

                $ownScopes[$rightId] = [$ownLanguages, $ownCategories];
            }
        }

        // The submitted rights are not the whole story: this endpoint replaces the group's rights,
        // so every right the group currently holds is about to be revoked. Checking only the
        // submitted list would let an administrator holding the delegable GROUP_EDIT right wipe
        // rights they do not hold themselves - with an empty list, nothing would be checked at all.
        // Mirrors deleteGroup(), which destroys the same rights.
        $denied = $this->denyUnlessGroupRightsMayBeReplaced($groupId, $heldRightIds, $rightIds);
        if ($denied instanceof JsonResponse) {
            return $denied;
        }

        $currentUser = CurrentUser::getCurrentUser($this->configuration);
        if (!$currentUser->perm instanceof MediumPermission) {
            return $this->json(['error' => 'Group permissions are not enabled.'], Response::HTTP_BAD_REQUEST);
        }

        // refuseAllGroupRights() drops the per-right language restriction rows along with the
        // rights, and a missing restriction row means "all languages". Capture the group's
        // restrictions first so a right that survives the save keeps its scope.
        $existingLanguages = $currentUser->perm->getAllLanguageRestrictions($groupId);
        $keptRightIds = array_intersect($rightIds, $heldRightIds);

        if (!$currentUser->perm->refuseAllGroupRights($groupId)) {
            return $this->json(['error' => Translation::get('ad_msg_mysqlerr')], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $failed = false;
        foreach ($rightIds as $rightId) {
            // A right the group keeps stays in the scope it is held in: its language restriction
            // is put back as it was and its category restriction is not touched, whether it is
            // narrower or wider than the acting user's own scope.
            [$ownLanguages, $ownCategories] = in_array($rightId, $keptRightIds, strict: true)
                ? [null, null]
                : $ownScopes[$rightId] ?? [null, null];

            // Restrict first, grant second: if restricting fails, the right stays ungranted (fail closed).
            if (!$this->restrictGroupRightToScope(
                $currentUser->perm,
                $groupId,
                $rightId,
                $this->languageScopeForRegrant($existingLanguages[$rightId] ?? [], $ownLanguages),
                $ownCategories,
            )) {
                $failed = true;
                continue;
            }

            if ($currentUser->perm->grantGroupRight($groupId, $rightId)) {
                continue;
            }

            $failed = true;
        }

        if ($failed) {
            return $this->json(['error' => Translation::get('ad_msg_mysqlerr')], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->adminLog?->log($this->currentUser, AdminLogType::GROUP_CHANGE_PERMISSIONS->value . ':' . $groupId);

        return $this->json([
            'success' => sprintf(
                '%s %s %s',
                Translation::getString('ad_msg_savedsuc_1'),
                $currentUser->perm->getGroupName($groupId),
                Translation::getString('ad_msg_savedsuc_2'),
            ),
        ], Response::HTTP_OK);
    }

    /**
     * Denies replacing the group's rights unless the acting user may, and returns null when the
     * save may proceed.
     *
     * A non-SuperAdmin may only edit a group whose rights they all hold themselves. The scope is
     * judged per right: one the group keeps is left as it is, so it may exceed the acting user's
     * scope, while one the save takes away must be held in at least the language and category
     * scope the group holds it in - nobody revokes what they could not grant.
     *
     * @param array<int> $heldRightIds  the rights the group holds now
     * @param array<int> $rightIds      the rights the group is to hold
     * @throws Exception
     */
    private function denyUnlessGroupRightsMayBeReplaced(
        int $groupId,
        array $heldRightIds,
        array $rightIds,
    ): ?JsonResponse {
        if ($this->currentUser->isSuperAdmin()) {
            return null;
        }

        // Fail closed: if the permission backend cannot enumerate group rights, we cannot prove
        // the acting user holds them, so the operation must be denied rather than allowed.
        $actingPermission = $this->currentUser->perm;
        if (!$actingPermission instanceof MediumPermission) {
            return $this->json([
                'error' => 'Cannot change group permissions without group permission support.',
            ], Response::HTTP_FORBIDDEN);
        }

        $actingUserId = $this->currentUser->getUserId();
        foreach ($heldRightIds as $heldRightId) {
            if (!$actingPermission->hasPermission($actingUserId, $heldRightId)) {
                return $this->json([
                    'error' => 'Cannot change the permissions of a group whose rights you do not hold.',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        foreach (array_diff($heldRightIds, $rightIds) as $revokedRightId) {
            if (!$this->holdsGroupRightInFullScope($actingPermission, $groupId, $revokedRightId)) {
                return $this->json([
                    'error' => Translation::get(key: 'ad_permission_scope_exceeded'),
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return null;
    }

    /**
     * Names the categories in the language of the administration; a category without a
     * translation in that language is named by its ID.
     *
     * @param array<int> $categoryIds
     * @return array<string>
     */
    private function categoryNames(array $categoryIds): array
    {
        $category = new Category($this->configuration);
        $category->setLanguage($this->configuration->getLanguage()->getLanguage());
        $categories = $category->loadCategories();

        return array_map(
            static fn(int $categoryId): string => (string) ($categories[$categoryId]['name'] ?? '#' . $categoryId),
            $categoryIds,
        );
    }

    /**
     * Whether the acting user may replace the grant a group holds a right in by another one:
     * everything the change adds or takes away must lie within their own scope.
     *
     * @throws Exception
     */
    private function mayChangeGrant(int $rightId, RightGrant $held, RightGrant $submitted): bool
    {
        return (
            $this->currentUser->isSuperAdmin()
            || $this->currentUser
                ->perm
                ->getScopeForRight($this->currentUser->getUserId(), $rightId)
                ->coversChange($held, $submitted)
        );
    }

    /**
     * Returns the languages a newly granted group right must be restricted to, null meaning
     * unrestricted, and the languages a right the group keeps is put back with. Restriction rows
     * that exist are kept. A non-SuperAdmin whose own grant is restricted (a non-null scope) can
     * only hand a right out within that scope: the stored restriction is narrowed to the
     * languages they hold, and an unrestricted or disjoint restriction becomes exactly their
     * own scope. An empty own scope stays empty, so the right is not granted at all.
     *
     * @param array<string> $existingLanguages
     * @param array<string>|null $ownLanguages
     * @return array<string>|null
     */
    private function languageScopeForRegrant(array $existingLanguages, ?array $ownLanguages): ?array
    {
        if ($ownLanguages === null) {
            return $existingLanguages === [] ? null : $existingLanguages;
        }

        $languages = array_values(array_intersect($existingLanguages, $ownLanguages));

        return $languages === [] ? $ownLanguages : $languages;
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/delete', name: 'admin.api.group.delete', methods: ['POST'])]
    public function deleteGroup(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::GROUP_DELETE);

        /** @var mixed $data */
        $data = $this->decodeJsonArray($request);
        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        if (!Token::getInstance($this->session)->verifyToken('delete-group', (string) ($data['csrfToken'] ?? ''))) {
            return $this->json(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $groupId = (int) ($data['groupId'] ?? 0);
        if ($groupId <= 0) {
            return $this->json(['error' => 'Invalid group ID.'], Response::HTTP_BAD_REQUEST);
        }

        // A non-SuperAdmin may only delete a group whose rights they fully hold themselves, in at
        // least the language and category scope the group holds them in. Deleting a group destroys
        // the rights, memberships and restrictions it carries, so without this an administrator
        // holding the delegable GROUP_DELETE right could tear down privileges they do not possess
        // and could not grant, join or manage through any of the other, privilege-gated endpoints.
        $denied = $this->denyUnlessGroupRightsHeld(
            $groupId,
            'Cannot delete a group without group permission support.',
            'Cannot delete a group whose rights you do not hold.',
        );
        if ($denied instanceof JsonResponse) {
            return $denied;
        }

        $currentUser = CurrentUser::getCurrentUser($this->configuration);
        if (!$currentUser->perm instanceof MediumPermission) {
            return $this->json(['error' => 'Group permissions are not enabled.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$currentUser->perm->deleteGroup($groupId)) {
            return $this->json([
                'error' => Translation::get('ad_group_error_delete'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->adminLog?->log($this->currentUser, AdminLogType::GROUP_DELETE->value . ':' . $groupId);

        return $this->json(['success' => Translation::get('ad_group_deleted')], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'group/categories', name: 'admin.api.group.categories', methods: ['GET'])]
    public function listCategories(): JsonResponse
    {
        $this->userHasGroupPermission();

        $category = new Category($this->configuration);
        // Restrict to the current UI language — loadCategories() would
        // otherwise return every translation of each category, with an
        // arbitrary one winning the per-id overwrite.
        $category->setLanguage($this->configuration->getLanguage()->getLanguage());

        // Depth-first tree order with nesting levels, sibling order taken
        // from the stored category order (same as the category overview page).
        $orderedCategories = new Order($this->configuration)->getOrderedFlatList($category->loadCategories());

        $categories = array_map(static fn(array $cat): array => [
            'id' => $cat['id'],
            'name' => $cat['name'],
            'parent_id' => $cat['parent_id'],
            'level' => $cat['level'],
        ], $orderedCategories);

        return $this->json($categories, Response::HTTP_OK);
    }
}
