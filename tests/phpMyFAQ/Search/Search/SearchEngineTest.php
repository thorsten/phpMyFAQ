<?php

namespace phpMyFAQ\Search\Search;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use OpenSearch\Client as OpenSearchClient;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
use phpMyFAQ\Configuration\OpenSearchConfiguration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SearchEngineTest extends TestCase
{
    public function testElasticsearchSearchBuildsResultsIncludingCustomPages(): void
    {
        $client = new class {
            public array $calls = [];

            public function search(array $params): object
            {
                $this->calls[] = $params;

                return new class {
                    public function asArray(): array
                    {
                        return [
                            'hits' => [
                                'total' => ['value' => 2],
                                'hits' => [
                                    [
                                        '_source' => [
                                            'id' => 1,
                                            'lang' => 'en',
                                            'question' => 'FAQ',
                                            'answer' => 'Answer',
                                            'keywords' => 'phpmyfaq',
                                            'category_id' => 4,
                                            'content_type' => 'faq',
                                        ],
                                        '_score' => 1.5,
                                    ],
                                    [
                                        '_source' => [
                                            'id' => 2,
                                            'lang' => 'en',
                                            'question' => 'Page',
                                            'answer' => 'Page Answer',
                                            'category_id' => 0,
                                            'content_type' => 'page',
                                            'slug' => 'unit-test-page',
                                        ],
                                        '_score' => 0.9,
                                    ],
                                ],
                            ],
                        ];
                    }
                };
            }
        };

        $search = new Elasticsearch(
            $this->createStub(Configuration::class),
            $client,
            $this->createElasticsearchConfiguration('faq-index'),
        );
        $search->setCategoryIds([1, 2]);

        $results = $search->search('phpmyfaq');

        $this->assertCount(2, $results);
        $this->assertSame('faq-index', $client->calls[0]['index']);
        $this->assertSame(
            [1, 2],
            $client->calls[0]['body']['query']['bool']['should'][0]['bool']['filter']['terms']['category_id'],
        );
        $this->assertSame(1, $results[0]->id);
        $this->assertSame('page', $results[1]->content_type);
        $this->assertSame('unit-test-page', $results[1]->slug);
        $this->assertSame([1, 2], $search->getCategoryIds());
    }

    public function testElasticsearchReturnsEmptyArrayOnClientErrors(): void
    {
        $client = new class {
            public function search(array $params): object
            {
                throw new ClientResponseException('search failed');
            }
        };

        $search = new Elasticsearch(
            $this->createStub(Configuration::class),
            $client,
            $this->createElasticsearchConfiguration('faq-index'),
        );

        $this->assertSame([], $search->search('phpmyfaq'));
        $this->assertSame([], $search->autoComplete('phpmyfaq'));
    }

    public function testElasticsearchAutocompleteUsesLanguageFilterAndReturnsHits(): void
    {
        $client = new class {
            public array $calls = [];

            public function search(array $params): object
            {
                $this->calls[] = $params;

                return new class {
                    public function asArray(): array
                    {
                        return [
                            'hits' => [
                                'total' => ['value' => 1],
                                'hits' => [
                                    [
                                        '_source' => [
                                            'id' => 9,
                                            'lang' => 'de',
                                            'question' => 'Thorsten',
                                            'answer' => 'Rinne',
                                            'keywords' => 'php',
                                            'category_id' => 1,
                                        ],
                                        '_score' => 2.0,
                                    ],
                                ],
                            ],
                        ];
                    }
                };
            }
        };

        $search = new Elasticsearch(
            $this->createStub(Configuration::class),
            $client,
            $this->createElasticsearchConfiguration('faq-index'),
        );
        $search->setLanguage('de');

        $results = $search->autoComplete('thor');

        $this->assertSame('de', $client->calls[0]['body']['query']['bool']['filter']['term']['lang']);
        $this->assertSame('de', $search->getLanguage());
        $this->assertCount(1, $results);
        $this->assertSame(9, $results[0]->id);
    }

    public function testOpenSearchSearchBuildsResultsIncludingCustomPages(): void
    {
        $client = $this
            ->getMockBuilder(OpenSearchClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['search'])
            ->getMock();
        $client
            ->expects($this->once())
            ->method('search')
            ->with($this->callback(static function (array $params): bool {
                return (
                    $params['index'] === 'faq-index'
                    && $params['body']['query']['bool']['should'][0]['bool']['filter']['terms']['category_id'] === [7]
                    && $params['body']['query']['bool']['must']['multi_match']['query'] === 'phpmyfaq'
                );
            }))
            ->willReturn([
                'hits' => [
                    'total' => ['value' => 2],
                    'hits' => [
                        [
                            '_source' => [
                                'id' => 1,
                                'lang' => 'en',
                                'question' => 'FAQ',
                                'answer' => 'Answer',
                                'keywords' => 'phpmyfaq',
                                'category_id' => 4,
                                'content_type' => 'faq',
                            ],
                            '_score' => 1.5,
                        ],
                        [
                            '_source' => [
                                'id' => 2,
                                'lang' => 'en',
                                'question' => 'Page',
                                'answer' => 'Page Answer',
                                'category_id' => 0,
                                'content_type' => 'page',
                                'slug' => 'unit-test-page',
                            ],
                            '_score' => 0.9,
                        ],
                    ],
                ],
            ]);

        $search = new OpenSearch(
            $this->createStub(Configuration::class),
            $client,
            $this->createOpenSearchConfiguration('faq-index'),
        );
        $search->setCategoryIds([7]);

        $results = $search->search('phpmyfaq');

        $this->assertCount(2, $results);
        $this->assertSame('page', $results[1]->content_type);
        $this->assertSame('unit-test-page', $results[1]->slug);
        $this->assertSame([7], $search->getCategoryIds());
    }

    public function testOpenSearchAutocompleteUsesLanguageFilterAndCanReturnEmptyResults(): void
    {
        $client = $this
            ->getMockBuilder(OpenSearchClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['search'])
            ->getMock();
        $client
            ->expects($this->exactly(2))
            ->method('search')
            ->willReturnCallback(static function (array $params): array {
                static $callCount = 0;
                ++$callCount;

                if ($callCount === 1) {
                    return [
                        'hits' => [
                            'total' => ['value' => 0],
                            'hits' => [],
                        ],
                    ];
                }

                return [
                    'hits' => [
                        'total' => ['value' => 1],
                        'hits' => [
                            [
                                '_source' => [
                                    'id' => 10,
                                    'lang' => 'fr',
                                    'question' => 'Bonjour',
                                    'answer' => 'Monde',
                                    'keywords' => '',
                                    'category_id' => 2,
                                ],
                                '_score' => 1.2,
                            ],
                        ],
                    ],
                ];
            });

        $search = new OpenSearch(
            $this->createStub(Configuration::class),
            $client,
            $this->createOpenSearchConfiguration('faq-index'),
        );
        $search->setLanguage('fr');

        $this->assertSame([], $search->autoComplete('bon'));
        $results = $search->autoComplete('bonjour');

        $this->assertSame('fr', $search->getLanguage());
        $this->assertCount(1, $results);
        $this->assertSame(10, $results[0]->id);
    }

    private function createElasticsearchConfiguration(string $index): ElasticsearchConfiguration
    {
        $configuration = $this->createMock(ElasticsearchConfiguration::class);
        $configuration->method('getIndex')->willReturn($index);

        return $configuration;
    }

    private function createOpenSearchConfiguration(string $index): OpenSearchConfiguration
    {
        $configuration = $this->createMock(OpenSearchConfiguration::class);
        $configuration->method('getIndex')->willReturn($index);

        return $configuration;
    }
}
