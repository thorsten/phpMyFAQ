<?php

namespace phpMyFAQ\Plugin;

use Symfony\Contracts\EventDispatcher\Event;

class MockPluginEvent extends Event
{
    private $data;
    private $output = '';

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setOutput($output)
    {
        $this->output .= $output;
    }

    public function getOutput()
    {
        return $this->output;
    }
}
