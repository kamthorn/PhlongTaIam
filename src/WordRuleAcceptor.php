<?php
declare(strict_types=1);

namespace PhlongTaIam;

class WordRuleAcceptor extends AbstractAcceptor
{
    public string $tag = "WORD_RULE";
    public string $type = "WORD_RULE";

    public function transit(string $ch): static
    {
        if (($ch >= "a" && $ch <= "z") || ($ch >= "A" && $ch <= "z")) {
            $this->isFinal = true;
            $this->strOffset++;
        } else {
            $this->isError = true;
        }
        return $this;
    }
}
