<?php
declare(strict_types=1);

namespace PhlongTaIam;

use RuntimeException;

/**
 * Dictionary of known words, stored as the set of every prefix of every word.
 *
 * Matching a token only ever asks two things of the dictionary: can the
 * characters read so far still grow into some word, and are they already a
 * word. A prefix map answers both with one hash lookup, which is why the
 * words themselves are not kept: array_keys(array_filter($prefixes)) gives
 * them back.
 */
class Dict implements RuleInterface
{
    /** Cost charged for a known word when the dictionary carries no counts. */
    public const FLAT_COST = 1.0;

    /** @var array<string, bool> Prefix => whether that prefix is itself a word. */
    public array $prefixes = [];

    /** @var array<string, int> Word => how often a corpus contained it. */
    public array $counts = [];

    /** @var array<string, float> Word => -log(probability), built on demand. */
    private array $costs = [];

    private int $totalCount = 0;

    /**
     * Replace the dictionary with the words in $dictPath.
     */
    public function loadDict(string $dictPath): void
    {
        $this->prefixes = [];
        $this->counts = [];
        $this->costs = [];
        $this->totalCount = 0;
        $this->addDict($dictPath);
    }

    /**
     * Merge the words in $dictPath into the dictionary, keeping what is
     * already there - e.g. a project's own terms on top of the bundled list.
     */
    public function addDict(string $dictPath): void
    {
        $contents = @file_get_contents($dictPath);
        if ($contents === false) {
            throw new RuntimeException("Cannot read dictionary file: $dictPath");
        }

        foreach (explode("\n", $contents) as $line) {
            $line = trim($line, "\r");
            // "word" or, when the list carries corpus counts, "word<TAB>count".
            $tab = strpos($line, "\t");
            if ($tab === false) {
                $this->addWord($line);
            } else {
                $count = substr($line, $tab + 1);
                $this->addWord(substr($line, 0, $tab), ctype_digit($count) ? (int) $count : null);
            }
        }
    }

    public function addWord(string $word, ?int $count = null): void
    {
        $chars = mb_str_split($word, 1, "UTF-8");
        $last = count($chars) - 1;
        $prefix = '';
        foreach ($chars as $i => $char) {
            $prefix .= $char;
            if ($i === $last) {
                $this->prefixes[$prefix] = true;
            } elseif (!isset($this->prefixes[$prefix])) {
                $this->prefixes[$prefix] = false;
            }
        }

        if ($count !== null && $last >= 0) {
            $this->counts[$word] = ($this->counts[$word] ?? 0) + $count;
            $this->totalCount += $count;
            $this->costs = [];
        }
    }

    /** Whether this dictionary carries corpus counts to weight words by. */
    public function isWeighted(): bool
    {
        return $this->counts !== [];
    }

    /**
     * How much a path pays for using $word.
     *
     * Without counts every known word costs the same, which is what the
     * unweighted selector expects. With counts it is -log(p), so a path made
     * of likely words is cheaper than one made of unlikely ones, and a word
     * the counts never saw is treated as having been seen once.
     */
    public function costOf(string $word): float
    {
        if (!$this->isWeighted()) {
            return self::FLAT_COST;
        }
        if (isset($this->costs[$word])) {
            return $this->costs[$word];
        }

        $count = $this->counts[$word] ?? 1;
        return $this->costs[$word] = -log($count / ($this->totalCount + 1));
    }

    /**
     * @return bool|null Whether $prefix is a complete word, or null when no
     *                   word starts with it.
     */
    public function lookup(string $prefix): ?bool
    {
        return $this->prefixes[$prefix] ?? null;
    }

    /**
     * A dictionary acceptor is started at every position, so unlike the rule
     * creators this one ignores the tags already claimed at this position.
     *
     * @param array<string, AbstractAcceptor> $tag
     */
    public function createAcceptor(array $tag = []): DictAcceptor
    {
        return new DictAcceptor($this);
    }
}
