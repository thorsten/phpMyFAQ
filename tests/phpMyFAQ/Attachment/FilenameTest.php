<?php

/**
 * Tests for the attachment Filename helper.
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
 * @since     2026-06-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Attachment;

use PHPUnit\Framework\TestCase;

final class FilenameTest extends TestCase
{
    public function testReturnsOriginalWhenCustomIsNull(): void
    {
        self::assertSame('report.pdf', Filename::compose('report.pdf', null));
    }

    public function testReturnsOriginalWhenCustomIsBlank(): void
    {
        self::assertSame('report.pdf', Filename::compose('report.pdf', '   '));
    }

    public function testAppendsOriginalExtensionWhenCustomHasNoExtension(): void
    {
        self::assertSame('invoice.pdf', Filename::compose('report.pdf', 'invoice'));
    }

    public function testKeepsOriginalExtensionWhenCustomSuppliesADifferentOne(): void
    {
        self::assertSame('invoice.pdf', Filename::compose('report.pdf', 'invoice.txt'));
    }

    public function testStripsPathComponentsFromCustomName(): void
    {
        self::assertSame('invoice.pdf', Filename::compose('report.pdf', '../../etc/invoice'));
    }

    public function testUsesCustomNameAsIsWhenOriginalHasNoExtension(): void
    {
        self::assertSame('invoice', Filename::compose('report', 'invoice'));
    }

    public function testFallsBackToOriginalWhenCustomSanitizesToEmpty(): void
    {
        self::assertSame('report.pdf', Filename::compose('report.pdf', '.pdf'));
    }

    public function testOriginalDotfileNameYieldsBaseWithExtension(): void
    {
        // PHP's pathinfo('.htaccess') treats 'htaccess' as the extension, so it IS appended.
        self::assertSame('backup.htaccess', Filename::compose('.htaccess', 'backup'));
    }

    public function testEmptyOriginalNameWithCustomReturnsCustomName(): void
    {
        // An empty original name is handled gracefully (no extension to preserve).
        self::assertSame('invoice', Filename::compose('', 'invoice'));
    }
}
