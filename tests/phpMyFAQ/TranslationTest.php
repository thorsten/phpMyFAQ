<?php

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;

/**
 * @testdox A Translation
 */
class TranslationTest extends TestCase
{
    private Translation $translation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translation = Translation::create();
    }

    /**
     * @testdox can set a languages directory
     * @throws Exception
     */
    public function testSetLanguagesDir(): void
    {
        $this->assertEquals(
            $this->translation,
            $this->translation->setLanguagesDir(__DIR__ . '/../../phpmyfaq/translations')
        );
    }

    /**
     * @testdox throws an exception when set a wrong languages directory
     */
    public function testSetLanguagesDirWithException(): void
    {
        $this->expectException(Exception::class);
        $this->assertEquals(
            $this->translation,
            $this->translation->setLanguagesDir(__DIR__ . '/foo/bar')
        );
    }

    /**
     * @testdox returns a translated key
     * @throws Exception
     */
    public function testGet(): void
    {
        Translation::create()
            ->setLanguagesDir(__DIR__ . '/../../phpmyfaq/translations')
            ->setCurrentLanguage('de');
        $this->assertEquals('deutsch', Translation::get('language'));
    }

    /**
     * @testdox returns the class if calling the factory method
     */
    public function testCreate(): void
    {
        $this->assertEquals(
            $this->translation,
            Translation::create()
        );
    }

    /**
     * @testdox returns the class if calling the getInstance() method
     */
    public function testGetInstance(): void
    {
        $this->assertEquals(
            Translation::create(),
            Translation::getInstance()
        );
    }

    /**
     * @testdox returns the current language
     * @throws Exception
     */
    public function testGetCurrentLanguage(): void
    {
        Translation::create()
            ->setLanguagesDir(__DIR__ . '/../../phpmyfaq/translations')
            ->setCurrentLanguage('de');
        $this->assertEquals(
            'de',
            Translation::create()
                ->setLanguagesDir(__DIR__ . '/../../phpmyfaq/translations')
                ->getCurrentLanguage()
        );
    }

    /**
     * @testdox sets the default language
     * @throws Exception
     */
    public function testSetDefaultLanguage(): void
    {
        Translation::create()
            ->setLanguagesDir(__DIR__ . '/../../phpmyfaq/translations')
            ->setDefaultLanguage('fi');
        $this->assertEquals(
            'fi',
            Translation::create()
                ->setLanguagesDir(__DIR__ . '/../../phpmyfaq/translations')
                ->getDefaultLanguage()
        );
    }
}
