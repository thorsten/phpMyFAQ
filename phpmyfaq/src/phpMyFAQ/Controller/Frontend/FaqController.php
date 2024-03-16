<?php

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FaqController extends AbstractController
{
    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        if (!$this->captchaCodeIsValid($request)) {
            return $this->json(['error' => Translation::get('msgCaptcha')], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['status' => 'ok']);
    }

    private function isAddingFaqsAllowed(CurrentUser $user): bool
    {
        if (
            !$this->configuration->get('records.allowNewFaqsForGuests') &&
            !$user->perm->hasPermission($user->getUserId(), PermissionType::FAQ_ADD->value)
        ) {
            return false;
        }
        return true;
    }
}
