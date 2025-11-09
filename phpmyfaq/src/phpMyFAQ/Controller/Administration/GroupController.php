<?php

/**
 * The Group Administration Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-24
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\Extensions\PermissionTranslationTwigExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Extension\AttributeExtension;

final class GroupController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/group', name: 'admin.group', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::GROUP_ADD);
        $this->userHasPermission(PermissionType::GROUP_EDIT);
        $this->userHasPermission(PermissionType::GROUP_DELETE);

        $this->addExtension(new AttributeExtension(PermissionTranslationTwigExtension::class));
        return $this->render('@admin/user/group.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
        ]);
    }

    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/group/add', name: 'admin.group.add', methods: ['GET'])]
    public function add(Request $request): Response
    {
        $this->userHasPermission(PermissionType::GROUP_ADD);

        $this->addExtension(new AttributeExtension(PermissionTranslationTwigExtension::class));
        return $this->render('@admin/user/group.add.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'csrfAddGroup' => Token::getInstance($this->container->get('session'))->getTokenString('add-group'),
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/group/create', name: 'admin.group.create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->userHasPermission(PermissionType::GROUP_ADD);

        if (!Token::getInstance($this->container->get('session'))->verifyToken('add-group', $request->get('csrf'))) {
            throw new UnauthorizedHttpException('Invalid CSRF token');
        }

        $user = $this->container->get('phpmyfaq.user');

        $groupName = Filter::filterVar($request->get('group_name'), FILTER_SANITIZE_SPECIAL_CHARS);
        $groupDescription = Filter::filterVar($request->get('group_description'), FILTER_SANITIZE_SPECIAL_CHARS);
        $groupAutoJoin = Filter::filterVar($request->get('group_auto_join'), FILTER_SANITIZE_SPECIAL_CHARS);

        // check group name
        if ($groupName === '') {
            $message = Translation::get(languageKey: 'ad_group_error_noName');
        }

        $groupData = [
            'name' => $groupName,
            'description' => $groupDescription,
            'auto_join' => $groupAutoJoin,
        ];

        if ($user->perm->addGroup($groupData) === 0) {
            $message = sprintf(
                '<div class="alert alert-danger">%s</div>',
                Translation::get(languageKey: 'ad_adus_dberr'),
            );
        } else {
            $message = sprintf(
                '<div class="alert alert-success">%s</div>',
                Translation::get(languageKey: 'ad_group_suc'),
            );
        }

        $this->addExtension(new AttributeExtension(PermissionTranslationTwigExtension::class));
        return $this->render('@admin/user/group.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'message' => $message,
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/group/confirm', name: 'admin.group.confirm', methods: ['POST'])]
    public function confirm(Request $request): Response
    {
        $this->userHasPermission(PermissionType::GROUP_DELETE);

        $session = $this->container->get('session');

        $groupId = Filter::filterVar($request->get('group_list_select'), FILTER_VALIDATE_INT);
        $groupData = $this->currentUser->perm->getGroupData($groupId);

        $this->addExtension(new AttributeExtension(PermissionTranslationTwigExtension::class));
        return $this->render('@admin/user/group.confirm.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'groupName' => $groupData['name'],
            'groupId' => $groupId,
            'csrfDeleteGroup' => Token::getInstance($session)->getTokenString('delete-group'),
        ]);
    }

    /**
     * @throws LoaderError
     * @throws Exception
     */
    #[Route('/group/delete', name: 'admin.group.delete', methods: ['POST'])]
    public function delete(Request $request): Response
    {
        $this->userHasPermission(PermissionType::GROUP_DELETE);

        $groupId = Filter::filterVar($request->get('group_id'), FILTER_VALIDATE_INT);
        $csrfToken = Filter::filterVar($request->get('pmf-csrf-token'), FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance($this->container->get('session'))->verifyToken('delete-group', $csrfToken)) {
            throw new UnauthorizedHttpException('Invalid CSRF token');
        }

        if (!$this->currentUser->perm->deleteGroup($groupId)) {
            $message = sprintf(
                '<div class="alert alert-danger">%s</div>',
                Translation::get(languageKey: 'ad_group_error_delete'),
            );
        } else {
            $message = sprintf(
                '<div class="alert alert-success">%s</div>',
                Translation::get(languageKey: 'ad_group_deleted'),
            );
        }

        $this->addExtension(new AttributeExtension(PermissionTranslationTwigExtension::class));
        return $this->render('@admin/user/group.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'message' => $message,
        ]);
    }

    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/group/update', name: 'admin.group.update', methods: ['POST'])]
    public function update(Request $request): Response
    {
        $this->userHasPermission(PermissionType::GROUP_EDIT);

        $groupId = Filter::filterVar($request->get('group_id'), FILTER_VALIDATE_INT);

        $groupData = [];
        $dataFields = ['name', 'description', 'auto_join'];
        foreach ($dataFields as $dataField) {
            $groupData[$dataField] = Filter::filterVar($request->get($dataField), FILTER_SANITIZE_SPECIAL_CHARS, '');
        }

        $user = $this->container->get('phpmyfaq.user');

        if (!$user->perm->changeGroup($groupId, $groupData)) {
            $message = sprintf(
                '<div class="alert alert-danger">%s %s</div>',
                Translation::get(languageKey: 'ad_msg_mysqlerr'),
                $this->configuration->getDb()->error(),
            );
        } else {
            $message = sprintf(
                '<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
                Translation::get(languageKey: 'ad_msg_savedsuc_1'),
                $user->perm->getGroupName($groupId),
                Translation::get(languageKey: 'ad_msg_savedsuc_2'),
            );
        }

        $this->addExtension(new AttributeExtension(PermissionTranslationTwigExtension::class));
        return $this->render('@admin/user/group.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'message' => $message,
        ]);
    }

    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/group/update/members', name: 'admin.group.update.members', methods: ['POST'])]
    public function updateMembers(Request $request): Response
    {
        $this->userHasPermission(PermissionType::GROUP_EDIT);

        $groupId = Filter::filterVar($request->get('group_id'), FILTER_VALIDATE_INT);
        $groupMembers = $request->request->all()['group_members'];

        $user = $this->container->get('phpmyfaq.user');
        if (!$user->perm->removeAllUsersFromGroup($groupId)) {
            $message = sprintf(
                '<div class="alert alert-danger">%s</div>',
                Translation::get(languageKey: 'ad_msg_mysqlerr'),
            );
        } else {
            foreach ($groupMembers as $groupMember) {
                $user->perm->addToGroup((int) $groupMember, $groupId);
            }

            $message = sprintf(
                '<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
                Translation::get(languageKey: 'ad_msg_savedsuc_1'),
                $user->perm->getGroupName($groupId),
                Translation::get(languageKey: 'ad_msg_savedsuc_2'),
            );
        }

        $this->addExtension(new AttributeExtension(PermissionTranslationTwigExtension::class));
        return $this->render('@admin/user/group.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'message' => $message,
        ]);
    }

    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/group/update/permissions', name: 'admin.group.update.permissions', methods: ['POST'])]
    public function updatePermissions(Request $request): Response
    {
        $this->userHasPermission(PermissionType::GROUP_EDIT);

        $groupId = Filter::filterVar($request->get('group_id'), FILTER_VALIDATE_INT);
        $groupPermissions = $request->request->all()['group_rights'];

        $user = $this->container->get('phpmyfaq.user');
        if (!$user->perm->refuseAllGroupRights($groupId)) {
            $message = sprintf(
                '<div class="alert alert-danger">%s</div>',
                Translation::get(languageKey: 'ad_msg_mysqlerr'),
            );
        } else {
            foreach ($groupPermissions as $groupPermission) {
                $user->perm->grantGroupRight($groupId, (int) $groupPermission);
            }

            $message = sprintf(
                '<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
                Translation::get(languageKey: 'ad_msg_savedsuc_1'),
                $user->perm->getGroupName($groupId),
                Translation::get(languageKey: 'ad_msg_savedsuc_2'),
            );
        }

        $this->addExtension(new AttributeExtension(PermissionTranslationTwigExtension::class));
        return $this->render('@admin/user/group.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'message' => $message,
        ]);
    }

    /**
     * @throws \Exception
     * @return array<string, mixed>
     */
    private function getBaseTemplateVars(): array
    {
        $user = $this->container->get('phpmyfaq.user');
        return [
            'rightData' => $user->perm->getAllRightsData(),
        ];
    }
}
