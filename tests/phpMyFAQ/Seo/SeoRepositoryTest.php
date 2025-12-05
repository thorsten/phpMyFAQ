<?php

declare(strict_types=1);

namespace phpMyFAQ\Seo;

use DateTime;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Entity\SeoEntity;
use phpMyFAQ\Enums\SeoType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SeoRepositoryTest extends TestCase
{
    private Configuration $configuration;
    private DatabaseDriver&MockObject $database;
    private SeoRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuration = $this->createStub(Configuration::class);
        $this->database = $this->createMock(DatabaseDriver::class);

        $this->configuration
            ->method('getDb')
            ->willReturn($this->database);

        $this->repository = new SeoRepository($this->configuration);
    }

    public function testCreateCallsInsertOnDatabase(): void
    {
        $entity = (new SeoEntity())
            ->setSeoType(SeoType::FAQ)
            ->setReferenceId(42)
            ->setReferenceLanguage('en')
            ->setTitle('Title')
            ->setDescription('Description');

        $this->database
            ->expects($this->once())
            ->method('nextId')
            ->with($this->stringContains('faqseo'), 'id')
            ->willReturn(1);

        $this->database
            ->expects($this->atLeastOnce())
            ->method('escape')
            ->willReturnArgument(0);

        $this->database
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('INSERT INTO'))
            ->willReturn(true);

        $this->assertTrue($this->repository->create($entity));
    }

    public function testUpdateCallsUpdateOnDatabase(): void
    {
        $entity = (new SeoEntity())
            ->setSeoType(SeoType::FAQ)
            ->setReferenceId(42)
            ->setReferenceLanguage('en')
            ->setTitle('Updated Title')
            ->setDescription('Updated Description');

        $this->database
            ->expects($this->atLeastOnce())
            ->method('escape')
            ->willReturnArgument(0);

        $this->database
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('UPDATE'))
            ->willReturn(true);

        $this->assertTrue($this->repository->update($entity));
    }

    public function testDeleteCallsDeleteOnDatabase(): void
    {
        $entity = (new SeoEntity())
            ->setSeoType(SeoType::FAQ)
            ->setReferenceId(42)
            ->setReferenceLanguage('en')
            ->setTitle('Title')
            ->setDescription('Description');

        $this->database
            ->expects($this->atLeastOnce())
            ->method('escape')
            ->willReturnArgument(0);

        $this->database
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DELETE FROM'))
            ->willReturn(true);

        $this->assertTrue($this->repository->delete($entity));
    }

    public function testGetPopulatesEntityWhenRowExists(): void
    {
        $entity = (new SeoEntity())
            ->setSeoType(SeoType::FAQ)
            ->setReferenceId(42)
            ->setReferenceLanguage('en');

        $result = new class {
        };

        $this->database
            ->expects($this->atLeastOnce())
            ->method('escape')
            ->willReturnArgument(0);

        $this->database
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT * FROM'))
            ->willReturn($result);

        $this->database
            ->expects($this->once())
            ->method('numRows')
            ->with($result)
            ->willReturn(1);

        $this->database
            ->expects($this->exactly(2))
            ->method('fetchObject')
            ->with($result)
            ->willReturnOnConsecutiveCalls(
                (object) [
                    'id' => 1,
                    'title' => 'Fetched Title',
                    'description' => 'Fetched Description',
                    'created' => (new DateTime())->format('Y-m-d H:i:s'),
                ],
                null,
            );

        $fetched = $this->repository->get($entity);

        $this->assertSame(1, $fetched->getId());
        $this->assertSame('Fetched Title', $fetched->getTitle());
        $this->assertSame('Fetched Description', $fetched->getDescription());
        $this->assertInstanceOf(DateTime::class, $fetched->getCreated());
    }
}
