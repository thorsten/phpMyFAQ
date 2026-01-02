<?php

/**
 * PDF Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Peter Beauvain <pbeauvain@web.de>
 * @author    Olivier Plathey <olivier@fpdf.org>
 * @author    Krzysztof Kruszynski <thywolf@wolf.homelinux.net>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-12
 */

namespace phpMyFAQ\Controller\Frontend;

use DateTime;
use League\CommonMark\Exception\CommonMarkException;
use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Category;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Export\Pdf;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\AttachmentHelper;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PdfController extends AbstractFrontController
{
    /**
     * @throws Exception|\Exception|CommonMarkException
     */
    #[Route(path: '/pdf/{categoryId}/{faqId}/{faqLanguage}', name: 'public.pdf.faq')]
    public function index(Request $request): Response
    {
        $categoryId = Filter::filterVar($request->attributes->get('categoryId'), FILTER_VALIDATE_INT);
        $faqId = Filter::filterVar($request->attributes->get('faqId'), FILTER_VALIDATE_INT);
        $faqLanguage = Filter::filterVar($request->attributes->get('faqLanguage'), FILTER_SANITIZE_SPECIAL_CHARS);

        if ($categoryId === false || $categoryId === null || $faqId === false || $faqId === null) {
            return new RedirectResponse($this->configuration->getDefaultUrl());
        }

        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $faq = $this->container->get('phpmyfaq.faq');
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        $category = new Category($this->configuration, $currentGroups, true);
        $category->setUser($currentUser);

        $tags = $this->container->get('phpmyfaq.tags');
        $tags->setUser($currentUser)->setGroups($currentGroups);

        $pdf = new Pdf($faq, $category, $this->configuration);

        $faq->getFaq($faqId);
        $faq->faqRecord['category_id'] = $categoryId;

        if (!$this->configuration->get('records.disableAttachments') && 'yes' === $faq->faqRecord['active']) {
            try {
                $attachmentHelper = new AttachmentHelper();
                $attList = AttachmentFactory::fetchByRecordId($this->configuration, $faqId);
                $faq->faqRecord['attachmentList'] = $attachmentHelper->getAttachmentList($attList);
            } catch (AttachmentException) {
                $faq->faqRecord['attachmentList'] = '';
            }
        }

        $filename = 'FAQ-' . $faqId . '-' . $faqLanguage . '.pdf';
        $pdfFile = $pdf->generateFile($faq->faqRecord, $filename);

        $response = new Response();
        $response->setExpires(new DateTime());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->setContent($pdfFile);

        return $response;
    }
}
