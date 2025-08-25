<?php

namespace phpMyFAQ\Helper;

use phpMyFAQ\Configuration;
use phpMyFAQ\Mail;
use phpMyFAQ\User;
use phpMyFAQ\User\UserData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RegistrationHelperTest extends TestCase
{
    private RegistrationHelper $registrationHelper;
    private MockObject|Configuration $configurationMock;
    private MockObject|User $userMock;
    private MockObject|UserData $userDataMock;
    private MockObject|Mail $mailMock;

    protected function setUp(): void
    {
        $this->configurationMock = $this->createMock(Configuration::class);
        $this->userMock = $this->createMock(User::class);
        $this->userDataMock = $this->createMock(UserData::class);
        $this->mailMock = $this->createMock(Mail::class);

        $this->registrationHelper = new RegistrationHelper($this->configurationMock);
    }

    public function testConstructor(): void
    {
        $helper = new RegistrationHelper($this->configurationMock);

        $this->assertInstanceOf(RegistrationHelper::class, $helper);
    }

    public function testInheritanceFromAbstractHelper(): void
    {
        // Assert
        $this->assertInstanceOf(AbstractHelper::class, $this->registrationHelper);
    }

    public function testIsDomainAllowedWithEmptyWhitelist(): void
    {
        $email = 'test@example.com';

        $this->configurationMock->expects($this->once())
            ->method('get')
            ->with('security.domainWhiteListForRegistrations')
            ->willReturn('');

        $result = $this->registrationHelper->isDomainAllowed($email);

        $this->assertTrue($result);
    }

    public function testIsDomainAllowedWithAllowedDomain(): void
    {
        // Arrange
        $email = 'test@example.com';
        $whitelist = 'example.com,allowed.org';

        $this->configurationMock->expects($this->once())
            ->method('get')
            ->with('security.domainWhiteListForRegistrations')
            ->willReturn($whitelist);

        $result = $this->registrationHelper->isDomainAllowed($email);

        $this->assertTrue($result);
    }

    public function testIsDomainAllowedWithNotAllowedDomain(): void
    {
        $email = 'test@notallowed.com';
        $whitelist = 'example.com,allowed.org';

        $this->configurationMock->expects($this->once())
            ->method('get')
            ->with('security.domainWhiteListForRegistrations')
            ->willReturn($whitelist);

        $result = $this->registrationHelper->isDomainAllowed($email);

        $this->assertFalse($result);
    }

    public function testIsDomainAllowedWithMultipleDomainsInWhitelist(): void
    {
        $emails = [
            'user1@example.com' => true,
            'user2@allowed.org' => true,
            'user3@forbidden.net' => false,
            'user4@another.example.com' => false,
        ];

        $whitelist = 'example.com, allowed.org, trusted.edu';

        $this->configurationMock->method('get')
            ->with('security.domainWhiteListForRegistrations')
            ->willReturn($whitelist);

        foreach ($emails as $email => $expected) {
            $result = $this->registrationHelper->isDomainAllowed($email);

            $this->assertEquals($expected, $result, "Failed for email: $email");
        }
    }

    public function testIsDomainAllowedWithWhitespaceInWhitelist(): void
    {
        $email = 'test@spaced.com';
        $whitelist = ' spaced.com , another.com '; // With extra spaces

        $this->configurationMock->expects($this->once())
            ->method('get')
            ->with('security.domainWhiteListForRegistrations')
            ->willReturn($whitelist);

        $result = $this->registrationHelper->isDomainAllowed($email);

        $this->assertTrue($result);
    }

    public function testIsDomainAllowedWithNullWhitelist(): void
    {
        $email = 'test@example.com';

        $this->configurationMock->expects($this->once())
            ->method('get')
            ->with('security.domainWhiteListForRegistrations')
            ->willReturn('');

        $result = $this->registrationHelper->isDomainAllowed($email);

        $this->assertTrue($result);
    }

    public function testIsDomainAllowedWithComplexEmail(): void
    {
        $email = 'user.name+tag@sub.example.com';
        $whitelist = 'sub.example.com';

        $this->configurationMock->expects($this->once())
            ->method('get')
            ->with('security.domainWhiteListForRegistrations')
            ->willReturn($whitelist);

        $result = $this->registrationHelper->isDomainAllowed($email);

        $this->assertTrue($result);
    }

    public function testIsDomainAllowedCaseSensitive(): void
    {
        $email = 'test@Example.COM';
        $whitelist = 'example.com';

        $this->configurationMock->expects($this->once())
            ->method('get')
            ->with('security.domainWhiteListForRegistrations')
            ->willReturn($whitelist);

        $result = $this->registrationHelper->isDomainAllowed($email);

        $this->assertFalse($result);
    }

    public function testAllPublicMethodsExist(): void
    {
        $expectedMethods = [
            '__construct',
            'createUser',
            'isDomainAllowed'
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                method_exists($this->registrationHelper, $methodName),
                "Method $methodName should exist"
            );
        }
    }

    public function testMethodReturnTypes(): void
    {
        $this->configurationMock->method('get')
            ->with('security.domainWhiteListForRegistrations')
            ->willReturn('example.com');

        $result = $this->registrationHelper->isDomainAllowed('test@example.com');
        $this->assertIsBool($result);
    }

    public function testIsDomainAllowedEdgeCases(): void
    {
        $this->configurationMock->method('get')
            ->with('security.domainWhiteListForRegistrations')
            ->willReturn('example.com');

        $result1 = $this->registrationHelper->isDomainAllowed('invalid-email');
        $this->assertIsBool($result1);

        $result2 = $this->registrationHelper->isDomainAllowed('test@@example.com');
        $this->assertIsBool($result2);

        $result3 = $this->registrationHelper->isDomainAllowed('');
        $this->assertIsBool($result3);
    }

    public function testDomainExtractionFromEmail(): void
    {
        $testCases = [
            'simple@domain.com' => 'domain.com',
            'user.name@sub.domain.org' => 'sub.domain.org',
            'test+tag@example.co.uk' => 'example.co.uk',
        ];

        $this->configurationMock->method('get')
            ->with('security.domainWhiteListForRegistrations')
            ->willReturn('domain.com,sub.domain.org,example.co.uk');

        foreach ($testCases as $email => $expectedDomain) {
            $result = $this->registrationHelper->isDomainAllowed($email);
            $this->assertTrue($result, "Email $email should be allowed for domain $expectedDomain");
        }
    }

    public function testCreateUserMethodSignature(): void
    {
        $reflection = new ReflectionClass($this->registrationHelper);
        $method = $reflection->getMethod('createUser');

        $this->assertTrue($method->isPublic());
        $this->assertEquals(4, $method->getNumberOfParameters());

        $parameters = $method->getParameters();
        $this->assertEquals('userName', $parameters[0]->getName());
        $this->assertEquals('fullName', $parameters[1]->getName());
        $this->assertEquals('email', $parameters[2]->getName());
        $this->assertEquals('isVisible', $parameters[3]->getName());

        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    public function testConfigurationDependency(): void
    {
        $reflection = new ReflectionClass($this->registrationHelper);
        $property = $reflection->getProperty('configuration');
        $config = $property->getValue($this->registrationHelper);

        $this->assertSame($this->configurationMock, $config);
    }

    public function testIsDomainAllowedBoundaryConditions(): void
    {
        // Test single character domain
        $this->configurationMock->method('get')
            ->with('security.domainWhiteListForRegistrations')
            ->willReturn('a.b');

        $result = $this->registrationHelper->isDomainAllowed('test@a.b');
        $this->assertTrue($result);

        $result2 = $this->registrationHelper->isDomainAllowed('test@c.d');
        $this->assertFalse($result2);
    }
}
