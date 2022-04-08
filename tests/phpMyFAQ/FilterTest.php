<?php

/**
 * Test suite for Filter class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-04-08
 */

namespace phpMyFAQ;

use PHPUnit\Framework\TestCase;

/**
 * @testdox A Filter
 */
class FilterTest extends TestCase
{
    /**
     * @testdox removes unwanted attributes
     */
    public function testRemoveAttributes()
    {
        $expected = '<a href="#">phpMyFAQ</a>';
        $actual = Filter::removeAttributes($expected);
        $this->assertEquals($expected, $actual);

        $expected = '<a href="#">phpMyFAQ</a>';
        $toTest = '<a href="#" onchange="bar()">phpMyFAQ</a>';
        $actual = Filter::removeAttributes($toTest);
        $this->assertEquals($expected, $actual);

        $expected = '<a href="#">phpMyFAQ</a>';
        $toTest = '<a href="#" disabled="disabled">phpMyFAQ</a>';
        $actual = Filter::removeAttributes($toTest);
        $this->assertEquals($expected, $actual);

        $expected = 'To: sslEnabledProtocols="TLSv1.2"';
        $actual = Filter::removeAttributes($expected);
        $this->assertEquals($expected, $actual);
    }
}
