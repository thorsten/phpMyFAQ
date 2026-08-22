<?php

namespace phpMyFAQ\Export\Pdf;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Records every requested URL and returns a canned [statusCode, headers, body]
 * response per URL, so redirect-following can be exercised without real network I/O.
 */
final class ExternalImageFetcherFakeRequester implements HttpRequesterInterface
{
    /** @var array<string, array{0: int, 1: string[], 2: false|string}> */
    private array $responses = [];

    /** @var string[] */
    public array $requestedUrls = [];

    /**
     * @param string[] $headers
     */
    public function withResponse(string $url, int $statusCode, array $headers, false|string $body): self
    {
        $this->responses[$url] = [$statusCode, $headers, $body];

        return $this;
    }

    public function request(string $url): array
    {
        $this->requestedUrls[] = $url;

        return $this->responses[$url] ?? [0, [], false];
    }
}

final class ExternalImageFetcherTest extends TestCase
{
    public function testFetchReturnsBodyForDirectlyAllowedHost(): void
    {
        $requester = (new ExternalImageFetcherFakeRequester())
            ->withResponse('https://allowed.test/image.png', 200, ['HTTP/1.1 200 OK'], 'imagebytes');

        $result = new ExternalImageFetcher($requester)->fetch('https://allowed.test/image.png', ['allowed.test']);

        self::assertSame('imagebytes', $result);
        self::assertSame(['https://allowed.test/image.png'], $requester->requestedUrls);
    }

    public function testFetchRejectsHostNotInAllowlistWithoutRequesting(): void
    {
        $requester = new ExternalImageFetcherFakeRequester();

        $result = new ExternalImageFetcher($requester)->fetch('https://blocked.test/image.png', ['allowed.test']);

        self::assertFalse($result);
        self::assertSame([], $requester->requestedUrls);
    }

    public function testFetchRejectsNonHttpSchemes(): void
    {
        $requester = new ExternalImageFetcherFakeRequester();
        $fetcher = new ExternalImageFetcher($requester);

        self::assertFalse($fetcher->fetch('ftp://allowed.test/image.png', ['allowed.test']));
        self::assertFalse($fetcher->fetch('file:///etc/passwd', ['allowed.test']));
        self::assertSame([], $requester->requestedUrls);
    }

    public function testFetchFollowsRedirectToAllowedHost(): void
    {
        $requester = (new ExternalImageFetcherFakeRequester())
            ->withResponse(
                'https://allowed.test/redirect',
                302,
                ['HTTP/1.1 302 Found', 'Location: https://allowed.test/image.png'],
                '',
            )
            ->withResponse('https://allowed.test/image.png', 200, ['HTTP/1.1 200 OK'], 'imagebytes');

        $result = new ExternalImageFetcher($requester)->fetch('https://allowed.test/redirect', ['allowed.test']);

        self::assertSame('imagebytes', $result);
        self::assertSame(
            ['https://allowed.test/redirect', 'https://allowed.test/image.png'],
            $requester->requestedUrls,
        );
    }

    /**
     * The core SSRF fix: a redirect from an allowed host to a disallowed host must not
     * be followed, and the disallowed host must never actually be requested.
     */
    public function testFetchRejectsRedirectToDisallowedHost(): void
    {
        $requester = (new ExternalImageFetcherFakeRequester())
            ->withResponse(
                'https://allowed.test/redirect',
                302,
                ['HTTP/1.1 302 Found', 'Location: https://blocked.test/image.png'],
                '',
            )
            ->withResponse('https://blocked.test/image.png', 200, ['HTTP/1.1 200 OK'], 'imagebytes');

        $result = new ExternalImageFetcher($requester)->fetch('https://allowed.test/redirect', ['allowed.test']);

        self::assertFalse($result);
        self::assertSame(['https://allowed.test/redirect'], $requester->requestedUrls);
    }

    public function testFetchRejectsRedirectToDisallowedHostViaProtocolRelativeLocation(): void
    {
        $requester = (new ExternalImageFetcherFakeRequester())
            ->withResponse(
                'https://allowed.test/redirect',
                302,
                ['HTTP/1.1 302 Found', 'Location: //blocked.test/image.png'],
                '',
            );

        $result = new ExternalImageFetcher($requester)->fetch('https://allowed.test/redirect', ['allowed.test']);

        self::assertFalse($result);
        self::assertSame(['https://allowed.test/redirect'], $requester->requestedUrls);
    }

    public function testFetchFollowsExactlyThreeRedirectsSuccessfully(): void
    {
        $requester = new ExternalImageFetcherFakeRequester();
        $requester
            ->withResponse('https://allowed.test/1', 302, ['HTTP/1.1 302 Found', 'Location: https://allowed.test/2'], '')
            ->withResponse('https://allowed.test/2', 302, ['HTTP/1.1 302 Found', 'Location: https://allowed.test/3'], '')
            ->withResponse('https://allowed.test/3', 302, ['HTTP/1.1 302 Found', 'Location: https://allowed.test/4'], '')
            ->withResponse('https://allowed.test/4', 200, ['HTTP/1.1 200 OK'], 'imagebytes');

        $result = new ExternalImageFetcher($requester)->fetch('https://allowed.test/1', ['allowed.test']);

        self::assertSame('imagebytes', $result);
    }

    public function testFetchStopsAfterExceedingTheRedirectCap(): void
    {
        $requester = new ExternalImageFetcherFakeRequester();
        $requester
            ->withResponse('https://allowed.test/1', 302, ['HTTP/1.1 302 Found', 'Location: https://allowed.test/2'], '')
            ->withResponse('https://allowed.test/2', 302, ['HTTP/1.1 302 Found', 'Location: https://allowed.test/3'], '')
            ->withResponse('https://allowed.test/3', 302, ['HTTP/1.1 302 Found', 'Location: https://allowed.test/4'], '')
            ->withResponse('https://allowed.test/4', 302, ['HTTP/1.1 302 Found', 'Location: https://allowed.test/5'], '');

        $result = new ExternalImageFetcher($requester)->fetch('https://allowed.test/1', ['allowed.test']);

        self::assertFalse($result);
        self::assertSame(
            [
                'https://allowed.test/1',
                'https://allowed.test/2',
                'https://allowed.test/3',
                'https://allowed.test/4',
            ],
            $requester->requestedUrls,
        );
    }

    public function testFetchFailsOnRedirectWithoutLocationHeader(): void
    {
        $requester = (new ExternalImageFetcherFakeRequester())
            ->withResponse('https://allowed.test/redirect', 302, ['HTTP/1.1 302 Found'], '');

        self::assertFalse(new ExternalImageFetcher($requester)->fetch('https://allowed.test/redirect', ['allowed.test']));
    }

    public function testFetchResolvesRootRelativeRedirect(): void
    {
        $requester = (new ExternalImageFetcherFakeRequester())
            ->withResponse('https://allowed.test/path/redirect', 302, ['HTTP/1.1 302 Found', 'Location: /image.png'], '')
            ->withResponse('https://allowed.test/image.png', 200, ['HTTP/1.1 200 OK'], 'imagebytes');

        $result = new ExternalImageFetcher($requester)->fetch('https://allowed.test/path/redirect', ['allowed.test']);

        self::assertSame('imagebytes', $result);
    }

    public function testFetchResolvesProtocolRelativeRedirect(): void
    {
        $requester = (new ExternalImageFetcherFakeRequester())
            ->withResponse(
                'https://allowed.test/redirect',
                302,
                ['HTTP/1.1 302 Found', 'Location: //allowed.test/image.png'],
                '',
            )
            ->withResponse('https://allowed.test/image.png', 200, ['HTTP/1.1 200 OK'], 'imagebytes');

        $result = new ExternalImageFetcher($requester)->fetch('https://allowed.test/redirect', ['allowed.test']);

        self::assertSame('imagebytes', $result);
    }

    public function testFetchResolvesPathRelativeRedirect(): void
    {
        $requester = (new ExternalImageFetcherFakeRequester())
            ->withResponse('https://allowed.test/dir/redirect', 302, ['HTTP/1.1 302 Found', 'Location: image.png'], '')
            ->withResponse('https://allowed.test/dir/image.png', 200, ['HTTP/1.1 200 OK'], 'imagebytes');

        $result = new ExternalImageFetcher($requester)->fetch('https://allowed.test/dir/redirect', ['allowed.test']);

        self::assertSame('imagebytes', $result);
    }

    public function testFetchFailsWhenRedirectLocationIsEmpty(): void
    {
        $requester = (new ExternalImageFetcherFakeRequester())
            ->withResponse('https://allowed.test/redirect', 302, ['HTTP/1.1 302 Found', 'Location: '], '');

        self::assertFalse(new ExternalImageFetcher($requester)->fetch('https://allowed.test/redirect', ['allowed.test']));
    }

    public function testFetchFailsOnNonSuccessStatusCode(): void
    {
        $requester = (new ExternalImageFetcherFakeRequester())
            ->withResponse('https://allowed.test/missing.png', 404, ['HTTP/1.1 404 Not Found'], 'not found');

        self::assertFalse(new ExternalImageFetcher($requester)->fetch('https://allowed.test/missing.png', ['allowed.test']));
    }

    public function testFetchFailsOnEmptyBody(): void
    {
        $requester = (new ExternalImageFetcherFakeRequester())
            ->withResponse('https://allowed.test/empty.png', 200, ['HTTP/1.1 200 OK'], '');

        self::assertFalse(new ExternalImageFetcher($requester)->fetch('https://allowed.test/empty.png', ['allowed.test']));
    }

    public function testFetchFailsWhenRequestReturnsFalseBody(): void
    {
        $requester = (new ExternalImageFetcherFakeRequester())
            ->withResponse('https://allowed.test/broken.png', 200, ['HTTP/1.1 200 OK'], false);

        self::assertFalse(new ExternalImageFetcher($requester)->fetch('https://allowed.test/broken.png', ['allowed.test']));
    }

    public function testFetchMatchesSubdomainOfAllowedHost(): void
    {
        $requester = (new ExternalImageFetcherFakeRequester())
            ->withResponse('https://images.allowed.test/photo.jpg', 200, ['HTTP/1.1 200 OK'], 'imagebytes');

        $result = new ExternalImageFetcher($requester)->fetch('https://images.allowed.test/photo.jpg', ['allowed.test']);

        self::assertSame('imagebytes', $result);
    }

    public function testFetchDoesNotMatchHostThatMerelyEndsWithAllowedHost(): void
    {
        // "notallowed.test" must not match an allowlist entry of "allowed.test" just
        // because the raw string happens to end with it (no subdomain dot boundary).
        $requester = new ExternalImageFetcherFakeRequester();

        $result = new ExternalImageFetcher($requester)->fetch('https://notallowed.test/photo.jpg', ['allowed.test']);

        self::assertFalse($result);
        self::assertSame([], $requester->requestedUrls);
    }

    public function testFetchIgnoresEmptyAndZeroAllowlistEntries(): void
    {
        $requester = (new ExternalImageFetcherFakeRequester())
            ->withResponse('https://allowed.test/photo.jpg', 200, ['HTTP/1.1 200 OK'], 'imagebytes');

        $result = new ExternalImageFetcher($requester)->fetch(
            'https://allowed.test/photo.jpg',
            ['', '0', 'allowed.test'],
        );

        self::assertSame('imagebytes', $result);
    }

    public function testConstructorDefaultsToStreamHttpRequesterWithoutMakingANetworkCall(): void
    {
        // The disallowed host is rejected before the requester is ever invoked, so this
        // exercises the default-constructor branch without touching the network.
        self::assertFalse(new ExternalImageFetcher()->fetch('https://blocked.test/photo.jpg', ['allowed.test']));
    }

    public function testResolveRedirectLocationRejectsAMalformedCurrentUrl(): void
    {
        // Defensive branch: fetch() never reaches resolveRedirectLocation() with a
        // current URL that lacks a scheme/host, since isFetchableUrl() already
        // requires both — exercised directly via reflection for completeness.
        $fetcher = new ExternalImageFetcher(new ExternalImageFetcherFakeRequester());
        $method = new ReflectionMethod($fetcher, 'resolveRedirectLocation');

        self::assertNull($method->invoke($fetcher, 'not-a-url', '/image.png'));
    }
}
