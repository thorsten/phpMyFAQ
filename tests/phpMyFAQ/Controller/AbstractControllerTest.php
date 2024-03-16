<?php

namespace phpMyFAQ\Controller;

use phpMyFAQ\Template\TemplateException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AbstractControllerTest extends TestCase
{
    /**
     * @throws TemplateException
     * @throws Exception
     */
    public function testRender(): void
    {
        $controller = $this->createMock(AbstractController::class);

        $controller->method('render')
            ->willReturn(new Response());

        $response = $controller->render('path/to/template', ['var' => 'value']);

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testJson(): void
    {
        $controller = $this->createMock(AbstractController::class);

        $controller->method('json')
            ->willReturn(new JsonResponse());

        $response = $controller->json(['data' => 'value']);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }
}
