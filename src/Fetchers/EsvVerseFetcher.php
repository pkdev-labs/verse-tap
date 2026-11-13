<?php

namespace PkDev\VerseClient\Fetchers;

use PkDev\VerseClient\Clients\EsvClient;

class EsvVerseFetcher implements HtmlVerseFetcherInterface, VerseFetcherInterface
{
    public function __construct(private readonly EsvClient $client) {}

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
