<?php

namespace PkDev\VerseClient\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

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
}
