<?php

namespace phpMyFAQ\Controller\Frontend;

use Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Filter;
use phpMyFAQ\Rating;
use phpMyFAQ\Session;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class VotingController extends AbstractController
{
    /**
     * @throws Exception
     */
    public function create(Request $request): JsonResponse
    {
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);

        $rating = new Rating($configuration);
        $session = new Session($configuration);
        $session->setCurrentUser($user);

        $data = json_decode($request->getContent());

        $faqId = Filter::filterVar($data->id ?? null, FILTER_VALIDATE_INT, 0);
        $vote = Filter::filterVar($data->value, FILTER_VALIDATE_INT);
        $userIp = Filter::filterVar($request->server->get('REMOTE_ADDR'), FILTER_VALIDATE_IP);

        if (isset($vote) && $rating->check($faqId, $userIp) && $vote > 0 && $vote < 6) {
            try {
                $session->userTracking('save_voting', $faqId);
            } catch (Exception $exception) {
                $configuration->getLogger()->error('Error saving voting', ['exception' => $exception]);
            }

            $votingData = [
                'record_id' => $faqId,
                'vote' => $vote,
                'user_ip' => $userIp,
            ];

            if ($rating->getNumberOfVotings($faqId) === 0) {
                $rating->addVoting($votingData);
            } else {
                $rating->update($votingData);
            }

            return $this->json(
                [
                    'success' => Translation::get('msgVoteThanks'),
                    'rating' => $rating->getVotingResult($faqId),
                ],
                Response::HTTP_OK
            );
        } elseif (!$rating->check($faqId, $userIp)) {
            try {
                $session->userTracking('error_save_voting', $faqId);
            } catch (Exception $exception) {
                return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
            }

            return $this->json(['error' => Translation::get('err_VoteTooMuch')], Response::HTTP_BAD_REQUEST);
        } else {
            try {
                $session->userTracking('error_save_voting', $faqId);
            } catch (Exception $exception) {
                return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
            }

            return $this->json(['error' => Translation::get('err_noVote')], Response::HTTP_BAD_REQUEST);
        }
    }
}
