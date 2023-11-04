<?php

/**
 * The Admin Configuration Tab Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-30
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Template\TemplateException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfigurationTabController extends AbstractController
{
    /**
     * @throws TemplateException
     */
    #[Route('admin/api/configuration/list')]
    public function list(Request $request): Response
    {
        $configuration = Configuration::getConfigurationInstance();

        $mode = $request->get('mode');

        return $this->render(
            './admin/configuration/tab-list.twig',
            [
                'mode' => $mode,
            ]
        );
    }
}
