# verse-tap

A small PHP client for fetching Bible verses and passages by reference, as plain text or as
display-ready HTML. It speaks two providers behind one interface:

- **ESV** through the [ESV API](https://api.esv.org)
- **Any other translation** through [API.Bible](https://api.bible), bound to one Bible by its id

```
ClientFactory::make('esv', ...)  ──►  EsvVerseFetcher       ──►  EsvClient       ──►  api.esv.org/v3
ClientFactory::make('niv', ...)  ──►  ApiBibleVerseFetcher  ──►  ApiBibleClient  ──►  rest.api.bible/v1
```

Both fetchers implement `VerseFetcherInterface` (`fetch()`, plain text) and
`HtmlVerseFetcherInterface` (`fetchHtml()`, HTML with verse numbers and headings).

## Requirements

- PHP 8.3 or later, with `ext-openssl`
- An ESV API token, an API.Bible key, or both, depending on which provider you use

## Installation

```bash
composer require pkdev/verse-tap
```

The namespace is `PkDev\VerseClient`.

## Usage

```php
use PkDev\VerseClient\ClientFactory;
use PkDev\VerseClient\Exceptions\VerseFetchException;

// ESV. The token comes from config, or from the ESV_API_TOKEN environment variable.
$esv = ClientFactory::make('esv', ['token' => $esvToken]);

$text = $esv->fetch('John 3:16');                // plain text, no verse numbers or headings
$html = $esv->fetchHtml('John 3', [              // HTML for a reader view
    'verseNumbers' => true,
    'headings' => true,
    'footnotes' => false,
]);

// Any other version goes to API.Bible. The version name is a label;
// bible_id is what picks the translation.
$niv = ClientFactory::make('niv', [
    'api_key' => $apiBibleKey,
    'bible_id' => $nivBibleId,
]);

try {
    $text = $niv->fetch('1 John 2:1-5');
    $html = $niv->fetchHtml('Psalm 23');
} catch (VerseFetchException $e) {
    // transport error, auth failure, unknown reference, or empty response
}
```

`fetchHtml()` options default to verse numbers and headings on, footnotes off. It returns the
provider's HTML unchanged, not sanitized, so treat it as markup from ESV or API.Bible when you
render it unescaped.

`ClientFactory::make()` returns `VerseFetcherInterface&HtmlVerseFetcherInterface`, so both
`fetch()` and `fetchHtml()` are available on whatever it builds.

You can skip the factory and wire a fetcher yourself. Each client takes an optional
`GuzzleHttp\Client` as its last argument, which is how the tests mock HTTP:

```php
use PkDev\VerseClient\Clients\ApiBibleClient;
use PkDev\VerseClient\Fetchers\ApiBibleVerseFetcher;

$fetcher = new ApiBibleVerseFetcher(new ApiBibleClient($apiBibleKey, $bibleId, $httpClient));
```

### References

The ESV fetcher passes the reference straight to the ESV API, which does its own parsing.

The API.Bible fetcher parses it locally with `Support\BibleReference` into the USFM ids API.Bible
expects. A whole chapter goes to the chapters endpoint; a verse or range goes to passages.

| Form | Example |
|---|---|
| Chapter | `Psalm 23` |
| Verse | `John 3:16` |
| Range in one chapter | `John 3:16-18` |
| Range across chapters | `John 3:16-4:2`, `John 3-4:2` |
| Single-chapter book | `Jude 3` (verse 3), `Jude 3-5`, `Jude 1` (the whole chapter) |
| Numbered book | `1 John 2:1`, `First John 1:9`, `II Kings 4` |
| Abbreviation | `Rom 8:28`, `Gen 1:1` |

All 66 books are recognized, with common abbreviations. In Obadiah, Philemon, 2 John, 3 John
and Jude, a lone number above 1 is a verse; a lone `1` is the whole chapter.

A range of whole chapters with no end verse, such as `John 3-4`, throws `VerseFetchException`:
API.Bible addresses passages verse to verse, and the last verse of a chapter is not known
locally.

## Configuration

| Provider | `make()` config | Notes |
|---|---|---|
| ESV (`'esv'`) | `token` | Falls back to the `ESV_API_TOKEN` environment variable |
| API.Bible (any other name) | `api_key`, `bible_id` | Both required |

## Errors

| Thrown | When |
|---|---|
| `InvalidArgumentException` | `make()` for a non-ESV version without `api_key` or `bible_id` |
| `PkDev\VerseClient\Exceptions\VerseFetchException` | API.Bible `fetch()` or `fetchHtml()`, and ESV `fetchHtml()`, on a failed request (including a non-2xx response), an empty passage, or (API.Bible only) a reference it cannot parse, an unknown book, or a chapter range with no end verse |

`VerseFetchException` extends `RuntimeException`.

ESV `fetch()` is the exception to the rule: it does not throw. On a failed request it returns
`Error: <message>`, and when the response has no passage it returns `Verse not found`.

## Running the tests

```bash
composer install
composer test
```

The suite uses Guzzle mock handlers and runs offline. The one test that calls the live ESV API,
`tests/Feature/VerseFetchTest.php`, is in the `live` group, which `phpunit.xml.dist` excludes by
default. Run it with a token in the environment; without one it skips:

```bash
ESV_API_TOKEN=<your token> composer test -- --group live
```

Static analysis and formatting:

```bash
composer analyze           # PHPStan, level 8
vendor/bin/pint --test     # Pint, house pint.json
```

CI runs the suite on PHP 8.3, 8.4 and 8.5, plus one job on the lowest dependency versions
`composer.json` allows, then PHPStan and Pint. It never runs the live group.

The repo also ships a `Dockerfile` and `docker-compose.yml` (one `php` service on PHP 8.3 with
Composer and Xdebug, which listens on host port 9005). The compose file passes `ESV_API_TOKEN`
through from your shell:

```bash
docker compose up -d --build
docker compose exec php composer install
docker compose exec php composer test
ESV_API_TOKEN=<your token> docker compose up -d
docker compose exec php composer test -- --group live
```

## License

MIT. See [LICENSE](LICENSE).
