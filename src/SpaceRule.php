<?php
declare(strict_types=1);

namespace PhlongTaIam;

class SpaceRule implements RuleInterface
{
    public function createAcceptor(array $tag): ?SpaceRuleAcceptor
    {
        if (array_key_exists("SPACE_RULE", $tag))
            return null;
        return new SpaceRuleAcceptor();
    }
}
