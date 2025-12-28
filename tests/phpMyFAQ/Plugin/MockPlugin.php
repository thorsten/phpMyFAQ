<?php

namespace phpMyFAQ\Plugin;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MockPlugin implements PluginInterface
{

    public function getName(): string
    {
        return 'mockPlugin';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'A simple mock plugin';
    }

    public function getAuthor(): string
    {
        return 'phpMyFAQ Team';
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getConfig(): ?PluginConfigurationInterface
    {
        return null; // No configuration needed for mock plugin
    }

    public function getStylesheets(): array
    {
        return [];
    }

    public function getTranslationsPath(): ?string
    {
        return null;
    }

    public function registerEvents(EventDispatcherInterface $eventDispatcher): void
    {
        $eventDispatcher->addListener('mock.event', [$this, 'onMockEvent']);
    }

    public function onMockEvent($event): void
    {
        $event->setOutput("MockPlugin: Event triggered.");
    }
}
