<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Plugin\PluginException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class PluginController extends AbstractAdministrationController
{
    /**
     * @throws PluginException
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/plugins')]
    public function index(Request $request): Response
    {
        $pluginManager = $this->container->get('phpmyfaq.plugin.plugin-manager');
        $pluginManager->loadPlugins();

        return $this->render('@admin/configuration/plugins.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'pluginList' => $pluginManager->getPlugins(),
        ]);
    }
}
