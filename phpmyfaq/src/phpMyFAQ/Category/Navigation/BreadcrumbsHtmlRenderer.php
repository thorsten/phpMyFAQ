<?php

declare(strict_types=1);

namespace phpMyFAQ\Category\Navigation;

use phpMyFAQ\Configuration;
use phpMyFAQ\Link;
use phpMyFAQ\Strings;

/**
 * Renders breadcrumb segments as HTML using existing Link + Strings utilities.
 */
final class BreadcrumbsHtmlRenderer
{
    /**
     * @param array<int, array{id:int, name:string, description:string}> $segments
     */
    public function render(Configuration $configuration, array $segments, string $useCssClass = 'breadcrumb'): string
    {
        $items = [];
        foreach ($segments as $index => $segment) {
            $url = sprintf('%sindex.php?action=show&cat=%d', $configuration->getDefaultUrl(), (int) $segment['id']);
            $oLink = new Link($url, $configuration);
            $oLink->text = Strings::htmlentities($segment['name']);
            $oLink->itemTitle = Strings::htmlentities($segment['name']);
            $oLink->tooltip = Strings::htmlentities($segment['description'] ?? '');
            if (0 === $index) {
                $oLink->setRelation('index');
            }
            $items[] = sprintf('<li class="breadcrumb-item">%s</li>', $oLink->toHtmlAnchor());
        }

        return sprintf('<ul class="%s">%s</ul>', $useCssClass, implode('', $items));
    }
}
