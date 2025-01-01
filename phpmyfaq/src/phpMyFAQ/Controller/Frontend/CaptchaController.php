<?php

/**
 * The Captcha Image Controller
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
 * @since     2024-03-12
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Captcha\Captcha;
use phpMyFAQ\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CaptchaController extends AbstractController
{
    /**
     * @throws \JsonException|\Exception
     */
    public function renderImage(): Response
    {
        $captcha = Captcha::getInstance($this->configuration);

        // Set headers
        $response = new Response();
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'image/jpeg');

        // Set image content
        $response->setContent($captcha->getCaptchaImage());

        // Return the response
        return $response;
    }
}
