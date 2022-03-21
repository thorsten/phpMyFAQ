<?php

namespace phpMyFAQ\Helper;

use PHPUnit\Framework\TestCase;

/**
 * Class AdministrationTest
 */
class AdministrationHelperTest extends TestCase
{
    /** @var AdministrationHelper */
    protected AdministrationHelper $instance;

    protected function setUp(): void
    {
        $this->instance = new AdministrationHelper();
    }

    public function testRenderMetaRobotsDropdown(): void
    {
        $expected = '<option selected>index, follow</option><option>index, nofollow</option>' .
            '<option>noindex, follow</option><option>noindex, nofollow</option>';
        $actual = $this->instance->renderMetaRobotsDropdown('index, follow');

        $this->assertEquals($expected, $actual);
    }

} 
