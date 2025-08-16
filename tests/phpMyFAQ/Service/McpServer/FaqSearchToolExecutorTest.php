<?php

namespace phpMyFAQ\Service\McpServer;

use PHPUnit\Framework\TestCase;
use phpMyFAQ\Configuration;
use phpMyFAQ\Search;
use phpMyFAQ\Faq;
use Symfony\AI\McpSdk\Capability\Tool\ToolCall;
use Symfony\AI\McpSdk\Capability\Tool\ToolCallResult;

class FaqSearchToolExecutorTest extends TestCase
{
    private FaqSearchToolExecutor $executor;
    private Search $searchMock;
    private Faq $faqMock;

    protected function setUp(): void
    {
        $configMock = $this->createMock(Configuration::class);
        $this->searchMock = $this->createMock(Search::class);
        $this->faqMock = $this->createMock(Faq::class);

        $configMock->method('getDefaultUrl')->willReturn('https://example.com/');

        $this->executor = new FaqSearchToolExecutor(
            $configMock,
            $this->searchMock,
            $this->faqMock
        );
    }

    public function testGetName(): void
    {
        $this->assertSame('faq_search', $this->executor->getName());
    }

    public function testCallWithEmptyQueryReturnsError(): void
    {
        $toolCall = new ToolCall('test-id', 'faq_search', ['query' => '']);
        $result = $this->executor->call($toolCall);

        $this->assertInstanceOf(ToolCallResult::class, $result);
        $this->assertStringContainsString('Search query cannot be empty', $result->result);
    }

    public function testCallWithNoResults(): void
    {
        $toolCall = new ToolCall('test-id', 'faq_search', ['query' => 'test']);
        $this->searchMock->method('search')->willReturn([]);

        $result = $this->executor->call($toolCall);

        $this->assertStringContainsString('No FAQ entries found', $result->result);
    }

    public function testCallWithResults(): void
    {
        $toolCall = new ToolCall('test-id', 'faq_search', ['query' => 'test', 'limit' => 1]);
        $searchResult = (object)[
            'id' => 42,
            'lang' => 'en',
            'question' => 'What is phpMyFAQ?',
            'answer' => 'phpMyFAQ is an open source FAQ system.',
            'category_id' => 1,
            'score' => 0.95
        ];
        $this->searchMock->method('search')->willReturn([$searchResult]);
        $this->faqMock->method('getFaqResult')->willReturn(['id' => 42]);

        $result = $this->executor->call($toolCall);

        $this->assertStringContainsString('Found 1 relevant FAQ entries', $result->result);
        $this->assertStringContainsString('What is phpMyFAQ?', $result->result);
        $this->assertStringContainsString('https://example.com/index.php?action=faq&cat=0&id=42&artlang=en', $result->result);
    }

    public function testCallWithException(): void
    {
        $toolCall = new ToolCall('test-id', 'faq_search', ['query' => 'test']);
        $this->searchMock->method('search')->willThrowException(new \Exception('DB error'));

        $result = $this->executor->call($toolCall);

        $this->assertStringContainsString('Error searching FAQ database', $result->result);
    }
}
