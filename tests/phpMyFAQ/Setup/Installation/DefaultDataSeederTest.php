<?php

namespace phpMyFAQ\Setup\Installation;

use PHPUnit\Framework\TestCase;

class DefaultDataSeederTest extends TestCase
{
    private DefaultDataSeeder $seeder;

    protected function setUp(): void
    {
        $this->seeder = new DefaultDataSeeder();
    }

    public function testGetMainConfigReturnsNonEmptyArray(): void
    {
        $config = $this->seeder->getMainConfig();
        $this->assertNotEmpty($config);
        $this->assertGreaterThan(60, count($config));
    }

    public function testGetMainConfigContainsRequiredKeys(): void
    {
        $config = $this->seeder->getMainConfig();
        $this->assertArrayHasKey('main.currentVersion', $config);
        $this->assertArrayHasKey('main.currentApiVersion', $config);
        $this->assertArrayHasKey('main.language', $config);
        $this->assertArrayHasKey('main.phpMyFAQToken', $config);
        $this->assertArrayHasKey('security.permLevel', $config);
        $this->assertArrayHasKey('spam.enableCaptchaCode', $config);
        $this->assertArrayHasKey('session.handler', $config);
        $this->assertArrayHasKey('session.redisDsn', $config);
    }

    public function testGetMainConfigHasDynamicValues(): void
    {
        $config = $this->seeder->getMainConfig();
        // currentVersion should be set to an actual version, not null
        $this->assertNotNull($config['main.currentVersion']);
        // phpMyFAQToken should be a hex string
        $this->assertNotNull($config['main.phpMyFAQToken']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $config['main.phpMyFAQToken']);
    }

    public function testApplyPersonalSettings(): void
    {
        $this->seeder->applyPersonalSettings('John Doe', 'john@example.com', 'de', 'medium');
        $config = $this->seeder->getMainConfig();

        $this->assertEquals('John Doe', $config['main.metaPublisher']);
        $this->assertEquals('john@example.com', $config['main.administrationMail']);
        $this->assertEquals('de', $config['main.language']);
        $this->assertEquals('medium', $config['security.permLevel']);
    }

    public function testGetMainRightsReturnsNonEmptyArray(): void
    {
        $rights = $this->seeder->getMainRights();
        $this->assertNotEmpty($rights);
        $this->assertEquals(50, count($rights));
    }

    public function testGetMainRightsHasCorrectStructure(): void
    {
        $rights = $this->seeder->getMainRights();
        foreach ($rights as $right) {
            $this->assertArrayHasKey('name', $right);
            $this->assertArrayHasKey('description', $right);
            $this->assertNotEmpty($right['name']);
            $this->assertNotEmpty($right['description']);
        }
    }

    public function testGetFormInputsReturnsCorrectCount(): void
    {
        $inputs = $this->seeder->getFormInputs();
        $this->assertCount(15, $inputs);
    }

    public function testGetFormInputsHasCorrectStructure(): void
    {
        $inputs = $this->seeder->getFormInputs();
        foreach ($inputs as $input) {
            $this->assertArrayHasKey('form_id', $input);
            $this->assertArrayHasKey('input_id', $input);
            $this->assertArrayHasKey('input_type', $input);
            $this->assertArrayHasKey('input_label', $input);
            $this->assertArrayHasKey('input_active', $input);
            $this->assertArrayHasKey('input_required', $input);
            $this->assertArrayHasKey('input_lang', $input);
        }
    }

    public function testGetFormInputsHasTwoForms(): void
    {
        $inputs = $this->seeder->getFormInputs();
        $formIds = array_unique(array_column($inputs, 'form_id'));
        $this->assertCount(2, $formIds);
        $this->assertContains(1, $formIds);
        $this->assertContains(2, $formIds);
    }
}
