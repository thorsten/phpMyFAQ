<?php

/**
 * The Admin Sticky FAQs Controller.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-12-27
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Faq;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use phpMyFAQ\Translation;

class StickyController extends AbstractController
{
    #[Route('admin/api/sticky/order')]
    public function saveOrder(Request $request): JsonResponse
    {
        $response = new JsonResponse();

        $data = json_decode($request->getContent());
        $faq = new Faq(Configuration::getConfigurationInstance());
        $faq->setStickyFaqOrder($data->faqIds);

        $response->setStatusCode(Response::HTTP_OK);
        $response->setData(['success' => Translation::get('ad_categ_save_order')]);

        return $response;
    }
}
