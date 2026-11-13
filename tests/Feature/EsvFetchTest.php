<?php

namespace PkDev\VerseClient\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use PkDev\VerseClient\Clients\EsvClient;
use PkDev\VerseClient\Fetchers\EsvVerseFetcher;

class EsvFetchTest extends TestCase
{
    /** @var array<int, array{request: Request}> */
    private array $history = [];

    private function fetcher(Response $response): EsvVerseFetcher
    {
        $this->history = [];
        $stack = HandlerStack::create(new MockHandler([$response]));
        $stack->push(Middleware::history($this->history)); // @phpstan-ignore assign.propertyType (Guzzle types the by-reference container as array|ArrayAccess)

        $http = new Client(['handler' => $stack, 'base_uri' => 'https://api.esv.org/v3/']);

        return new EsvVerseFetcher(new EsvClient('test-token', $http));
    }

    public function test_fetch_returns_the_trimmed_passage_from_the_text_endpoint(): void
    {
        $fetcher = $this->fetcher(new Response(200, [], (string) json_encode([
            'passages' => ["  For God so loved the world  \n"],
        ])));

        $this->assertSame('For God so loved the world', $fetcher->fetch('John 3:16'));

        $request = $this->history[0]['request'];
        $this->assertStringContainsString('passage/text', $request->getUri()->getPath());
        $this->assertStringContainsString('q=John%203%3A16', $request->getUri()->getQuery());
        $this->assertSame('Token test-token', $request->getHeaderLine('Authorization'));
    }

    public function test_fetch_returns_verse_not_found_when_there_is_no_passage(): void
    {
        $fetcher = $this->fetcher(new Response(200, [], (string) json_encode(['passages' => []])));

        $this->assertSame('Verse not found', $fetcher->fetch('Nowhere 9:99'));
    }

    public function test_fetch_returns_an_error_string_on_a_failed_request(): void
    {
        $fetcher = $this->fetcher(new Response(401, [], 'unauthorized'));

        $this->assertStringStartsWith('Error:', $fetcher->fetch('John 3:16'));
    }
}
