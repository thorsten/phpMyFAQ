<?php

/**
 * Terms of Service Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TermsController extends AbstractFrontController
{
    /**
     * Displays the terms of service page - either a custom page or redirects to configured URL.
     * @throws Exception
     */
    #[Route(path: '/terms.html', name: 'public.terms')]
    public function index(Request $request): Response
    {
        return $this->handleStaticPageRedirect('main.termsURL');
    }
}
