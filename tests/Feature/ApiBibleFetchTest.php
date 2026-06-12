<?php

namespace PkDev\VerseClient\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use PkDev\VerseClient\Clients\ApiBibleClient;
use PkDev\VerseClient\Exceptions\VerseFetchException;
use PkDev\VerseClient\Fetchers\ApiBibleVerseFetcher;

class ApiBibleFetchTest extends TestCase
{
    /** @var array<int, array{request: Request}> */
    private array $history = [];

    private function fetcher(Response $response): ApiBibleVerseFetcher
    {
        $this->history = [];
        $stack = HandlerStack::create(new MockHandler([$response]));
        $stack->push(Middleware::history($this->history));

        $http = new Client(['handler' => $stack, 'base_uri' => 'https://rest.api.bible/v1/']);

        return new ApiBibleVerseFetcher(new ApiBibleClient('test-key', 'niv-bible-id', $http));
    }

    private function ok(string $content): Response
    {
        return new Response(200, [], (string) json_encode(['data' => ['content' => $content]]));
    }

    public function test_verse_hits_the_passages_endpoint_with_auth_and_content_type(): void
    {
        $fetcher = $this->fetcher($this->ok('For God so loved the world'));

        $text = $fetcher->fetch('John 3:16');

        $this->assertSame('For God so loved the world', $text);

        $request = $this->history[0]['request'];
        $this->assertStringContainsString('/bibles/niv-bible-id/passages/JHN.3.16', $request->getUri()->getPath());
        $this->assertStringContainsString('content-type=text', $request->getUri()->getQuery());
        $this->assertSame('test-key', $request->getHeaderLine('api-key'));
    }

    public function test_chapter_reference_hits_the_chapters_endpoint(): void
    {
        $fetcher = $this->fetcher($this->ok('<p class="p">In the beginning…</p>'));

        $html = $fetcher->fetchHtml('Psalm 23');

        $this->assertStringContainsString('beginning', $html);

        $request = $this->history[0]['request'];
        $this->assertStringContainsString('/bibles/niv-bible-id/chapters/PSA.23', $request->getUri()->getPath());
        $this->assertStringContainsString('content-type=html', $request->getUri()->getQuery());
    }

    public function test_html_defaults_request_verse_numbers_and_titles(): void
    {
        $fetcher = $this->fetcher($this->ok('<span>x</span>'));
        $fetcher->fetchHtml('John 3:16');

        $query = $this->history[0]['request']->getUri()->getQuery();
        $this->assertStringContainsString('include-verse-numbers=true', $query);
        $this->assertStringContainsString('include-titles=true', $query);
    }

    public function test_transport_error_throws(): void
    {
        $fetcher = $this->fetcher(new Response(500, [], 'boom'));

        $this->expectException(VerseFetchException::class);
        $fetcher->fetch('John 3:16');
    }

    public function test_empty_content_throws(): void
    {
        $fetcher = $this->fetcher($this->ok('   '));

        $this->expectException(VerseFetchException::class);
        $fetcher->fetch('John 3:16');
    }
}
