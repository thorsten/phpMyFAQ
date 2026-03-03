<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Configuration;
use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(CustomPageController::class)]
#[UsesNamespace('phpMyFAQ')]
final class CustomPageControllerWebTest extends ControllerWebTestCase
{
    private function seedCustomPage(int $id, string $slug, string $title, string $content, string $active): void
    {
        $configuration = $this->getConfiguration('public');
        self::assertInstanceOf(Configuration::class, $configuration);

        $db = $configuration->getDb();
        $db->query(sprintf(
            "DELETE FROM faqcustompages WHERE id = %d AND lang = 'en'",
            $id,
        ));
        $db->query(sprintf(
            "INSERT INTO faqcustompages
                (id, lang, page_title, slug, content, author_name, author_email, active, created, seo_robots)
             VALUES
                (%d, 'en', '%s', '%s', '%s', 'Test Author', 'test@example.com', '%s', '2026-03-03 10:00:00', 'index,follow')",
            $id,
            $db->escape($title),
            $db->escape($slug),
            $db->escape($content),
            $db->escape($active),
        ));
    }

    public function testActiveCustomPageRenders(): void
    {
        $this->seedCustomPage(101, 'unit-test-page', 'Unit Test Page', '<p>Unit test content</p>', 'y');

        $response = $this->requestPublic('GET', '/page/unit-test-page.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Unit Test Page', $response);
        self::assertResponseContains('Unit test content', $response);
    }

    public function testInactiveCustomPageRendersNotFoundPage(): void
    {
        $this->seedCustomPage(102, 'page-2', 'Inactive Page', '<p>Inactive content</p>', 'n');

        $response = $this->requestPublic('GET', '/page/page-2.html');

        self::assertContains($response->getStatusCode(), [200, 404]);
        self::assertResponseContains('404', $response);
    }
}
