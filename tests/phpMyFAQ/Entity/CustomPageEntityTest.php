<?php

/**
 * Test case for CustomPage Entity
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
 */

namespace phpMyFAQ\Entity;

use DateTime;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Class CustomPageEntityTest
 */
#[AllowMockObjectsWithoutExpectations]
class CustomPageEntityTest extends TestCase
{
    private CustomPageEntity $customPage;

    protected function setUp(): void
    {
        $this->customPage = new CustomPageEntity();
    }

    /**
     * Test CustomPageEntity instantiation
     */
    public function testCustomPageInstantiation(): void
    {
        $this->assertInstanceOf(CustomPageEntity::class, $this->customPage);
    }

    /**
     * Test id getter and setter
     */
    public function testIdGetterAndSetter(): void
    {
        $id = 123;
        $result = $this->customPage->setId($id);

        $this->assertInstanceOf(CustomPageEntity::class, $result); // Test fluent interface
        $this->assertEquals($id, $this->customPage->getId());
    }

    /**
     * Test language getter and setter
     */
    public function testLanguageGetterAndSetter(): void
    {
        $language = 'en';
        $result = $this->customPage->setLanguage($language);

        $this->assertInstanceOf(CustomPageEntity::class, $result);
        $this->assertEquals($language, $this->customPage->getLanguage());
    }

    /**
     * Test pageTitle getter and setter
     */
    public function testPageTitleGetterAndSetter(): void
    {
        $title = 'Privacy Policy';
        $result = $this->customPage->setPageTitle($title);

        $this->assertInstanceOf(CustomPageEntity::class, $result);
        $this->assertEquals($title, $this->customPage->getPageTitle());
    }

    /**
     * Test slug getter and setter
     */
    public function testSlugGetterAndSetter(): void
    {
        $slug = 'privacy-policy';
        $result = $this->customPage->setSlug($slug);

        $this->assertInstanceOf(CustomPageEntity::class, $result);
        $this->assertEquals($slug, $this->customPage->getSlug());
    }

    /**
     * Test content getter and setter
     */
    public function testContentGetterAndSetter(): void
    {
        $content = '<p>This is the privacy policy content.</p>';
        $result = $this->customPage->setContent($content);

        $this->assertInstanceOf(CustomPageEntity::class, $result);
        $this->assertEquals($content, $this->customPage->getContent());
    }

    /**
     * Test authorName getter and setter
     */
    public function testAuthorNameGetterAndSetter(): void
    {
        $authorName = 'John Doe';
        $result = $this->customPage->setAuthorName($authorName);

        $this->assertInstanceOf(CustomPageEntity::class, $result);
        $this->assertEquals($authorName, $this->customPage->getAuthorName());
    }

    /**
     * Test authorEmail getter and setter
     */
    public function testAuthorEmailGetterAndSetter(): void
    {
        $email = 'john@example.com';
        $result = $this->customPage->setAuthorEmail($email);

        $this->assertInstanceOf(CustomPageEntity::class, $result);
        $this->assertEquals($email, $this->customPage->getAuthorEmail());
    }

    /**
     * Test active getter and setter
     */
    public function testActiveGetterAndSetter(): void
    {
        $result = $this->customPage->setActive(true);

        $this->assertInstanceOf(CustomPageEntity::class, $result);
        $this->assertTrue($this->customPage->isActive());

        $this->customPage->setActive(false);
        $this->assertFalse($this->customPage->isActive());
    }

    /**
     * Test created getter and setter
     */
    public function testCreatedGetterAndSetter(): void
    {
        $date = new DateTime('2026-01-12 12:00:00');
        $result = $this->customPage->setCreated($date);

        $this->assertInstanceOf(CustomPageEntity::class, $result);
        $this->assertEquals($date, $this->customPage->getCreated());
    }

    /**
     * Test updated getter and setter
     */
    public function testUpdatedGetterAndSetter(): void
    {
        $date = new DateTime('2026-01-13 12:00:00');
        $result = $this->customPage->setUpdated($date);

        $this->assertInstanceOf(CustomPageEntity::class, $result);
        $this->assertEquals($date, $this->customPage->getUpdated());
    }

    /**
     * Test updated returns null when not set
     */
    public function testUpdatedReturnsNullWhenNotSet(): void
    {
        $this->assertNull($this->customPage->getUpdated());
    }

    /**
     * Test fluent interface with method chaining
     */
    public function testFluentInterface(): void
    {
        $result = $this->customPage
            ->setId(1)
            ->setLanguage('en')
            ->setPageTitle('Test Page')
            ->setSlug('test-page')
            ->setContent('<p>Test content</p>')
            ->setAuthorName('Test Author')
            ->setAuthorEmail('test@example.com')
            ->setActive(true)
            ->setCreated(new DateTime());

        $this->assertInstanceOf(CustomPageEntity::class, $result);
        $this->assertEquals(1, $this->customPage->getId());
        $this->assertEquals('en', $this->customPage->getLanguage());
        $this->assertEquals('Test Page', $this->customPage->getPageTitle());
        $this->assertEquals('test-page', $this->customPage->getSlug());
    }
}
