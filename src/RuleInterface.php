<?php
declare(strict_types=1);

namespace PhlongTaIam;

/**
 * Starts acceptors at each character position. Implement this to teach the
 * word breaker a new kind of token.
 */
interface RuleInterface
{
    /**
     * @param array<string, AbstractAcceptor> $tag Tags already claimed at this
     *        position. Rules that allow only one live acceptor at a time
     *        return null when their own tag is present.
     */
    public function createAcceptor(array $tag): ?AbstractAcceptor;
}
