<?php

namespace phpMyFAQ\Controller;

use phpMyFAQ\Twig\TemplateException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AbstractControllerTest extends TestCase
{
    /**
     * @throws TemplateException
     * @throws Exception
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testRender(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $controller->method('render')->willReturn(new Response());

        $response = $controller->render('path/to/template', ['var' => 'value']);

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     * @throws Exception
     */
    public function testRenderView(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $controller->method('renderView')->willReturn('string');

        $response = $controller->renderView('path/to/template', ['var' => 'value']);

        $this->assertIsString($response);
    }

    /**
     * @throws Exception
     */
    public function testJson(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $controller->method('json')->willReturn(new JsonResponse());

        $response = $controller->json(['data' => 'value']);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }
}
