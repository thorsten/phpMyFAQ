<?php

declare(strict_types=1);

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
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\AttachmentHelper;
use phpMyFAQ\Tags;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PdfController extends AbstractFrontController
{
    public function __construct(
        private readonly Faq $faq,
        private readonly Tags $tags,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception|\Exception|CommonMarkException
     */
    #[Route(
        path: '/pdf/{categoryId}/{faqId}/{faqLanguage}',
        name: 'public.pdf.faq',
        requirements: [
            'categoryId' => '\d+',
            'faqId' => '\d+',
            'faqLanguage' => '[a-z]{2}(_[a-z]{2})?',
        ],
        methods: ['GET'],
    )]
    public function index(Request $request): Response
    {
        $categoryId = Filter::filterVar($request->attributes->get('categoryId'), FILTER_VALIDATE_INT);
        $faqId = Filter::filterVar($request->attributes->get('faqId'), FILTER_VALIDATE_INT);
        $faqLanguage = Filter::filterVar($request->attributes->get('faqLanguage'), FILTER_SANITIZE_SPECIAL_CHARS);

        if ($categoryId === false || $categoryId === null || $faqId === false || $faqId === null) {
            return new RedirectResponse($this->configuration->getDefaultUrl());
        }

        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $this->faq->setUser($currentUser);
        $this->faq->setGroups($currentGroups);

        $category = new Category($this->configuration, $currentGroups, true);
        $category->setUser($currentUser);

        $this->tags->setUser($currentUser)->setGroups($currentGroups);

        $pdf = new Pdf($this->faq, $category, $this->configuration);

        $this->faq->getFaq($faqId);
        $this->faq->faqRecord['category_id'] = $categoryId;

        if (!$this->configuration->get('records.disableAttachments') && 'yes' === $this->faq->faqRecord['active']) {
            try {
                $attachmentHelper = new AttachmentHelper();
                $attList = AttachmentFactory::fetchByRecordId($this->configuration, $faqId);
                $this->faq->faqRecord['attachmentList'] = $attachmentHelper->getAttachmentList($attList);
            } catch (AttachmentException) {
                $this->faq->faqRecord['attachmentList'] = '';
            }
        }

        $filename = 'FAQ-' . $faqId . '-' . $faqLanguage . '.pdf';
        $pdfFile = $pdf->generateFile($this->faq->faqRecord, $filename);

        $response = new Response();
        $response->setExpires(new DateTime());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->setContent($pdfFile);

        return $response;
    }
}
