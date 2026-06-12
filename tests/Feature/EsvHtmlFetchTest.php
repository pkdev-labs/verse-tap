<?php

namespace PkDev\VerseClient\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use PkDev\VerseClient\Clients\EsvClient;
use PkDev\VerseClient\Exceptions\VerseFetchException;
use PkDev\VerseClient\Fetchers\EsvVerseFetcher;

class EsvHtmlFetchTest extends TestCase
{
    /** @var array<int, array{request: \GuzzleHttp\Psr7\Request}> */
    private array $history = [];

    private function fetcher(Response $response): EsvVerseFetcher
    {
        $this->history = [];
        $stack = HandlerStack::create(new MockHandler([$response]));
        $stack->push(Middleware::history($this->history));

        $http = new Client(['handler' => $stack, 'base_uri' => 'https://api.esv.org/v3/']);

        return new EsvVerseFetcher(new EsvClient('test-token', $http));
    }

    public function test_fetch_html_returns_passage_markup_and_hits_html_endpoint(): void
    {
        $fetcher = $this->fetcher(new Response(200, [], (string) json_encode([
            'passages' => ['<h3>Heading</h3><p><b class="verse-num">16 </b>For God so loved…</p>'],
        ])));

        $html = $fetcher->fetchHtml('John 3:16');

        $this->assertStringContainsString('verse-num', $html);

        $request = $this->history[0]['request'];
        $this->assertStringContainsString('passage/html', $request->getUri()->getPath());
        $this->assertSame('Token test-token', $request->getHeaderLine('Authorization'));
    }

    public function test_fetch_html_throws_on_empty_passage(): void
    {
        $fetcher = $this->fetcher(new Response(200, [], (string) json_encode(['passages' => []])));

        $this->expectException(VerseFetchException::class);
        $fetcher->fetchHtml('Nowhere 9:99');
    }

    public function test_fetch_html_throws_on_transport_error(): void
    {
        $fetcher = $this->fetcher(new Response(401, [], 'unauthorized'));

        $this->expectException(VerseFetchException::class);
        $fetcher->fetchHtml('John 3:16');
    }
}
