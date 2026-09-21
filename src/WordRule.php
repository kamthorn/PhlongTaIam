<?php
declare(strict_types=1);

namespace PhlongTaIam;

class WordRule implements RuleInterface
{
    public function createAcceptor(array $tag): ?WordRuleAcceptor
    {
        if (array_key_exists("WORD_RULE", $tag))
            return null;
        return new WordRuleAcceptor();
    }
}
