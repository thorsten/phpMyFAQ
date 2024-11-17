<?php

namespace phpMyFAQ\Controller;

use Symfony\Component\HttpFoundation\Response;

class RobotsController extends AbstractController
{
    /**
     * @throws \Exception
     */
    public function index(): Response
    {
        $response = new Response();

        $response->headers->set('Content-Type', 'text/plain');
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($this->configuration->get('seo.contentRobotsText'));

        return $response;
    }
}
