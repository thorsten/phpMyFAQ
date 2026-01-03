<?php

/**
 * Error Controller (500)
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
 * @since     2026-01-03
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use Exception;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class ErrorController extends AbstractFrontController
{
    /**
     * Static fallback method for early bootstrap errors
     * Used when the system is not fully initialized yet
     *
     * @param string|null $errorMessage Optional error message to display
     * @throws Exception
     */
    public static function renderBootstrapError(?string $errorMessage = null): Response
    {
        $loader = new FilesystemLoader(PMF_ROOT_DIR . '/assets/templates/error');
        $twig = new Environment($loader);
        $html = $twig->render('500.twig', [
            'errorMessage' => $errorMessage,
        ]);

        $response = new Response(content: $html, status: Response::HTTP_INTERNAL_SERVER_ERROR);
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');

        return $response;
    }

    /**
     * Renders a 500 Internal Server Error page
     *
     * @param string|null $errorMessage Optional error message to display (shown only in debug mode)
     * @throws Exception
     */
    public function internalServerError(Request $request, ?string $errorMessage = null): Response
    {
        try {
            $response = $this->render('500.twig', [
                ...$this->getHeader($request),
                'title' => sprintf('%s - %s', Translation::get(key: 'msgError500'), $this->configuration->getTitle()),
                'errorMessage' => $errorMessage,
            ]);
        } catch (Exception) {
            $response = $this->renderMinimalError($errorMessage);
        }

        $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);

        return $response;
    }

    /**
     * Renders a minimal standalone error page without dependencies
     *
     * @param string|null $errorMessage Optional error message to display
     * @throws Exception
     */
    private function renderMinimalError(?string $errorMessage = null): Response
    {
        $loader = new FilesystemLoader(PMF_ROOT_DIR . '/assets/templates/error');
        $twig = new Environment($loader);
        $html = $twig->render('500.twig', [
            'errorMessage' => $errorMessage,
        ]);

        $response = new Response(content: $html, status: Response::HTTP_INTERNAL_SERVER_ERROR);
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');

        return $response;
    }
}
