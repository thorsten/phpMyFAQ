<?php

/**
 * This class is just a wrapper for Twig v3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Template
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-05-27
 */

namespace phpMyFAQ\Template;

use phpMyFAQ\System;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\FilesystemLoader;
use Twig\TemplateWrapper;
use Twig\TwigFunction;

readonly class TwigWrapper
{
    private Environment $twig;

    public function __construct(string $templatePath)
    {
        $loader = new FilesystemLoader($templatePath);
        $this->twig = new Environment(
            $loader,
            [
                'debug' => System::isDevelopmentVersion()
            ]
        );
    }

    /**
     * @throws TemplateException
     */
    public function loadTemplate(string $templateFile): TemplateWrapper
    {
        try {
            return $this->twig->load($templateFile);
        } catch (LoaderError | RuntimeError | SyntaxError $exception) {
            throw new TemplateException($exception->getMessage());
        }
    }

    public function addExtension(ExtensionInterface $extension): void
    {
        $this->twig->addExtension($extension);
    }

    public function addFunction(TwigFunction $function): void
    {
        $this->twig->addFunction($function);
    }
}
