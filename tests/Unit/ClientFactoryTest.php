<?php

namespace PkDev\VerseClient\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PkDev\VerseClient\ClientFactory;
use PkDev\VerseClient\Clients\EsvClient;
use PkDev\VerseClient\Fetchers\ApiBibleVerseFetcher;
use PkDev\VerseClient\Fetchers\EsvVerseFetcher;
use PkDev\VerseClient\Fetchers\HtmlVerseFetcherInterface;
use PkDev\VerseClient\Fetchers\VerseFetcherInterface;
use ReflectionProperty;

class ClientFactoryTest extends TestCase
{
    private string|false $originalToken;

    protected function setUp(): void
    {
        $this->originalToken = getenv('ESV_API_TOKEN');
    }

    protected function tearDown(): void
    {
        putenv($this->originalToken === false ? 'ESV_API_TOKEN' : "ESV_API_TOKEN={$this->originalToken}");
    }

    private function esvToken(EsvVerseFetcher $fetcher): string
    {
        $client = (new ReflectionProperty(EsvVerseFetcher::class, 'client'))->getValue($fetcher);
        $this->assertInstanceOf(EsvClient::class, $client);

        $token = (new ReflectionProperty(EsvClient::class, 'token'))->getValue($client);
        $this->assertIsString($token);

        return $token;
    }

    public function test_esv_builds_an_esv_fetcher_with_the_configured_token(): void
    {
        putenv('ESV_API_TOKEN=from-environment');

        $fetcher = ClientFactory::make('ESV', ['token' => 'from-config']);

        $this->assertInstanceOf(EsvVerseFetcher::class, $fetcher);
        $this->assertSame('from-config', $this->esvToken($fetcher));
    }

    public function test_esv_falls_back_to_the_environment_token(): void
    {
        putenv('ESV_API_TOKEN=from-environment');

        $fetcher = ClientFactory::make('esv');

        $this->assertInstanceOf(EsvVerseFetcher::class, $fetcher);
        $this->assertSame('from-environment', $this->esvToken($fetcher));
    }

    public function test_any_other_version_builds_an_api_bible_fetcher(): void
    {
        $fetcher = ClientFactory::make('niv', ['api_key' => 'key', 'bible_id' => 'bible']);

        $this->assertInstanceOf(ApiBibleVerseFetcher::class, $fetcher);
    }

    /**
     * @return array<string, array{0: array{api_key?: string, bible_id?: string}}>
     */
    public static function incompleteApiBibleConfigs(): array
    {
        return [
            'no config' => [[]],
            'no bible id' => [['api_key' => 'key']],
            'no api key' => [['bible_id' => 'bible']],
        ];
    }

    /**
     * @param  array{api_key?: string, bible_id?: string}  $config
     */
    #[DataProvider('incompleteApiBibleConfigs')]
    public function test_api_bible_requires_an_api_key_and_a_bible_id(array $config): void
    {
        $this->expectException(InvalidArgumentException::class);

        ClientFactory::make('niv', $config);
    }

    /**
     * @return array<string, array{0: string, 1: array{token?: string, api_key?: string, bible_id?: string}}>
     */
    public static function versions(): array
    {
        return [
            'esv' => ['esv', ['token' => 'token']],
            'api.bible' => ['niv', ['api_key' => 'key', 'bible_id' => 'bible']],
        ];
    }

    /**
     * @param  array{token?: string, api_key?: string, bible_id?: string}  $config
     */
    #[DataProvider('versions')]
    public function test_every_fetcher_speaks_both_text_and_html(string $version, array $config): void
    {
        $fetcher = ClientFactory::make($version, $config);

        $this->assertInstanceOf(VerseFetcherInterface::class, $fetcher);
        $this->assertInstanceOf(HtmlVerseFetcherInterface::class, $fetcher);
    }
}
