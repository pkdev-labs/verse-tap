<?php

namespace PkDev\VerseClient;

use PkDev\VerseClient\Clients\EsvClient;
use PkDev\VerseClient\Fetchers\EsvVerseFetcher;
use PkDev\VerseClient\Fetchers\VerseFetcherInterface;

class ClientFactory
{
    public static function make(string $version): VerseFetcherInterface
    {
        return match (strtolower($version)) {
            'esv' => new EsvVerseFetcher(new EsvClient(getenv('ESV_API_TOKEN') ?: '')),
            default => throw new \InvalidArgumentException("Unsupported version: $version")
        };
    }
}
