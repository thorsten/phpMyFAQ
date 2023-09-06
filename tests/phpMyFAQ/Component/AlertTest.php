<?php

namespace phpMyFAQ\Component;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;

/**
 * @testdox An Alert component
 */
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

    /**
     * @testdox renders a danger alert without an error message
     */
    public function testDangerWithoutError(): void
    {
        $this->assertEquals(
            '<div class="alert alert-danger alert-dismissible fade show mt-2"><h4 class="alert-heading">Hilfe</h4>' .
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>',
            Alert::danger('msgHelp')
        );
    }

    /**
     * @testdox renders a danger alert with an error message
     */
    public function testDangerWithError(): void
    {
        $this->assertEquals(
            '<div class="alert alert-danger alert-dismissible fade show mt-2"><h4 class="alert-heading">Hilfe</h4>' .
            '<p>FooBarError!</p>' .
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>',
            Alert::danger('msgHelp', 'FooBarError!')
        );
    }

    /**
     * @testdox renders a warning alert
     */
    public function testWarning(): void
    {
        $this->assertEquals(
            '<div class="alert alert-warning alert-dismissible fade show">Hilfe' .
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>',
            Alert::warning('msgHelp')
        );
    }

    /**
     * @testdox renders a success alert
     */
    public function testSuccess(): void
    {
        $this->assertEquals(
            '<div class="alert alert-success alert-dismissible fade show">Hilfe' .
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>',
            Alert::success('msgHelp')
        );
    }

    /**
     * @testdox renders a info alert
     */
    public function testInfo(): void
    {
        $this->assertEquals(
            '<div class="alert alert-info">Hilfe</div>',
            Alert::info('msgHelp')
        );
    }
}
