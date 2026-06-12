<?php

namespace PkDev\VerseClient\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PkDev\VerseClient\Exceptions\VerseFetchException;
use PkDev\VerseClient\Support\BibleReference;

/**
 * Client for API.Bible (https://docs.api.bible). One instance is bound to a
 * single Bible (e.g. an NIV/NRSV/CSB id) and resolves human references to the
 * chapters or passages endpoint as appropriate.
 */
class ApiBibleClient
{
    private Client $http;

    public function __construct(
        private string $apiKey,
        private string $bibleId,
        ?Client $http = null,
    ) {
        $this->http = $http ?? new Client([
            'base_uri' => 'https://rest.api.bible/v1/',
            'timeout' => 10.0,
        ]);
    }

    /**
     * Fetch a reference as text or HTML. Whole-chapter references hit the
     * chapters endpoint; verses and ranges hit passages.
     *
     * @param  array<string, mixed>  $options  verseNumbers/headings/footnotes booleans
     *
     * @throws VerseFetchException
     */
    public function content(string $reference, bool $html, array $options = []): string
    {
        $target = BibleReference::toApiId($reference);

        $resource = $target['isChapter']
            ? "bibles/{$this->bibleId}/chapters/{$target['id']}"
            : "bibles/{$this->bibleId}/passages/{$target['id']}";

        $bool = static fn (mixed $value, bool $default): string => ($value ?? $default) ? 'true' : 'false';

        try {
            $response = $this->http->get($resource, [
                'headers' => ['api-key' => $this->apiKey],
                'query' => [
                    'content-type' => $html ? 'html' : 'text',
                    'include-verse-numbers' => $bool($options['verseNumbers'] ?? null, $html),
                    'include-titles' => $bool($options['headings'] ?? null, $html),
                    'include-notes' => $bool($options['footnotes'] ?? null, false),
                    'include-chapter-numbers' => 'false',
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new VerseFetchException("API.Bible request failed for \"{$reference}\": {$e->getMessage()}", previous: $e);
        }

        $data = json_decode((string) $response->getBody(), true);
        $content = is_array($data) ? ($data['data']['content'] ?? null) : null;

        if (!is_string($content) || trim($content) === '') {
            throw new VerseFetchException("API.Bible returned no content for \"{$reference}\".");
        }

        return $html ? trim($content) : trim(preg_replace('/\s+/', ' ', $content));
    }
}
