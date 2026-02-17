<?php

/**
 * The Voting Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-03
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use Exception;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Entity\Vote;
use phpMyFAQ\Filter;
use phpMyFAQ\Rating;
use phpMyFAQ\Translation;
use phpMyFAQ\User\UserSession;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VotingController extends AbstractController
{
    public function __construct(
        private readonly Rating $rating,
        private readonly UserSession $userSession,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'voting', name: 'api.private.voting', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->userSession->setCurrentUser($this->currentUser);

        $data = json_decode($request->getContent());

        if (!$data) {
            throw new Exception('Invalid JSON data');
        }

        if (!isset($data->value)) {
            throw new Exception('Missing vote value');
        }

        if (!isset($data->id)) {
            throw new Exception('Missing FAQ ID');
        }

        $faqId = Filter::filterVar($data->id ?? null, FILTER_VALIDATE_INT, 0);
        $vote = Filter::filterVar($data->value, FILTER_VALIDATE_INT);
        $userIp = Filter::filterVar($request->server->get('REMOTE_ADDR'), FILTER_VALIDATE_IP) ?? '';

        if ($faqId <= 0) {
            throw new Exception('Missing FAQ ID');
        }

        if (!isset($vote) || $vote < 1 || $vote > 5) {
            throw new Exception('Invalid vote value');
        }

        if (isset($vote) && $this->rating->check($faqId, $userIp) && $vote > 0 && $vote < 6) {
            $this->userSession->userTracking('save_voting', $faqId);

            $votingData = new Vote();
            $votingData->setFaqId($faqId)->setVote($vote)->setIp($userIp);

            if ($this->rating->getNumberOfVotings($faqId) === 0) {
                $this->rating->create($votingData);
            } else {
                $this->rating->update($votingData);
            }

            return $this->json([
                'success' => Translation::get(key: 'msgVoteThanks'),
                'rating' => $this->rating->get($faqId),
            ], Response::HTTP_OK);
        }

        if (!$this->rating->check($faqId, $userIp)) {
            $this->userSession->userTracking('error_save_voting', $faqId);
            return $this->json(['error' => Translation::get(key: 'err_VoteTooMuch')], Response::HTTP_BAD_REQUEST);
        }

        $this->userSession->userTracking('error_save_voting', $faqId);
        return $this->json(['error' => Translation::get(key: 'err_noVote')], Response::HTTP_BAD_REQUEST);
    }
}
