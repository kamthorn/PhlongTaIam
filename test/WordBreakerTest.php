<?php
namespace PhlongTaIam\Tests;

use PHPUnit\Framework\TestCase;
use PhlongTaIam\WordBreaker;

final class WordBreakerTest extends TestCase
{
    private WordBreaker $wordBreaker;

    protected function setUp(): void
    {
        $this->wordBreaker = new WordBreaker(__DIR__ . '/../data/tdict-std.txt');
    }

    public function testBreaksMixedThaiAndLatinTextAsShownInTheReadme(): void
    {
        $words = $this->wordBreaker->breakIntoWords('ฉัน eat ข้าวชิมิ');

        $this->assertSame(
            ['ฉัน', ' ', 'eat', ' ', 'ข้าว', 'ชิ', 'มิ'],
            $words
        );
    }

    public function testReturnsAnEmptyListForAnEmptyString(): void
    {
        $this->assertSame([], $this->wordBreaker->breakIntoWords(''));
    }

    public function testBreaksASingleKnownWord(): void
    {
        $this->assertSame(['กิน'], $this->wordBreaker->breakIntoWords('กิน'));
    }

    public function testKeepsAnUnknownCharacterBetweenTwoKnownWords(): void
    {
        $this->assertSame(
            ['ฉัน', '5', 'กิน'],
            $this->wordBreaker->breakIntoWords('ฉัน5กิน')
        );
    }

    public function testDoesNotSplitAKnownWordWhenFollowedByAnUnknownCharacter(): void
    {
        // Regression test: an off-by-one in the left-boundary bookkeeping
        // used to make the unknown "5" swallow the last character of the
        // preceding dictionary word (e.g. "กิน5" broke into "กิ" + "น5").
        $this->assertSame(['กิน', '5'], $this->wordBreaker->breakIntoWords('กิน5'));
        $this->assertSame(['ข้าว', '5'], $this->wordBreaker->breakIntoWords('ข้าว5'));
    }

    public function testSeveralDictionariesCanBeMerged(): void
    {
        $withoutCustom = $this->wordBreaker->breakIntoWords('ขนมครกโบราณ');
        $this->assertNotSame(['ขนมครกโบราณ'], $withoutCustom, 'not a word in the standard dictionary');

        $merged = new WordBreaker([
            __DIR__ . '/../data/tdict-std.txt',
            __DIR__ . '/fixtures/custom-dict.txt',
        ]);

        $this->assertSame(['ขนมครกโบราณ'], $merged->breakIntoWords('ขนมครกโบราณ'));
        $this->assertSame(['ฉัน', 'กิน'], $merged->breakIntoWords('ฉันกิน'), 'the standard dictionary still applies');
    }

    public function testInsertWordBreaksJoinsTokensWithAZeroWidthSpaceByDefault(): void
    {
        $this->assertSame(
            "ฉัน\u{200B}กิน\u{200B}ข้าว\u{200B}ชิ\u{200B}มิ",
            $this->wordBreaker->insertWordBreaks('ฉันกินข้าวชิมิ')
        );
    }

    public function testInsertWordBreaksAcceptsACustomBreakCharacter(): void
    {
        $this->assertSame(
            'ฉัน|กิน',
            $this->wordBreaker->insertWordBreaks('ฉันกิน', '|')
        );
    }

    public function testALongTextBreaksInWellUnderASecond(): void
    {
        // buildPath() and rangesToTextList() used to call mb_substr() on the
        // full input string once per character and once per word
        // respectively. mb_substr re-scans from the start of the string on
        // every call, so both were effectively O(n^2): this ~22,000
        // character text used to take over a second.
        $chunk = 'การให้คำแนะนำในเรื่องของจุดแข็ง จุดอ่อน และโอกาสในการปรับปรุงให้ดีขึ้น ';
        $letters = range('A', 'Z');
        $text = '';
        for ($i = 0; $i < 300; $i++) {
            $text .= str_repeat($letters[$i % 26], 3).$chunk;
        }
        $this->assertGreaterThan(20000, mb_strlen($text));

        $start = microtime(true);
        $this->wordBreaker->breakIntoWords($text);
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(
            1.0,
            $elapsed,
            "Word-breaking a ".mb_strlen($text)."-character text took {$elapsed}s - an O(n^2) mb_substr loop may be back."
        );
    }
}
