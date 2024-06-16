<?php

namespace phpMyFAQ\Controller;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Faq\Statistics;
use phpMyFAQ\Template\TemplateException;
use phpMyFAQ\Template\TwigWrapper;
use Symfony\Component\HttpFoundation\Response;

class SitemapController extends AbstractController
{
    private const PMF_SITEMAP_GOOGLE_MAX_URLS = 50000;

    /**
     * @throws TemplateException|Exception
     */
    public function index(): Response
    {
        $response = new Response();
        $faqStatistics = new Statistics($this->configuration);

        $items = $faqStatistics->getTopTenData(self::PMF_SITEMAP_GOOGLE_MAX_URLS - 1);

        $urls = [];
        foreach ($items as $item) {
            $urls[] = [
                'loc' => $item['url'],
                'lastmod' => $item['date'],
                'priority' => '1.00',
            ];
        }

        $twigWrapper = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
        $template = $twigWrapper->loadTemplate('sitemap.xml.twig');

        $response->headers->set('Content-Type', 'text/xml');
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($template->render(['urls' => $urls]));

        return $response;
    }
}
