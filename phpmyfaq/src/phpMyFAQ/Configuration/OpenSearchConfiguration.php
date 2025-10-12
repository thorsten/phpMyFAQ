<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

readonly class OpenSearchConfiguration
{
    /** @var string[] */
    private array $hosts;

    private string $index;

    public function __construct(string $filename)
    {
        $PMF_OS = [
            'hosts' => [],
            'index' => '',
        ];

        include $filename;

        $this->hosts = $PMF_OS['hosts'];
        $this->index = $PMF_OS['index'];
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
