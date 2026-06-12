<?php

namespace PkDev\VerseClient\Fetchers;

use PkDev\VerseClient\Clients;

class EsvVerseFetcher implements HtmlVerseFetcherInterface, VerseFetcherInterface
{
    public function __construct(private Clients\EsvClient $client) {}

    public function fetch(string $reference): string
    {
        return $this->client->verse($reference);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function fetchHtml(string $reference, array $options = []): string
    {
        return $this->client->verseHtml($reference, $options);
    }
}
