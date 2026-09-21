<?php
namespace PhlongTaIam\Tests;

use PHPUnit\Framework\TestCase;
use PhlongTaIam\Dict;

final class DictTest extends TestCase
{
    private function loadFixtureDict(): Dict
    {
        $dict = new Dict();
        $dict->loadDict(__DIR__ . '/fixtures/mini-dict.txt');
        return $dict;
    }

    public function testLoadDictFiltersOutTrailingBlankLine(): void
    {
        $dict = $this->loadFixtureDict();

        // mini-dict.txt has 8 words plus a trailing newline; the trailing
        // empty line must not become a bogus dictionary entry.
        $this->assertCount(8, $dict->dict);
    }

    public function testTransitAcceptsEveryCharacterOfAKnownWord(): void
    {
        $dict = $this->loadFixtureDict();
        $acceptor = $dict->createAcceptor();

        $acceptor->transit('ก');
        $this->assertFalse($acceptor->isError);
        $this->assertFalse($acceptor->isFinal, 'ก alone is only a prefix, not a full word');

        $acceptor->transit('ิ');
        $this->assertFalse($acceptor->isError);

        $acceptor->transit('น');
        $this->assertFalse($acceptor->isError);
        $this->assertTrue($acceptor->isFinal, 'กิน is a complete dictionary word');
    }

    public function testTransitFlagsErrorForACharacterSequenceNotInDict(): void
    {
        $dict = $this->loadFixtureDict();
        $acceptor = $dict->createAcceptor();

        $acceptor->transit('ซ');

        $this->assertTrue($acceptor->isError);
    }

    public function testIsFinalIsTrueOnAShorterWordThatIsAlsoAPrefixOfLongerOnes(): void
    {
        $dict = $this->loadFixtureDict();
        $acceptor = $dict->createAcceptor();

        // "กา" is itself a word, but "กาก" and "การ" also start with "กา".
        $acceptor->transit('ก');
        $acceptor->transit('า');

        $this->assertFalse($acceptor->isError);
        $this->assertTrue($acceptor->isFinal, 'กา must be recognized as a complete word even though longer entries share its prefix');
    }
}
