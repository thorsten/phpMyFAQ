<?php

namespace phpMyFAQ\Plugin;

use PHPUnit\Framework\TestCase;

class PluginEventTest extends TestCase
{
    public function testConstructorSetsData(): void
    {
        $data = 'test data';
        $event = new PluginEvent($data);
        $this->assertEquals($data, $event->getData());
    }

    public function testGetData(): void
    {
        $data = 'another test data';
        $event = new PluginEvent($data);
        $this->assertEquals($data, $event->getData());
    }

    public function testSetOutputAndGetOutput(): void
    {
        $data = 'test data';
        $event = new PluginEvent($data);
        $output = 'test output';
        $event->setOutput($output);
        $this->assertEquals($output, $event->getOutput());
    }

    public function testAppendOutput(): void
    {
        $data = 'test data';
        $event = new PluginEvent($data);
        $output1 = 'output part 1';
        $output2 = 'output part 2';
        $event->setOutput($output1);
        $event->setOutput($output2);
        $this->assertEquals($output1 . $output2, $event->getOutput());
    }
}
