<?php

/**
 * Custom Page Controller
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
 * @since     2026-01-15
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\SeoEntity;
use phpMyFAQ\Enums\SeoType;
use phpMyFAQ\Seo;
use phpMyFAQ\Strings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class CustomPageController extends AbstractFrontController
{
    /**
     * Displays a custom page by its slug
     *
     * @throws Exception|LoaderError*@throws \Exception
     *
     */
    #[Route(path: '/page/{slug}.html', name: 'public.page', requirements: ['slug' => '[a-z0-9\-_]+'], methods: ['GET'])]
    public function show(Request $request): Response
    {
        $slug = $request->attributes->get('slug');

        if (empty($slug)) {
            return $this->render('404.twig', [
                ...$this->getHeader($request),
            ]);
        }

        $customPage = $this->container->get('phpmyfaq.custom-page');
        $language = $this->configuration->getLanguage()->getLanguage();

        // Get page by slug
        $pageEntity = $customPage->getBySlug($slug, $language);

        // If not found or not active, show 404
        if ($pageEntity === null || !$pageEntity->isActive()) {
            return $this->render('404.twig', [
                ...$this->getHeader($request),
            ]);
        }

        // Get SEO metadata
        $seo = new Seo($this->configuration);
        $seoQueryEntity = new SeoEntity();
        $seoQueryEntity
            ->setSeoType(SeoType::PAGE)
            ->setReferenceId($pageEntity->getId())
            ->setReferenceLanguage($language);

        try {
            $seoEntity = $seo->get($seoQueryEntity);
            $metaTitle = $seoEntity->getTitle() ?: $pageEntity->getPageTitle();
            $metaDescription = $seoEntity->getDescription() ?: '';
        } catch (Exception $e) {
            // SEO data aren't found, use page defaults
            $metaTitle = $pageEntity->getPageTitle();
            $metaDescription = '';
        }

        return $this->render('custom-page.twig', [
            ...$this->getHeader($request),
            'pageTitle' => Strings::htmlentities($pageEntity->getPageTitle()),
            'pageContent' => $pageEntity->getContent(),
            'authorName' => Strings::htmlentities($pageEntity->getAuthorName()),
            'authorEmail' => $pageEntity->getAuthorEmail(),
            'pageCreated' => $pageEntity->getCreated()->format('Y-m-d H:i:s'),
            'pageUpdated' => $pageEntity->getUpdated()?->format('Y-m-d H:i:s'),
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
        ]);
    }
}
