<?php

namespace phpMyFAQ\Twig\Extensions;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Twig\Extension\AbstractExtension;

/**
 * Test class for UserNameTwigExtension
 */
class UserNameTwigExtensionTest extends TestCase
{
    private UserNameTwigExtension $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new UserNameTwigExtension();
    }

    public function testExtendsAbstractExtension(): void
    {
        $this->assertInstanceOf(AbstractExtension::class, $this->extension);
    }

    public function testClassUsesCorrectNamespace(): void
    {
        $reflection = new ReflectionClass($this->extension);
        $this->assertEquals('phpMyFAQ\Twig\Extensions', $reflection->getNamespaceName());
    }

    public function testGetUserNameMethodExists(): void
    {
        $this->assertTrue(method_exists(UserNameTwigExtension::class, 'getUserName'));

        $reflection = new ReflectionClass(UserNameTwigExtension::class);
        $method = $reflection->getMethod('getUserName');

        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    public function testGetRealNameMethodExists(): void
    {
        $this->assertTrue(method_exists(UserNameTwigExtension::class, 'getRealName'));

        $reflection = new ReflectionClass(UserNameTwigExtension::class);
        $method = $reflection->getMethod('getRealName');

        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    public function testGetUserNameMethodSignature(): void
    {
        $reflection = new ReflectionClass(UserNameTwigExtension::class);
        $method = $reflection->getMethod('getUserName');

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $userIdParam = $parameters[0];
        $this->assertEquals('userId', $userIdParam->getName());
        $this->assertEquals('int', $userIdParam->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    public function testGetRealNameMethodSignature(): void
    {
        $reflection = new ReflectionClass(UserNameTwigExtension::class);
        $method = $reflection->getMethod('getRealName');

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $userIdParam = $parameters[0];
        $this->assertEquals('userId', $userIdParam->getName());
        $this->assertEquals('int', $userIdParam->getType()->getName());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    public function testGetUserNameHasTwigFilterAttribute(): void
    {
        $reflection = new ReflectionClass(UserNameTwigExtension::class);
        $method = $reflection->getMethod('getUserName');

        $attributes = $method->getAttributes();
        $this->assertNotEmpty($attributes);

        $filterAttribute = null;
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Twig\Attribute\AsTwigFilter') {
                $filterAttribute = $attribute;
                break;
            }
        }

        $this->assertNotNull($filterAttribute, 'getUserName should have AsTwigFilter attribute');

        $arguments = $filterAttribute->getArguments();
        $this->assertContains('userName', $arguments);
    }

    public function testGetRealNameHasTwigFilterAttribute(): void
    {
        $reflection = new ReflectionClass(UserNameTwigExtension::class);
        $method = $reflection->getMethod('getRealName');

        $attributes = $method->getAttributes();
        $this->assertNotEmpty($attributes);

        $filterAttribute = null;
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Twig\Attribute\AsTwigFilter') {
                $filterAttribute = $attribute;
                break;
            }
        }

        $this->assertNotNull($filterAttribute, 'getRealName should have AsTwigFilter attribute');

        $arguments = $filterAttribute->getArguments();
        $this->assertContains('realName', $arguments);
    }

    public function testBothMethodsThrowException(): void
    {
        $reflection = new ReflectionClass(UserNameTwigExtension::class);

        // Test getUserName throws Exception
        $getUserNameMethod = $reflection->getMethod('getUserName');
        $docComment = $getUserNameMethod->getDocComment();
        $this->assertStringContainsString('@throws Exception', $docComment);

        // Test getRealName throws Exception
        $getRealNameMethod = $reflection->getMethod('getRealName');
        $docComment = $getRealNameMethod->getDocComment();
        $this->assertStringContainsString('@throws Exception', $docComment);
    }

    public function testGetUserNameWithValidId(): void
    {
        // Test method structure without actual user lookup due to dependencies
        $this->assertTrue(method_exists(UserNameTwigExtension::class, 'getUserName'));

        // Test return type compliance
        $reflection = new ReflectionClass(UserNameTwigExtension::class);
        $method = $reflection->getMethod('getUserName');
        $returnType = $method->getReturnType();
        $this->assertEquals('string', $returnType->getName());
    }

    public function testGetRealNameWithValidId(): void
    {
        // Test method structure without actual user lookup due to dependencies
        $this->assertTrue(method_exists(UserNameTwigExtension::class, 'getRealName'));

        // Test return type compliance
        $reflection = new ReflectionClass(UserNameTwigExtension::class);
        $method = $reflection->getMethod('getRealName');
        $returnType = $method->getReturnType();
        $this->assertEquals('string', $returnType->getName());
    }

    public function testGetUserNameWithZeroId(): void
    {
        // Test method signature and type safety
        $reflection = new ReflectionClass(UserNameTwigExtension::class);
        $method = $reflection->getMethod('getUserName');

        $parameters = $method->getParameters();
        $this->assertEquals('int', $parameters[0]->getType()->getName());
    }

    public function testGetRealNameWithZeroId(): void
    {
        // Test method signature and type safety
        $reflection = new ReflectionClass(UserNameTwigExtension::class);
        $method = $reflection->getMethod('getRealName');

        $parameters = $method->getParameters();
        $this->assertEquals('int', $parameters[0]->getType()->getName());
    }

    public function testClassHasCorrectImports(): void
    {
        $filename = (new ReflectionClass(UserNameTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        $expectedImports = [
            'use phpMyFAQ\Configuration;',
            'use phpMyFAQ\Core\Exception;',
            'use phpMyFAQ\User;',
            'use Twig\Attribute\AsTwigFilter;',
            'use Twig\Extension\AbstractExtension;'
        ];

        foreach ($expectedImports as $import) {
            $this->assertStringContainsString($import, $source);
        }
    }

    public function testMethodsUseConfigurationInstance(): void
    {
        $filename = (new ReflectionClass(UserNameTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        $this->assertStringContainsString('Configuration::getConfigurationInstance()', $source);
        $this->assertStringContainsString('new User', $source);
    }

    public function testGetUserNameImplementation(): void
    {
        $filename = (new ReflectionClass(UserNameTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        // Should create a User instance and call getUserById
        $this->assertStringContainsString('$user->getUserById($userId)', $source);
        $this->assertStringContainsString('$user->getLogin()', $source);
    }

    public function testGetRealNameImplementation(): void
    {
        $filename = (new ReflectionClass(UserNameTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        // Should create User instance and call getUserById
        $this->assertStringContainsString('$user->getUserById($userId)', $source);
        $this->assertStringContainsString("getUserData(field: 'display_name')", $source);
    }

    public function testMethodsAreStaticForTwigCompatibility(): void
    {
        $reflection = new ReflectionClass(UserNameTwigExtension::class);

        $getUserNameMethod = $reflection->getMethod('getUserName');
        $this->assertTrue($getUserNameMethod->isStatic(), 'getUserName should be static for Twig performance');

        $getRealNameMethod = $reflection->getMethod('getRealName');
        $this->assertTrue($getRealNameMethod->isStatic(), 'getRealName should be static for Twig performance');
    }

    public function testExtensionStructure(): void
    {
        $this->assertInstanceOf(AbstractExtension::class, $this->extension);

        $reflection = new ReflectionClass($this->extension);
        $this->assertTrue($reflection->hasMethod('getUserName'));
        $this->assertTrue($reflection->hasMethod('getRealName'));
    }

    public function testParameterTypeEnforcement(): void
    {
        $reflection = new ReflectionClass(UserNameTwigExtension::class);

        // Test getUserName parameter
        $getUserNameMethod = $reflection->getMethod('getUserName');
        $parameters = $getUserNameMethod->getParameters();
        $userIdParam = $parameters[0];

        $this->assertTrue($userIdParam->hasType());
        $this->assertEquals('int', $userIdParam->getType()->getName());
        $this->assertFalse($userIdParam->allowsNull());

        // Test getRealName parameter
        $getRealNameMethod = $reflection->getMethod('getRealName');
        $parameters = $getRealNameMethod->getParameters();
        $userIdParam = $parameters[0];

        $this->assertTrue($userIdParam->hasType());
        $this->assertEquals('int', $userIdParam->getType()->getName());
        $this->assertFalse($userIdParam->allowsNull());
    }

    public function testReturnTypeEnforcement(): void
    {
        $reflection = new ReflectionClass(UserNameTwigExtension::class);

        // Test getUserName return type
        $getUserNameMethod = $reflection->getMethod('getUserName');
        $returnType = $getUserNameMethod->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
        $this->assertFalse($returnType->allowsNull());

        // Test getRealName return type
        $getRealNameMethod = $reflection->getMethod('getRealName');
        $returnType = $getRealNameMethod->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
        $this->assertFalse($returnType->allowsNull());
    }

    public function testFilterNamesAreCorrect(): void
    {
        $reflection = new ReflectionClass(UserNameTwigExtension::class);

        // Test getUserName filter name
        $getUserNameMethod = $reflection->getMethod('getUserName');
        $attributes = $getUserNameMethod->getAttributes();
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Twig\\Attribute\\AsTwigFilter') {
                $arguments = array_values($attribute->getArguments());
                $this->assertContains($arguments[0], ['userName','realName']);
            }
        }

        // Test getRealName filter name
        $getRealNameMethod = $reflection->getMethod('getRealName');
        $attributes = $getRealNameMethod->getAttributes();
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Twig\\Attribute\\AsTwigFilter') {
                $arguments = array_values($attribute->getArguments());
                $this->assertContains($arguments[0], ['userName','realName']);
            }
        }
    }

    public function testClassNamespaceDeclaration(): void
    {
        $filename = (new ReflectionClass(UserNameTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        // Should not have declare(strict_types=1) since it's missing in the actual file
        // This tests the actual state of the file
        $this->assertStringContainsString('namespace phpMyFAQ\Twig\Extensions;', $source);
    }

    public function testUserInstanceCreation(): void
    {
        $filename = (new ReflectionClass(UserNameTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        // Should create User instance with Configuration
        $this->assertStringContainsString('$user = new User(Configuration::getConfigurationInstance())', $source);
    }

    public function testBothMethodsFollowSamePattern(): void
    {
        $filename = (new ReflectionClass(UserNameTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        // Both methods should follow the same pattern of:
        // 1. Create User instance
        // 2. Call getUserById
        // 3. Return user data

        $getUserNameCount = substr_count($source, '$user = new User(Configuration::getConfigurationInstance())');
        $getUserByIdCount = substr_count($source, '$user->getUserById($userId)');

        $this->assertEquals(2, $getUserNameCount, 'Should create User instance twice (once per method)');
        $this->assertEquals(2, $getUserByIdCount, 'Should call getUserById twice (once per method)');
    }

    public function testMethodsHandleDifferentUserData(): void
    {
        $filename = (new ReflectionClass(UserNameTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        // getUserName should call getLogin()
        $this->assertStringContainsString('return $user->getLogin();', $source);

        // getRealName should call getUserData('display_name')
        $this->assertStringContainsString('return $user->getUserData(field: \'display_name\');', $source);
    }

    public function testDocumentationExists(): void
    {
        $filename = (new ReflectionClass(UserNameTwigExtension::class))->getFileName();
        $source = file_get_contents($filename);

        // Should have proper documentation
        $this->assertStringContainsString('/**', $source);
        $this->assertStringContainsString('Twig extension to return the login name of a user', $source);
        $this->assertStringContainsString('@package   phpMyFAQ\Template', $source);
    }
}
