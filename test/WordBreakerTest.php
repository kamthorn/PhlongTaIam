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
}
