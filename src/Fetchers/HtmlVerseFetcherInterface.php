<?php

namespace PkDev\VerseClient\Fetchers;

use PkDev\VerseClient\Exceptions\VerseFetchException;

/**
 * A fetcher that can return display-ready HTML for a reference — verse numbers
 * and section headings preserved — suitable for a reader UI. Distinct from the
 * plain-text {@see VerseFetcherInterface} so existing consumers are unaffected.
 */
interface HtmlVerseFetcherInterface
{
    /**
     * Return formatted HTML for a reference ("John 3", "John 3:16", "1 John 2:1-5").
     *
     * @param  array<string, mixed>  $options  Provider-agnostic display hints,
     *                                         e.g. ['verseNumbers' => true, 'headings' => true, 'footnotes' => false].
     *
     * @throws VerseFetchException
     */
    public function fetchHtml(string $reference, array $options = []): string;
}
