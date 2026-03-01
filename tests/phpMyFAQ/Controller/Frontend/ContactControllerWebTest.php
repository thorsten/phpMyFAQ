<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(ContactController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ContactControllerWebTest extends ControllerWebTestCase
{
    public function testContactPageRenders(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);

        $response = $this->requestPublic('GET', '/contact.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Contact', $response);
    }

    public function testContactPageFormatsPlainTextContactInformationWithLineBreaks(): void
    {
        $this->getConfiguration()->getAll();
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'main.contactInformation' => "Line one\nLine two",
            'layout.contactInformationHTML' => false,
        ]);

        $response = $this->requestPublic('GET', '/contact.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('Line one<br />', $response);
        self::assertResponseContains('Line two', $response);
    }

    public function testContactPageRendersHtmlContactInformationWhenEnabled(): void
    {
        $this->getConfiguration()->getAll();
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'main.contactInformation' => '&lt;strong&gt;HTML contact&lt;/strong&gt;',
            'layout.contactInformationHTML' => true,
        ]);

        $response = $this->requestPublic('GET', '/contact.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('<strong>HTML contact</strong>', $response);
    }

    public function testContactPageKeepsEncodedHtmlEscapedWhenHtmlModeIsDisabled(): void
    {
        $this->getConfiguration()->getAll();
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'main.contactInformation' => '&lt;strong&gt;Escaped contact&lt;/strong&gt;',
            'layout.contactInformationHTML' => false,
        ]);

        $response = $this->requestPublic('GET', '/contact.html');

        self::assertResponseIsSuccessful($response);
        self::assertResponseContains('&lt;strong&gt;Escaped contact&lt;/strong&gt;', $response);
    }
}
