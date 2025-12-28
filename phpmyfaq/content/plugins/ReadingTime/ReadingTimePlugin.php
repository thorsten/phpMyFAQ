<?php

/**
 * The ReadingTime Plugin
 *
 * This plugin estimates and displays the reading time for FAQ articles.
 *
 * Add
 *
 *      {{ phpMyFAQPlugin('reading.time', answer) | raw }}
 *
 * into the Twig template.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-07-10
 */

declare(strict_types=1);

namespace phpMyFAQ\Plugin\ReadingTime;

use phpMyFAQ\Plugin\PluginInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

require_once __DIR__ . '/ReadingTimePluginConfiguration.php';

class ReadingTimePlugin implements PluginInterface
{
    private ReadingTimePluginConfiguration $config;

    public function __construct()
    {
        $this->config = new ReadingTimePluginConfiguration();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'ReadingTime';
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): string
    {
        return '0.2.0';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Example plugin that shows estimated reading time for FAQ articles with plugin configuration example.';
    }

    /**
     * @inheritDoc
     */
    public function getAuthor(): string
    {
        return 'phpMyFAQ Team';
    }

    /**
     * @inheritDoc
     */
    public function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getConfig(): ?ReadingTimePluginConfiguration
    {
        return $this->config;
    }

    /**
     * @inheritDoc
     */
    public function getStylesheets(): array
    {
        return []; // No stylesheets for this plugin
    }

    /**
     * @inheritDoc
     */
    public function getTranslationsPath(): ?string
    {
        return null; // No translations for this plugin
    }

    /**
     * @inheritDoc
     */
    public function registerEvents(EventDispatcherInterface $eventDispatcher): void
    {
        $eventDispatcher->addListener('reading.time', [$this, 'addReadingTime']);
    }

    public function addReadingTime($event): void
    {
        $content = $event->getData();
        $readingTime = $this->calculateReadingTime($content);
        $badge = $this->generateBadge($readingTime);

        $event->setOutput($badge);
    }

    private function calculateReadingTime(string $content): int
    {
        $plainText = strip_tags($content);

        $wordCount = str_word_count($plainText);

        return max(1, (int) ceil($wordCount / $this->config->wordsPerMinute));
    }

    private function generateBadge(int $minutes): string
    {
        $showIcon = $this->config->showIcon;

        $icon = $showIcon ? '<i class="bi bi-clock"></i> ' : '';
        $pluralSuffix = $minutes === 1 ? '' : 'n';

        return sprintf('%s ~ %d min %s', $icon, $minutes, $pluralSuffix);
    }
}
