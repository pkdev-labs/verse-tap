<?php

namespace PkDev\VerseClient;

use InvalidArgumentException;
use PkDev\VerseClient\Clients\ApiBibleClient;
use PkDev\VerseClient\Clients\EsvClient;
use PkDev\VerseClient\Fetchers\ApiBibleVerseFetcher;
use PkDev\VerseClient\Fetchers\EsvVerseFetcher;
use PkDev\VerseClient\Fetchers\VerseFetcherInterface;

class ClientFactory
{
    /**
     * Build a fetcher for a version. 'esv' uses the ESV API (token from config
     * or the ESV_API_TOKEN env var); any other version is served via API.Bible
     * and requires 'api_key' and 'bible_id' in $config.
     *
     * @param  array{token?: string, api_key?: string, bible_id?: string}  $config
     */
    public static function make(string $version, array $config = []): VerseFetcherInterface
    {
        $version = strtolower($version);

        if ($version === 'esv') {
            $token = $config['token'] ?? (getenv('ESV_API_TOKEN') ?: '');

            return new EsvVerseFetcher(new EsvClient((string) $token));
        }

        $apiKey = $config['api_key'] ?? null;
        $bibleId = $config['bible_id'] ?? null;

        if ($apiKey === null || $bibleId === null) {
            throw new InvalidArgumentException("Version '{$version}' requires 'api_key' and 'bible_id' config.");
        }

        return new ApiBibleVerseFetcher(new ApiBibleClient((string) $apiKey, (string) $bibleId));
    }
}
