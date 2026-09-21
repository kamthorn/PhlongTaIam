<?php
namespace PhlongTaIam\Tests;

use PHPUnit\Framework\TestCase;
use PhlongTaIam\Dict;
use PhlongTaIam\WordBreaker;

final class WeightedDictionaryTest extends TestCase
{
    private function fixture(string $name): string
    {
        return __DIR__ . '/fixtures/' . $name;
    }

    public function testAPlainDictionaryIsNotWeighted(): void
    {
        $dict = new Dict();
        $dict->loadDict($this->fixture('mini-dict.txt'));

        $this->assertFalse($dict->isWeighted());
        $this->assertSame(Dict::FLAT_COST, $dict->costOf('กิน'));
    }

    public function testCountsAreReadFromTabSeparatedLines(): void
    {
        $dict = new Dict();
        $dict->loadDict($this->fixture('weighted-dict.txt'));

        $this->assertTrue($dict->isWeighted());
        $this->assertSame(900, $dict->counts['การ']);
        $this->assertTrue($dict->lookup('การ'), 'the word itself still loads');
        $this->assertNull($dict->lookup('การก'), 'and nothing extra does');
    }

    public function testACommonWordCostsLessThanARareOne(): void
    {
        $dict = new Dict();
        $dict->loadDict($this->fixture('weighted-dict.txt'));

        $this->assertLessThan($dict->costOf('กาก'), $dict->costOf('การ'));
    }

    public function testAWordWithNoCountIsTreatedAsSeenOnce(): void
    {
        $dict = new Dict();
        $dict->loadDict($this->fixture('weighted-dict.txt'));
        $dict->addWord('มิ');

        $this->assertGreaterThan($dict->costOf('กาก'), $dict->costOf('มิ'));
    }

    public function testCountsDecideBetweenTwoValidSegmentations(): void
    {
        // "การกิน" can be read as การ+กิน or as กา+ร+กิน; the counted
        // dictionary makes the first far cheaper.
        $weighted = new WordBreaker($this->fixture('weighted-dict.txt'));

        $this->assertTrue($weighted->dict->isWeighted());
        $this->assertSame(['การ', 'กิน'], $weighted->breakIntoWords('การกิน'));
    }

    public function testAnUnweightedDictionaryKeepsTheOriginalSelectionRules(): void
    {
        $plain = new WordBreaker($this->fixture('mini-dict.txt'));

        $this->assertFalse($plain->dict->isWeighted());
        $this->assertSame(['กิน', 'ข้าว'], $plain->breakIntoWords('กินข้าว'));
    }

    public function testMergingACountedListOntoAPlainOneEnablesWeighting(): void
    {
        $merged = new WordBreaker([
            $this->fixture('mini-dict.txt'),
            $this->fixture('weighted-dict.txt'),
        ]);

        $this->assertTrue($merged->dict->isWeighted());
        $this->assertSame(['การ', 'กิน'], $merged->breakIntoWords('การกิน'));
    }
}
