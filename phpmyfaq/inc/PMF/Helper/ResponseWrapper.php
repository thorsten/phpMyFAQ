<?php

namespace PMF\Helper;

use Symfony\Component\HttpFoundation\Response;

class ResponseWrapper {
    /**
     * @var Response
     */
    private $response;

    /**
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function addCommonHeaders()
    {
        $this->response->headers->set('Expires', 'Thu, 07 Apr 1977 14:47:00 GMT');
        $this->response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
        $this->response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        $this->response->headers->set('Cache-Control', 'post-check=0, pre-check=0', false);
        $this->response->headers->set('Pragma', 'no-cache');
        $this->response->headers->set('Vary', 'Negotiate,Accept');
    }

    public function addContentTypeHeader($contentType)
    {
        $this->response->headers->set('Content-type', $contentType);
    }
}