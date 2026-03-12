<?php

declare(strict_types=1);

namespace phpMyFAQ\Form;

use phpMyFAQ\Configuration;
use phpMyFAQ\Forms;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(FormsFactory::class)]
#[UsesClass(Forms::class)]
#[UsesClass(FormsRepository::class)]
final class FormsFactoryTest extends TestCase
{
    public function testCreateBuildsFormsWithDefaultRepository(): void
    {
        $configuration = $this->createStub(Configuration::class);

        $forms = FormsFactory::create($configuration);

        $this->assertInstanceOf(Forms::class, $forms);
        $this->assertSame($configuration, $this->readProperty($forms, 'configuration'));
        $this->assertInstanceOf(FormsRepository::class, $this->readProperty($forms, 'formsRepository'));
    }

    public function testCreateBuildsFormsWithInjectedRepository(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $repository = $this->createMock(FormsRepositoryInterface::class);

        $forms = FormsFactory::create($configuration, $repository);

        $this->assertInstanceOf(Forms::class, $forms);
        $this->assertSame($repository, $this->readProperty($forms, 'formsRepository'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflectionProperty = new ReflectionProperty($object, $property);
        return $reflectionProperty->getValue($object);
    }
}
