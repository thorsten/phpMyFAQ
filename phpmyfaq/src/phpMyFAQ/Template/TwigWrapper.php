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

use phpMyFAQ\Core\Exception;
use phpMyFAQ\System;
use phpMyFAQ\Template\Extensions\PluginTwigExtension;
use phpMyFAQ\Template\Extensions\TranslateTwigExtension;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\FilesystemLoader;
use Twig\TemplateWrapper;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigWrapper
{
    private Environment $twigEnvironment;

    /** @var string Name of an active template set. */
    private static string $templateSetName = 'default';

    public function __construct(string $templatePath, bool $isSetup = false)
    {
        $filesystemLoader = new FilesystemLoader($templatePath);
        $this->twigEnvironment = new Environment(
            $filesystemLoader,
            [
                'debug' => System::isDevelopmentVersion()
            ]
        );

        // Always add the translation extension
        $this->twigEnvironment->addExtension(new TranslateTwigExtension());

        // Add the plugin extension if it's not in the setup phase
        if (!$isSetup) {
            $this->twigEnvironment->addExtension(new PluginTwigExtension());
        }

        // If we're on a development version or debug is enabled, add the debug extension
        if (System::isDevelopmentVersion() || DEBUG) {
            $this->twigEnvironment->addExtension(new DebugExtension());
        }
    }

    /**
     * @throws Exception
     */
    public function loadTemplate(string $templateFile): TemplateWrapper
    {
        try {
            return $this->twigEnvironment->load($templateFile);
        } catch (LoaderError | RuntimeError | SyntaxError $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    public function addExtension(ExtensionInterface $extension): void
    {
        $this->twigEnvironment->addExtension($extension);
    }

    public function addFunction(TwigFunction $twigFunction): void
    {
        $this->twigEnvironment->addFunction($twigFunction);
    }

    public function getExtension(string $class): ExtensionInterface
    {
        return $this->twigEnvironment->getExtension($class); /** @phpstan-ignore-line */
    }

    public function addFilter(TwigFilter $filter): void
    {
        $this->twigEnvironment->addFilter($filter);
    }

    /**
     * Returns the name of the actual template set.
     */
    public static function getTemplateSetName(): string
    {
        return self::$templateSetName;
    }

    /**
     * Set the template set name to use.
     */
    public static function setTemplateSetName(string $tplSetName): void
    {
        self::$templateSetName = $tplSetName;
    }
}
