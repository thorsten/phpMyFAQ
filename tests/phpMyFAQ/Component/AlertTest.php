<?php

namespace phpMyFAQ\Component;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;

class AlertTest extends TestCase
{
    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setLanguagesDir(__DIR__ . '/../../../phpmyfaq/translations')
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('de');
    }

    public function testDangerWithoutError(): void
    {
        $this->assertEquals(
            '<div class="alert alert-danger alert-dismissible fade show mt-2"><h4 class="alert-heading">Hilfe</h4>' .
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>',
            Alert::danger('msgHelp')
        );
    }

    public function testDangerWithError(): void
    {
        $this->assertEquals(
            '<div class="alert alert-danger alert-dismissible fade show mt-2"><h4 class="alert-heading">Hilfe</h4>' .
            '<p>FooBarError!</p>' .
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>',
            Alert::danger('msgHelp', 'FooBarError!')
        );
    }

    public function testSuccess(): void
    {
        $this->assertEquals(
            '<div class="alert alert-success alert-dismissible fade show">Hilfe' .
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>',
            Alert::success('msgHelp')
        );
    }
}
