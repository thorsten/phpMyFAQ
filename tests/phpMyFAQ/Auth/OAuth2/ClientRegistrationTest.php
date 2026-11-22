<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2;

use InvalidArgumentException;
use phpMyFAQ\Enums\OAuth2GrantType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClientRegistration::class)]
final class ClientRegistrationTest extends TestCase
{
    public function testKeepsTheTrimmedValuesOfAValidRegistration(): void
    {
        $registration = new ClientRegistration(
            '  Intranet  ',
            ' https://intranet.example.org/callback ',
            [OAuth2GrantType::AUTHORIZATION_CODE, OAuth2GrantType::CLIENT_CREDENTIALS, OAuth2GrantType::AUTHORIZATION_CODE],
            confidential: true,
        );

        $this->assertSame('Intranet', $registration->name);
        $this->assertSame('https://intranet.example.org/callback', $registration->redirectUri);
        $this->assertSame(['authorization_code', 'client_credentials'], $registration->grantValues());
        $this->assertTrue($registration->confidential);
    }

    public function testAClientCredentialsClientNeedsNoRedirectUri(): void
    {
        $registration = new ClientRegistration('Cron', '', [OAuth2GrantType::CLIENT_CREDENTIALS], confidential: true);

        $this->assertSame('', $registration->redirectUri);
    }

    public function testANativeApplicationMayRegisterASchemeOfItsOwn(): void
    {
        $registration = new ClientRegistration(
            'Mobile app',
            'org.example.app://callback',
            [OAuth2GrantType::AUTHORIZATION_CODE],
            confidential: false,
        );

        $this->assertSame('org.example.app://callback', $registration->redirectUri);
    }

    /**
     * @param list<OAuth2GrantType> $grants
     */
    #[DataProvider('invalidRegistrations')]
    public function testRejectsARegistrationTheAuthorizationServerCannotWorkWith(
        string $name,
        string $redirectUri,
        array $grants,
        bool $confidential,
        string $translationKey,
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($translationKey);

        new ClientRegistration($name, $redirectUri, $grants, $confidential);
    }

    /**
     * @return array<string, array{string, string, list<OAuth2GrantType>, bool, string}>
     */
    public static function invalidRegistrations(): array
    {
        $authorizationCode = [OAuth2GrantType::AUTHORIZATION_CODE];
        $callback = 'https://intranet.example.org/callback';

        return [
            'empty name' => ['   ', $callback, $authorizationCode, true, 'msgOAuth2ClientNameInvalid'],
            'name too long' => [str_repeat('a', 256), $callback, $authorizationCode, true, 'msgOAuth2ClientNameInvalid'],
            'no grant' => ['Intranet', $callback, [], true, 'msgOAuth2ClientGrantRequired'],
            'client credentials without a secret' => [
                'Intranet',
                '',
                [OAuth2GrantType::CLIENT_CREDENTIALS],
                false,
                'msgOAuth2ClientCredentialsNeedSecret',
            ],
            'authorization code without redirect URI' => [
                'Intranet',
                '',
                $authorizationCode,
                true,
                'msgOAuth2ClientRedirectUriRequired',
            ],
            'relative redirect URI' => [
                'Intranet',
                '/callback',
                $authorizationCode,
                true,
                'msgOAuth2ClientRedirectUriInvalid',
            ],
            'script redirect URI' => [
                'Intranet',
                'javascript://example.org/%0Aalert(1)',
                $authorizationCode,
                true,
                'msgOAuth2ClientRedirectUriInvalid',
            ],
        ];
    }
}
