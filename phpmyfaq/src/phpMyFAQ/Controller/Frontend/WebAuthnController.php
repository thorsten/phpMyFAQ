<?php

/**
 * The WebAuthn Controller
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
 * @since     2024-09-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class WebAuthnController extends AbstractFrontController
{
    /**
     * @throws Exception|LoaderError
     */
    #[Route(path: '/services/webauthn', name: 'public.webauthn.index')]
    public function index(Request $request): Response
    {
        return $this->render(file: '/webauthn.twig', context: [
            ...$this->getHeader($request),
            'msgLoginUser' => Translation::get(key: 'msgLoginUser'),
            'title' => Translation::get(key: 'msgLoginUser'),
            'faqHome' => $this->configuration->getDefaultUrl(),
            'isUserRegistrationEnabled' => $this->configuration->get(item: 'security.enableRegistration'),
            'msgRegisterUser' => Translation::get(key: 'msgRegisterUser'),
        ]);
    }
}
