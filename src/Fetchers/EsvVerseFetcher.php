<?php

namespace PkDev\VerseClient\Fetchers;

use PkDev\VerseClient\Clients;

class EsvVerseFetcher implements VerseFetcherInterface
{
    public function __construct(private Clients\EsvClient $client) {}

    public function fetch(string $reference): string
    {
        return $this->client->verse($reference);
    }
}
