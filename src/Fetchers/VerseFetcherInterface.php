<?php

namespace PkDev\VerseClient\Fetchers;

interface VerseFetcherInterface
{
    public function fetch(string $reference): string;
}
