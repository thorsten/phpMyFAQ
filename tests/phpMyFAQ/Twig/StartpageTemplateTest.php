<?php

/**
 * Test case for the default start page template
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-11-06
 */

declare(strict_types=1);

namespace phpMyFAQ\Twig;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Twig\Extensions\TagNameTwigExtension;
use PHPUnit\Framework\TestCase;
use stdClass;
use Twig\Extension\AttributeExtension;

class StartpageTemplateTest extends TestCase
{
    /**
     * A FAQ question is stored with literal quote characters (the admin API
     * entity-decodes it before saving). Rendering it unescaped into the
     * title="" attribute of the "Top FAQs" list let an editor break out of
     * the attribute and plant an event handler on the public start page.
     *
     * @throws Exception
     */
    public function testTopTenTitleAttributeIsEscaped(): void
    {
        $topten = new stdClass();
        $topten->title = 'x" onmouseover="alert(1)';
        $topten->preview = 'x" onmouseover="alert(1)';
        $topten->url = 'index.php?action=faq&id=1';
        $topten->visits = '1 view';

        $twigWrapper = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
        $twigWrapper->addExtension(new AttributeExtension(TagNameTwigExtension::class));
        $template = $twigWrapper->loadTemplate('startpage.twig');

        $html = $template->renderBlock('content', [
            'topRecordsList' => [$topten],
            'latestRecordsList' => [],
            'trendingRecordsList' => [],
            'newsList' => [],
            'tagsList' => [],
            'startPageCategories' => false,
            'stickyRecordsList' => [],
        ]);

        static::assertStringNotContainsString('onmouseover="alert(1)"', $html);
        static::assertStringContainsString('title="x&quot; onmouseover=&quot;alert(1)"', $html);
    }
}
