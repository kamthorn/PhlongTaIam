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
}
