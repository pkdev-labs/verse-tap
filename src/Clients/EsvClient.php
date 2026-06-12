<?php

namespace PkDev\VerseClient\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PkDev\VerseClient\Exceptions\VerseFetchException;

class EsvClient
{
    private Client $http;
    private string $token;

    public function __construct(string $token, ?Client $http = null)
    {
        $this->token = $token;
        $this->http  = $http ?? new Client([
            'base_uri' => 'https://api.esv.org/v3/',
            'timeout'  => 10.0,
        ]);
    }

    public function verse(string $reference): string
    {
        try {
            $response = $this->http->get('passage/text', [
                'query' => [
                    'q'                          => $reference,
                    'include-headings'          => 'false',
                    'include-footnotes'         => 'false',
                    'include-verse-numbers'     => 'false',
                    'include-short-copyright'   => 'false',
                    'include-passage-references'=> 'false',
                    'indent-poetry'             => 'false',
                ],
                'headers' => [
                    'Authorization' => "Token {$this->token}",
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            return trim($data['passages'][0] ?? 'Verse not found');
        } catch (GuzzleException $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    /**
     * Display-ready HTML for a reference — verse numbers and headings preserved
     * for a reader UI. Unlike {@see verse()}, this throws on failure.
     *
     * @param array<string, mixed> $options verseNumbers/headings/footnotes booleans
     *
     * @throws VerseFetchException
     */
    public function verseHtml(string $reference, array $options = []): string
    {
        $bool = static fn (mixed $value, bool $default): string => ($value ?? $default) ? 'true' : 'false';

        try {
            $response = $this->http->get('passage/html', [
                'query' => [
                    'q'                           => $reference,
                    'include-verse-numbers'       => $bool($options['verseNumbers'] ?? null, true),
                    'include-headings'            => $bool($options['headings'] ?? null, true),
                    'include-footnotes'           => $bool($options['footnotes'] ?? null, false),
                    'include-passage-references'  => 'false',
                    'include-audio-link'          => 'false',
                    'include-book-titles'         => 'false',
                    'include-chapter-numbers'     => 'false',
                ],
                'headers' => [
                    'Authorization' => "Token {$this->token}",
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new VerseFetchException("ESV request failed for \"{$reference}\": {$e->getMessage()}", previous: $e);
        }

        $data = json_decode((string) $response->getBody(), true);
        $html = is_array($data) ? ($data['passages'][0] ?? null) : null;

        if (!is_string($html) || trim($html) === '') {
            throw new VerseFetchException("ESV returned no content for \"{$reference}\".");
        }

        return trim($html);
    }
}
