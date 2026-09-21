<?php
namespace PhlongTaIam\Tests;

use PHPUnit\Framework\TestCase;
use PhlongTaIam\WordBreaker;

/**
 * The shipped dictionaries, and what changes between them.
 */
final class BundledDictionaryTest extends TestCase
{
    private function dictionary(string $name): string
    {
        return __DIR__ . '/../data/' . $name;
    }

    public function testTheDefaultDictionaryKnowsCompoundsTheBaseListDoesNot(): void
    {
        $base = new WordBreaker($this->dictionary('tdict-std.txt'));
        $full = new WordBreaker($this->dictionary('tdict.txt'));

        // The base list has โรง and เรียน separately, so it splits the compound.
        $this->assertSame(['โรง', 'เรียน'], $base->breakIntoWords('โรงเรียน'));
        $this->assertSame(['โรงเรียน'], $full->breakIntoWords('โรงเรียน'));

        $this->assertSame(['โครงการ'], $full->breakIntoWords('โครงการ'));
        $this->assertSame(['รถยนต์'], $full->breakIntoWords('รถยนต์'));
    }

    public function testTheBaseListIsStillShippedForAnyoneUsingIt(): void
    {
        $this->assertFileExists($this->dictionary('tdict-std.txt'));

        $base = new WordBreaker($this->dictionary('tdict-std.txt'));
        $this->assertSame(['ฉัน', 'กิน', 'ข้าว'], $base->breakIntoWords('ฉันกินข้าว'));
    }

    public function testTheDefaultDictionaryIsASupersetOfTheBaseList(): void
    {
        $base = new WordBreaker($this->dictionary('tdict-std.txt'));
        $full = new WordBreaker($this->dictionary('tdict.txt'));

        $baseWords = array_filter($base->dict->prefixes);
        $fullWords = array_filter($full->dict->prefixes);

        $this->assertGreaterThan(count($baseWords), count($fullWords));
        $this->assertSame(
            [],
            array_keys(array_diff_key($baseWords, $fullWords)),
            'these words are in the base list but missing from the default dictionary'
        );
    }

    public function testNeitherDictionaryCarriesCounts(): void
    {
        foreach (['tdict.txt', 'tdict-std.txt'] as $name) {
            $wordBreaker = new WordBreaker($this->dictionary($name));
            $this->assertFalse(
                $wordBreaker->dict->isWeighted(),
                "$name should be a plain word list, so the original selection rules apply"
            );
        }
    }
}
