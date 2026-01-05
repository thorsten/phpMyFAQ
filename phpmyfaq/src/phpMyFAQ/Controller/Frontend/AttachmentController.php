<?php

/**
 * Attachment Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-06-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\AttachmentService;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class AttachmentController extends AbstractFrontController
{
    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/attachment/{attachmentId}', name: 'public.attachment')]
    public function index(Request $request): Response
    {
        $id = Filter::filterVar($request->attributes->get('attachmentId'), FILTER_VALIDATE_INT);
        $attachmentErrors = [];
        $attachment = null;

        $attachmentService = new AttachmentService(
            $this->configuration,
            $this->currentUser,
            $this->container->get('phpmyfaq.faq.permission'),
        );

        if ($id === false || $id === null) {
            $attachmentErrors[] = $attachmentService->getGenericErrorMessage();
        } else {
            try {
                $attachment = $attachmentService->getAttachment($id);
            } catch (AttachmentException $attachmentException) {
                $attachmentErrors[] = $attachmentService->getAttachmentErrorMessage($attachmentException);
            }
        }

        if (
            $attachment instanceof \phpMyFAQ\Attachment\AbstractAttachment
            && $attachment->getRecordId() > 0
            && $attachmentService->canDownloadAttachment($attachment)
        ) {
            $streamedResponse = new StreamedResponse(static function () use ($attachment): void {
                $attachment->rawOut();
            });

            $streamedResponse->headers->set('Content-Type', $attachment->getMimeType());
            $streamedResponse->headers->set('Content-Length', (string) $attachment->getFilesize());

            if ($attachment->getMimeType() === 'application/pdf') {
                $streamedResponse->headers->set(
                    'Content-Disposition',
                    'inline; filename="' . rawurlencode($attachment->getFilename()) . '"',
                );
            } else {
                $streamedResponse->headers->set(
                    'Content-Disposition',
                    'attachment; filename="' . rawurlencode($attachment->getFilename()) . '"',
                );
            }

            $streamedResponse->headers->set('Content-MD5', $attachment->getRealHash());
            $streamedResponse->send();
        } else {
            $attachmentErrors[] = $attachmentService->getGenericErrorMessage();
        }

        return $this->render('attachment.twig', [
            ...$this->getHeader($request),
            'attachmentErrors' => $attachmentErrors,
        ]);
    }
}
