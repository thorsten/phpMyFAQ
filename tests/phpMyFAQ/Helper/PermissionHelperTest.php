<?php

namespace phpMyFAQ\Helper;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\Configuration;

class PermissionHelperTest extends TestCase
{
    private PermissionHelper $permissionHelper;
    private Configuration $configuration;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->configuration = $this->createStub(Configuration::class);
        $this->permissionHelper = new PermissionHelper();
    }

    public function testPermOptions(): void
    {
        $current = 'basic';
        $expected = '<option value="basic" selected>basic</option><option value="medium" >medium</option>';

        $options = $this->permissionHelper::permOptions($current);

        $this->assertEquals($expected, $options);
    }

    public function testPermOptionsWithMediumSelected(): void
    {
        $current = 'medium';
        $expected = '<option value="basic" >basic</option><option value="medium" selected>medium</option>';

        $options = $this->permissionHelper::permOptions($current);

        $this->assertEquals($expected, $options);
    }

    public function testPermOptionsWithInvalidSelected(): void
    {
        $current = 'invalid';
        $expected = '<option value="basic" >basic</option><option value="medium" >medium</option>';

        $options = $this->permissionHelper::permOptions($current);

        $this->assertEquals($expected, $options);
    }
}
