<?php

namespace PkDev\VerseClient\Tests\Feature;

use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\TestCase;
use PkDev\VerseClient\ClientFactory;
use PkDev\VerseClient\Clients\EsvClient;
use PkDev\VerseClient\Fetchers\EsvVerseFetcher;
use Psr\Http\Message\RequestInterface;

class VerseFetchTest extends TestCase
{
    public function test_it_can_fetch_a_verse(): void
    {
        $client = ClientFactory::make('esv');
        $result = $client->fetch('John 3:16');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('God', $result);
    }
}