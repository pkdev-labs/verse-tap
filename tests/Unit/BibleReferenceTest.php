<?php

namespace PkDev\VerseClient\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PkDev\VerseClient\Exceptions\VerseFetchException;
use PkDev\VerseClient\Support\BibleReference;

class BibleReferenceTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function references(): array
    {
        return [
            'single verse' => ['John 3:16', 'JHN.3.16', false],
            'verse range same chapter' => ['John 3:16-18', 'JHN.3.16-JHN.3.18', false],
            'verse range cross chapter' => ['John 3:16-4:2', 'JHN.3.16-JHN.4.2', false],
            'classic passage' => ['Genesis 1:1-2:3', 'GEN.1.1-GEN.2.3', false],
            'whole chapter' => ['Psalm 23', 'PSA.23', true],
            'numbered digit' => ['1 John 2:1-5', '1JN.2.1-1JN.2.5', false],
            'numbered word' => ['First John 1:9', '1JN.1.9', false],
            'numbered roman' => ['II Kings 4', '2KI.4', true],
            'abbreviation' => ['Rom 8:28', 'ROM.8.28', false],
            'song of solomon' => ['Song of Solomon 1:1', 'SNG.1.1', false],
            'psalms plural' => ['Psalms 119:105', 'PSA.119.105', false],
            'chapter into a verse' => ['John 3-4:2', 'JHN.3.1-JHN.4.2', false],
            'single-chapter book, whole chapter' => ['Jude 1', 'JUD.1', true],
            'single-chapter book, verse' => ['Jude 3', 'JUD.1.3', false],
            'single-chapter book, verse range' => ['Jude 3-5', 'JUD.1.3-JUD.1.5', false],
            'single-chapter book, range from verse 1' => ['Jude 1-4', 'JUD.1.1-JUD.1.4', false],
            'single-chapter book, chapter and verse' => ['Jude 1:3', 'JUD.1.3', false],
            'philemon verse' => ['Philemon 4', 'PHM.1.4', false],
            'third john verse' => ['3 John 2', '3JN.1.2', false],
            'obadiah verse' => ['Obadiah 15', 'OBA.1.15', false],
            'second john verse' => ['2 John 6', '2JN.1.6', false],
        ];
    }

    #[DataProvider('references')]
    public function test_it_resolves_references_to_api_bible_ids(string $reference, string $expectedId, bool $isChapter): void
    {
        $result = BibleReference::toApiId($reference);

        $this->assertSame($expectedId, $result['id']);
        $this->assertSame($isChapter, $result['isChapter']);
    }

    public function test_isaiah_is_not_treated_as_a_roman_numeral_prefix(): void
    {
        $this->assertSame('ISA.40.31', BibleReference::toApiId('Isaiah 40:31')['id']);
    }

    public function test_a_chapter_range_throws_instead_of_returning_one_chapter(): void
    {
        $this->expectException(VerseFetchException::class);
        $this->expectExceptionMessage('Chapter ranges are not supported: John 3-4');
        BibleReference::toApiId('John 3-4');
    }

    public function test_a_chapter_range_parses_its_end_chapter(): void
    {
        $parsed = BibleReference::parse('John 3-4');

        $this->assertSame(3, $parsed['chapter']);
        $this->assertSame(4, $parsed['endChapter']);
        $this->assertNull($parsed['verseStart']);
        $this->assertNull($parsed['verseEnd']);
        $this->assertFalse($parsed['isChapter']);
    }

    public function test_unknown_book_throws(): void
    {
        $this->expectException(VerseFetchException::class);
        BibleReference::toApiId('Hezekiah 3:1');
    }

    public function test_unparseable_reference_throws(): void
    {
        $this->expectException(VerseFetchException::class);
        BibleReference::toApiId('not a reference');
    }
}
