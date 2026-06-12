<?php

namespace PkDev\VerseClient\Support;

use PkDev\VerseClient\Exceptions\VerseFetchException;

/**
 * Parses human Bible references ("John 3:16", "1 John 2:1-5", "Psalm 23") into
 * the USFM identifiers API.Bible expects (chapter ids like "JHN.3", passage ids
 * like "JHN.3.16" / "JHN.3.16-JHN.4.2").
 */
class BibleReference
{
    /**
     * Normalised book name (lowercase, alphanumerics only, numeral prefix as a
     * digit) → USFM code. Common abbreviations are included.
     *
     * @var array<string, string>
     */
    private const BOOKS = [
        // Old Testament
        'genesis' => 'GEN', 'gen' => 'GEN',
        'exodus' => 'EXO', 'exod' => 'EXO', 'exo' => 'EXO',
        'leviticus' => 'LEV', 'lev' => 'LEV',
        'numbers' => 'NUM', 'num' => 'NUM',
        'deuteronomy' => 'DEU', 'deut' => 'DEU', 'deu' => 'DEU',
        'joshua' => 'JOS', 'josh' => 'JOS', 'jos' => 'JOS',
        'judges' => 'JDG', 'judg' => 'JDG', 'jdg' => 'JDG',
        'ruth' => 'RUT', 'rut' => 'RUT',
        '1samuel' => '1SA', '1sam' => '1SA', '1sa' => '1SA',
        '2samuel' => '2SA', '2sam' => '2SA', '2sa' => '2SA',
        '1kings' => '1KI', '1kgs' => '1KI', '1ki' => '1KI',
        '2kings' => '2KI', '2kgs' => '2KI', '2ki' => '2KI',
        '1chronicles' => '1CH', '1chron' => '1CH', '1chr' => '1CH', '1ch' => '1CH',
        '2chronicles' => '2CH', '2chron' => '2CH', '2chr' => '2CH', '2ch' => '2CH',
        'ezra' => 'EZR', 'ezr' => 'EZR',
        'nehemiah' => 'NEH', 'neh' => 'NEH',
        'esther' => 'EST', 'est' => 'EST',
        'job' => 'JOB',
        'psalm' => 'PSA', 'psalms' => 'PSA', 'psa' => 'PSA', 'ps' => 'PSA',
        'proverbs' => 'PRO', 'prov' => 'PRO', 'pro' => 'PRO', 'prv' => 'PRO',
        'ecclesiastes' => 'ECC', 'eccl' => 'ECC', 'ecc' => 'ECC',
        'songofsolomon' => 'SNG', 'songofsongs' => 'SNG', 'song' => 'SNG', 'canticles' => 'SNG', 'sng' => 'SNG', 'sos' => 'SNG',
        'isaiah' => 'ISA', 'isa' => 'ISA',
        'jeremiah' => 'JER', 'jer' => 'JER',
        'lamentations' => 'LAM', 'lam' => 'LAM',
        'ezekiel' => 'EZK', 'ezek' => 'EZK', 'ezk' => 'EZK',
        'daniel' => 'DAN', 'dan' => 'DAN',
        'hosea' => 'HOS', 'hos' => 'HOS',
        'joel' => 'JOL', 'jol' => 'JOL',
        'amos' => 'AMO', 'amo' => 'AMO',
        'obadiah' => 'OBA', 'obad' => 'OBA', 'oba' => 'OBA',
        'jonah' => 'JON', 'jon' => 'JON',
        'micah' => 'MIC', 'mic' => 'MIC',
        'nahum' => 'NAM', 'nah' => 'NAM', 'nam' => 'NAM',
        'habakkuk' => 'HAB', 'hab' => 'HAB',
        'zephaniah' => 'ZEP', 'zeph' => 'ZEP', 'zep' => 'ZEP',
        'haggai' => 'HAG', 'hag' => 'HAG',
        'zechariah' => 'ZEC', 'zech' => 'ZEC', 'zec' => 'ZEC',
        'malachi' => 'MAL', 'mal' => 'MAL',
        // New Testament
        'matthew' => 'MAT', 'matt' => 'MAT', 'mat' => 'MAT',
        'mark' => 'MRK', 'mrk' => 'MRK', 'mar' => 'MRK', 'mk' => 'MRK',
        'luke' => 'LUK', 'luk' => 'LUK', 'lk' => 'LUK',
        'john' => 'JHN', 'jhn' => 'JHN', 'jn' => 'JHN',
        'acts' => 'ACT', 'act' => 'ACT',
        'romans' => 'ROM', 'rom' => 'ROM',
        '1corinthians' => '1CO', '1cor' => '1CO', '1co' => '1CO',
        '2corinthians' => '2CO', '2cor' => '2CO', '2co' => '2CO',
        'galatians' => 'GAL', 'gal' => 'GAL',
        'ephesians' => 'EPH', 'eph' => 'EPH',
        'philippians' => 'PHP', 'phil' => 'PHP', 'php' => 'PHP',
        'colossians' => 'COL', 'col' => 'COL',
        '1thessalonians' => '1TH', '1thess' => '1TH', '1th' => '1TH',
        '2thessalonians' => '2TH', '2thess' => '2TH', '2th' => '2TH',
        '1timothy' => '1TI', '1tim' => '1TI', '1ti' => '1TI',
        '2timothy' => '2TI', '2tim' => '2TI', '2ti' => '2TI',
        'titus' => 'TIT', 'tit' => 'TIT',
        'philemon' => 'PHM', 'phlm' => 'PHM', 'phm' => 'PHM',
        'hebrews' => 'HEB', 'heb' => 'HEB',
        'james' => 'JAS', 'jas' => 'JAS', 'jam' => 'JAS',
        '1peter' => '1PE', '1pet' => '1PE', '1pe' => '1PE',
        '2peter' => '2PE', '2pet' => '2PE', '2pe' => '2PE',
        '1john' => '1JN', '1jn' => '1JN',
        '2john' => '2JN', '2jn' => '2JN',
        '3john' => '3JN', '3jn' => '3JN',
        'jude' => 'JUD', 'jud' => 'JUD',
        'revelation' => 'REV', 'revelations' => 'REV', 'rev' => 'REV',
    ];

    /**
     * Parse a reference into its components.
     *
     * @return array{book: string, chapter: int, verseStart: ?int, endChapter: ?int, verseEnd: ?int, isChapter: bool}
     *
     * @throws VerseFetchException when the reference can't be parsed or the book is unknown
     */
    public static function parse(string $reference): array
    {
        $ref = trim($reference);

        if (!preg_match('/^(.+?)\s+(\d+)(?::(\d+))?(?:\s*-\s*(?:(\d+):)?(\d+))?$/u', $ref, $m)) {
            throw new VerseFetchException("Unparseable reference: {$reference}");
        }

        $book = self::resolveBook($m[1]);
        $chapter = (int) $m[2];
        $verseStart = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : null;
        $endChapter = isset($m[4]) && $m[4] !== '' ? (int) $m[4] : null;
        $verseEnd = isset($m[5]) && $m[5] !== '' ? (int) $m[5] : null;

        return [
            'book' => $book,
            'chapter' => $chapter,
            'verseStart' => $verseStart,
            'endChapter' => $endChapter,
            'verseEnd' => $verseEnd,
            'isChapter' => $verseStart === null,
        ];
    }

    /**
     * The API.Bible identifier for a reference, plus whether it addresses a
     * whole chapter (chapters endpoint) or a verse/range (passages endpoint).
     *
     * @return array{id: string, isChapter: bool}
     *
     * @throws VerseFetchException
     */
    public static function toApiId(string $reference): array
    {
        $p = self::parse($reference);
        $book = $p['book'];

        if ($p['isChapter']) {
            return ['id' => "{$book}.{$p['chapter']}", 'isChapter' => true];
        }

        $start = "{$book}.{$p['chapter']}.{$p['verseStart']}";

        if ($p['verseEnd'] === null) {
            return ['id' => $start, 'isChapter' => false];
        }

        $endChapter = $p['endChapter'] ?? $p['chapter'];
        $end = "{$book}.{$endChapter}.{$p['verseEnd']}";

        return ['id' => "{$start}-{$end}", 'isChapter' => false];
    }

    /**
     * @throws VerseFetchException when the book name isn't recognised
     */
    private static function resolveBook(string $book): string
    {
        // Convert a leading numeral prefix (digit, roman, or word) to a digit,
        // using the original space boundary so "I John" → "1 John" but "Isaiah"
        // is untouched.
        $book = preg_replace('/^\s*(iii|third|3rd)\s+/i', '3 ', $book);
        $book = preg_replace('/^\s*(ii|second|2nd)\s+/i', '2 ', (string) $book);
        $book = preg_replace('/^\s*(i|first|1st)\s+/i', '1 ', (string) $book);

        $key = strtolower((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $book));

        if (!isset(self::BOOKS[$key])) {
            throw new VerseFetchException("Unknown book in reference: {$book}");
        }

        return self::BOOKS[$key];
    }
}
