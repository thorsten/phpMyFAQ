<?php

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Mail\Builtin;
use phpMyFAQ\Mail\Smtp;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class MailTest extends TestCase
{
    private Mail $mail;

    protected function setUp(): void
    {
        parent::setUp();

        Request::setTrustedHosts(['^.*$']); // Trust all hosts for testing

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);

        $this->mail = new Mail($configuration);
    }

    public function testCreateBoundaryReturnsString(): void
    {
        $result = Mail::createBoundary();

        $this->assertIsString($result);
        $this->assertStringStartsWith('-----', $result);
        $this->assertSame(37, strlen($result));
        $this->assertMatchesRegularExpression('/^-----[a-f0-9]{32}$/', $result);
    }

    public function testGetServerNameWithNoHostHeaders(): void
    {
        unset($_SERVER['HTTP_HOST']);
        unset($_SERVER['SERVER_NAME']);

        $result = Mail::getServerName();

        $this->assertSame('localhost.localdomain', $result);
    }

    /**
     * @throws Exception
     */
    public function testSetFromWithValidAddress(): void
    {
        $result = $this->mail->setFrom('example@example.com', 'John Doe');
        $this->assertTrue($result);
    }

    public function testSetFromWithInvalidAddress(): void
    {
        $this->assertFalse($this->mail->setFrom('invalid-email'));
    }

    public function testValidateEmailWithValidAddress(): void
    {
        $result = Mail::validateEmail('example@example.com');
        $this->assertTrue($result);
    }

    public function testValidateEmailWithInvalidAddress(): void
    {
        $result = Mail::validateEmail('invalid-email');
        $this->assertFalse($result);
    }

    public function testValidateEmailWithEmptyAddress(): void
    {
        $result = Mail::validateEmail('');
        $this->assertFalse($result);
    }

    public function testValidateEmailWithZeroAddress(): void
    {
        $result = Mail::validateEmail('0');
        $this->assertFalse($result);
    }

    public function testValidateEmailWithUnsafeCharacters(): void
    {
        $result = Mail::validateEmail("example@\r\nexample.com");
        $this->assertFalse($result);
    }

    /**
     * @throws Exception
     */
    public function testAddCcWithValidAddress(): void
    {
        $result = $this->mail->addCc('example@example.com', 'John Doe');
        $this->assertTrue($result);
    }

    public function testAddCcWithInvalidAddress(): void
    {
        $this->assertFalse($this->mail->addCc('invalid-email'));
    }

    /**
     * @throws Exception
     */
    public function testAddToWithValidAddress(): void
    {
        $result = $this->mail->addTo('example@example.com', 'John Doe');
        $this->assertTrue($result);
    }

    public function testAddToWithInvalidAddress(): void
    {
        $this->assertFalse($this->mail->addTo('invalid-email'));
    }

    public function testGetDateWithValidTimestamp(): void
    {
        $timestamp = strtotime('2023-01-01 12:00:00');
        $result = Mail::getDate($timestamp);

        $this->assertEquals(date(format: 'r', timestamp: $timestamp), $result);
    }

    public function testGetTimeWithRequestTimeSet(): void
    {
        $_SERVER['REQUEST_TIME'] = strtotime('2023-01-01 12:00:00');
        $result = Mail::getTime();

        $this->assertEquals($_SERVER['REQUEST_TIME'], $result);
    }

    public function testGetTimeWithNoRequestTime(): void
    {
        $requestTimeToRestore = $_SERVER['REQUEST_TIME'] ?? null;
        unset($_SERVER['REQUEST_TIME']);
        $result = Mail::getTime();

        $this->assertLessThanOrEqual(time(), $result);
        $this->assertGreaterThanOrEqual(time() - 1, $result); // Allow for up to 1 second of difference

        $_SERVER['REQUEST_TIME'] = $requestTimeToRestore;
    }

    public function testWrapLinesWithDefaultWidth(): void
    {
        $message = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum acnunc quis neque tempor varius.';
        $result = $this->mail->wrapLines($message);

        $expectedResult = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum\r\nacnunc quis neque tempor varius.";
        $this->assertSame($expectedResult, $result);
    }

    public function testWrapLinesWithCustomWidth(): void
    {
        $message = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum ac nunc quis neque tempor varius.';
        $result = $this->mail->wrapLines($message, 30);

        $expectedResult = "Lorem ipsum dolor sit amet,\r\nconsectetur adipiscing elit.\r\nVestibulum ac nunc quis neque\r\ntempor varius.";
        $this->assertSame($expectedResult, $result);
    }

    public function testFixEOL(): void
    {
        $text = "Line 1\r\nLine 2\rLine 3\nLine 4\r\n";
        $result = $this->mail->fixEOL($text);

        $expectedResult = "Line 1\r\nLine 2\r\nLine 3\r\nLine 4\r\n";
        $this->assertSame($expectedResult, $result);
    }

    public function testGetMUAWithBuiltin(): void
    {
        $result = Mail::getMUA('builtin');
        $this->assertInstanceOf(Builtin::class, $result);
    }

    public function testGetMUAWithSMTP(): void
    {
        $result = Mail::getMUA('smtp');
        $this->assertInstanceOf(Smtp::class, $result);
    }

    /**
     * @throws Exception
     */
    public function testSetReplyToWithValidAddress(): void
    {
        $result = $this->mail->setReplyTo('example@example.com', 'John Doe');
        $this->assertTrue($result);
    }

    public function testSetReplyToWithInvalidAddress(): void
    {
        $this->assertFalse($this->mail->setReplyTo('invalid-email'));
    }

    public function testSafeEmailWithSafeEmailEnabled(): void
    {
        $configurationMock = $this->createStub(Configuration::class);
        $configurationMock->method('get')->willReturn(true);

        $instance = new Mail($configurationMock);

        $result = $instance->safeEmail('test@example.com');
        $this->assertSame('test_AT_example_DOT_com', $result);
    }

    public function testSafeEmailWithSafeEmailDisabled(): void
    {
        $configurationMock = $this->createStub(Configuration::class);
        $configurationMock->method('get')->willReturn(false);

        $instance = new Mail($configurationMock);

        $result = $instance->safeEmail('test@example.com');
        $this->assertSame('test@example.com', $result);
    }
}
