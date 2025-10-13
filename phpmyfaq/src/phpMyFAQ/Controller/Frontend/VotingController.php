<?php

declare(strict_types=1);

/**
 * The Voting Controller
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
 * @since     2024-03-03
 */

namespace phpMyFAQ\Controller\Frontend;

use Exception;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Entity\Vote;
use phpMyFAQ\Filter;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class VotingController extends AbstractController
{
    /**
     * @throws Exception
     */
    public function create(Request $request): JsonResponse
    {
        $rating = $this->container->get('phpmyfaq.rating');
        $session = $this->container->get('phpmyfaq.user.session');
        $session->setCurrentUser($this->currentUser);

        $data = json_decode($request->getContent());

        $faqId = Filter::filterVar($data->id ?? null, FILTER_VALIDATE_INT, 0);
        $vote = Filter::filterVar($data->value, FILTER_VALIDATE_INT);
        $userIp = Filter::filterVar($request->server->get('REMOTE_ADDR'), FILTER_VALIDATE_IP);

        if (isset($vote) && $rating->check($faqId, $userIp) && $vote > 0 && $vote < 6) {
            $session->userTracking('save_voting', $faqId);

            $votingData = new Vote();
            $votingData->setFaqId($faqId)->setVote($vote)->setIp($userIp);

            if ($rating->getNumberOfVotings($faqId) === 0) {
                $rating->create($votingData);
            } else {
                $rating->update($votingData);
            }

            return $this->json([
                'success' => Translation::get('msgVoteThanks'),
                'rating' => $rating->get($faqId),
            ], Response::HTTP_OK);
        }

        if (!$rating->check($faqId, $userIp)) {
            $session->userTracking('error_save_voting', $faqId);
            return $this->json(['error' => Translation::get('err_VoteTooMuch')], Response::HTTP_BAD_REQUEST);
        }

        $session->userTracking('error_save_voting', $faqId);
        return $this->json(['error' => Translation::get('err_noVote')], Response::HTTP_BAD_REQUEST);
    }
}
