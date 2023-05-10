<?php

namespace phpMyFAQ\Configuration;

class ElasticsearchConfiguration
{
    /** @var string[] */
    private readonly array $hosts;

    private readonly string $index;

    public function __construct(string $filename)
    {
        $PMF_ES = [
            'hosts' => [],
            'index' => '',
        ];

        include($filename);

        $this->hosts = $PMF_ES['hosts'];
        $this->index = $PMF_ES['index'];
    }

    /**
     * @return string[]
     */
    public function getHosts(): array
    {
        return $this->hosts;
    }

    public function getIndex(): string
    {
        return $this->index;
    }
}
