<?php
declare(strict_types=1);

namespace PhlongTaIam;

/**
 * State of one candidate token being matched from a given start position.
 *
 * Acceptors are consumed by Acceptors::transit(), which feeds them one
 * character at a time and drops them once isError is set, and by
 * PathInfoBuilder, which reads the scoring fields of the ones that are
 * isFinal. PHP interfaces cannot declare properties, so that shared state
 * lives here rather than in an interface.
 */
abstract class AbstractAcceptor
{
    /** Number of characters consumed so far. */
    public int $strOffset = 0;

    /** Whether the characters consumed so far form a complete token. */
    public bool $isFinal = false;

    /** Whether this candidate has been ruled out. */
    public bool $isError = false;

    /** Identifies the rule, so at most one acceptor per rule starts here. */
    public string $tag;

    /** Recorded on the path, and read back by WordBreaker::buildPath(). */
    public string $type;

    /** Word count contributed to a path. */
    public int $w = 1;

    /** Merge count contributed to a path. */
    public int $mw = 0;

    /** Unknown-character count contributed to a path. */
    public int $unk = 0;

    abstract public function transit(string $ch): static;
}
