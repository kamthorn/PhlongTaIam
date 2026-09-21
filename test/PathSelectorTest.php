<?php
namespace PhlongTaIam\Tests;

use PHPUnit\Framework\TestCase;
use PhlongTaIam\PathSelector;

final class PathSelectorTest extends TestCase
{
    private PathSelector $selector;

    protected function setUp(): void
    {
        $this->selector = new PathSelector();
    }

    public function testPrefersTheCandidateWithFewerUnknownCharacters(): void
    {
        $low = ['unk' => 0, 'mw' => 5, 'w' => 5];
        $high = ['unk' => 1, 'mw' => 0, 'w' => 0];

        $this->assertSame($low, $this->selector->selectPath([$high, $low]));
    }

    public function testBreaksUnkTiesByFewerMultiWordMerges(): void
    {
        $fewerMerges = ['unk' => 0, 'mw' => 0, 'w' => 5];
        $moreMerges = ['unk' => 0, 'mw' => 1, 'w' => 0];

        $this->assertSame($fewerMerges, $this->selector->selectPath([$moreMerges, $fewerMerges]));
    }

    public function testBreaksRemainingTiesByFewerWords(): void
    {
        $fewerWords = ['unk' => 0, 'mw' => 0, 'w' => 1];
        $moreWords = ['unk' => 0, 'mw' => 0, 'w' => 2];

        $this->assertSame($fewerWords, $this->selector->selectPath([$moreWords, $fewerWords]));
    }

    public function testReturnsNullForAnEmptyCandidateList(): void
    {
        $this->assertNull($this->selector->selectPath([]));
    }
}
