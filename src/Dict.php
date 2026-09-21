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
    /** @var array<string, bool> Prefix => whether that prefix is itself a word. */
    public array $prefixes = [];

    /**
     * Replace the dictionary with the words in $dictPath.
     */
    public function loadDict(string $dictPath): void
    {
        $this->prefixes = [];
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

        foreach (explode("\n", $contents) as $word) {
            $this->addWord(trim($word, "\r"));
        }
    }

    public function addWord(string $word): void
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
