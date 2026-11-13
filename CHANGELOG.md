# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - Unreleased

### Changed
- `ClientFactory::make()` now declares `VerseFetcherInterface&HtmlVerseFetcherInterface`,
  so static analysis accepts `make(...)->fetchHtml()` without an `instanceof` check.
- The ESV token and API.Bible key are marked `#[\SensitiveParameter]`, so they are redacted
  from stack traces that include arguments.
- Package metadata: a description that says what the package does, more keywords, and an
  author.

### Fixed
- `BibleReference` no longer truncates a chapter range such as `John 3-4` to its first
  chapter. API.Bible passages are addressed verse to verse, so a range with no end verse now
  throws `VerseFetchException`; `John 3-4:2` resolves to `JHN.3.1-JHN.4.2`.
- Verse references in the single-chapter books (Obadiah, Philemon, 2 John, 3 John, Jude)
  resolve to chapter 1: `Jude 3` is `JUD.1.3`, `Jude 3-5` is `JUD.1.3-JUD.1.5`. A lone `1`
  still means the whole chapter, so `Jude 1` is `JUD.1`.
- The support links on Packagist pointed at `pkdev-labs/verse-client`, which does not
  exist. They now point at `pkdev-labs/verse-tap`.

### Removed
- The `pkdev/traits` dependency, which nothing used.
- Tests, Docker files and tool configuration from the installed package.

### Added
- The MIT `LICENSE` file. The license was already MIT; only the text was missing.

## [1.1.2] - 2026-06-12

### Changed
- Minimum PHP raised from 8.2 to 8.3, and PHPUnit from 10.5 to 12. This floor raise belonged in a
  minor release; Composer keeps PHP 8.2 users on 1.1.1.

## [1.1.1] - 2026-06-12

Same commit as 1.1.0.

## [1.1.0] - 2026-06-12

### Added
- API.Bible support through `ApiBibleClient` and `ApiBibleVerseFetcher`, for any
  translation API.Bible licenses.
- `HtmlVerseFetcherInterface` and `fetchHtml()` on both fetchers, for display-ready HTML
  with verse numbers and headings.
- `VerseFetchException`, thrown by the new fetch paths.
- `Support\BibleReference`, which parses references into API.Bible ids.

### Changed
- `ClientFactory::make()` takes a config array (`token`, `api_key`, `bible_id`); any version
  other than `esv` goes to API.Bible.
- `pkdev/traits` requirement raised to `^2.0`.

## [1.0.1] - 2025-08-15

### Changed
- Package renamed from `pkdev/verse-client` to `pkdev/verse-tap`. The namespace stays
  `PkDev\VerseClient`.

## [1.0.0] - 2025-08-15

### Added
- ESV API client and `EsvVerseFetcher`, built through `ClientFactory::make('esv')`.

[1.2.0]: https://github.com/pkdev-labs/verse-tap/compare/v1.1.2...develop
[1.1.2]: https://github.com/pkdev-labs/verse-tap/compare/v1.1.1...v1.1.2
[1.1.1]: https://github.com/pkdev-labs/verse-tap/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/pkdev-labs/verse-tap/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/pkdev-labs/verse-tap/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/pkdev-labs/verse-tap/releases/tag/v1.0.0
