<?php

namespace PkDev\VerseClient\Tests\Unit;

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
        ];
    }

    /**
     * @dataProvider references
     */
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
