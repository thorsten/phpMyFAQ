<?php

namespace phpMyFAQ\Plugin;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MockPlugin implements PluginInterface
{
    private PluginManager $manager;

    public function __construct($manager)
    {
        $this->manager = $manager;
    }

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

    public function getConfig(): array
    {
        return [
            'option1' => 'value1'
        ];
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
