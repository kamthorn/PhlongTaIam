<?php
declare(strict_types=1);

namespace PhlongTaIam;

class SingleSymbolRule implements RuleInterface
{
    public function createAcceptor(array $tag): SingleSymbolAcceptor
    {
        return new SingleSymbolAcceptor();
    }
}
