<?php

namespace PkDev\VerseClient\Fetchers;

use PkDev\VerseClient\Clients\ApiBibleClient;

/**
 * Fetches verses and passages from API.Bible (NIV, NRSV, CSB, and other
 * licensed/open Bibles). Speaks both the plain-text and HTML contracts.
 */
class ApiBibleVerseFetcher implements HtmlVerseFetcherInterface, VerseFetcherInterface
{
    public function __construct(private ApiBibleClient $client) {}

    public function fetch(string $reference): string
    {
        return $this->client->content($reference, html: false);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function fetchHtml(string $reference, array $options = []): string
    {
        return $this->client->content($reference, html: true, options: $options);
    }
}
