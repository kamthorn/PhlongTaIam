<?php
declare(strict_types=1);

namespace PhlongTaIam;

use RuntimeException;

class Dict
{
    /** @var string[] Dictionary words, lexicographically sorted. */
    public array $dict = [];

    public function isNotEmptyWord(string $w): bool
    {
        return mb_strlen($w, "UTF-8") > 0;
    }

    public function loadDict(string $dictPath): void
    {
        $contents = @file_get_contents($dictPath);
        if ($contents === false) {
            throw new RuntimeException("Cannot read dictionary file: $dictPath");
        }
        // array_filter preserves keys, so a blank line anywhere but at the
        // end would leave holes that dictSeek's binary search indexes into.
        $this->dict = array_values(
            array_filter(explode("\n", $contents), [$this, "isNotEmptyWord"])
        );
    }

    /**
     * Narrow [$l, $r] to the first ("LEFT") or last ("RIGHT") word whose
     * character at $strOffset is $ch.
     */
    public function dictSeek(int $l, int $r, string $ch, int $strOffset, string $pos): ?int
    {
        $ans = null;
        while ($l <= $r) {
            $m = intdiv($l + $r, 2);
            $dictItem = $this->dict[$m];
            $len = mb_strlen($dictItem, "UTF-8");
            if ($len <= $strOffset) {
                $l = $m + 1;
            } else {
                $ch_ = mb_substr($dictItem, $strOffset, 1, "UTF-8");
                if ($ch_ < $ch) {
                    $l = $m + 1;
                } else if ($ch_ > $ch) {
                    $r = $m - 1;
                } else {
                    $ans = $m;
                    if ($pos == "LEFT") {
                        $r = $m - 1;
                    } else {
                        $l = $m + 1;
                    }
                }
            }
        }
        return $ans;
    }

    public function isFinal(DictAcceptor $acceptor): bool
    {
        $w = $this->dict[$acceptor->l];
        $len = mb_strlen($w, "UTF-8");
        return $len == $acceptor->strOffset;
    }

    public function transit(DictAcceptor $acceptor, string $ch): DictAcceptor
    {
        $l = $this->dictSeek($acceptor->l,
                             $acceptor->r,
                             $ch,
                             $acceptor->strOffset,
                             "LEFT");
        if (!is_null($l)) {
            // The LEFT seek already matched at $l, so the RIGHT seek over
            // [$l, $acceptor->r] always finds at least that same entry.
            $r = $this->dictSeek($l,
                                 $acceptor->r,
                                 $ch,
                                 $acceptor->strOffset,
                                 "RIGHT");
            $acceptor->l = $l;
            $acceptor->r = $r;
            $acceptor->strOffset++;
            $acceptor->isFinal = $this->isFinal($acceptor);
        } else {
            $acceptor->isError = true;
        }
        return $acceptor;
    }

    /**
     * A dictionary acceptor is started at every position, so unlike the rule
     * creators this one ignores the tags already claimed at this position.
     *
     * @param array<string, object> $tag
     */
    public function createAcceptor(array $tag = []): DictAcceptor
    {
        return new DictAcceptor($this);
    }
}

class DictAcceptor
{
    public Dict $dict;
    public int $l;
    public int $r;
    public int $strOffset = 0;
    public bool $isFinal = false;
    public bool $isError = false;
    public string $tag = "DICT";
    public string $type = "DICT";
    public int $w = 1;
    public int $mw = 0;
    public int $unk = 0;

    public function __construct(Dict $dict)
    {
        $this->dict = $dict;
        $this->l = 0;
        $this->r = count($dict->dict) - 1;
    }

    public function transit(string $ch): DictAcceptor
    {
        return $this->dict->transit($this, $ch);
    }
}
