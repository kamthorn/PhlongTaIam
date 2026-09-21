<?php
declare(strict_types=1);

namespace PhlongTaIam;

class SpaceRuleAcceptor extends AbstractAcceptor
{
    public string $tag = "SPACE_RULE";
    public string $type = "SPACE_RULE";

    public function transit(string $ch): static
    {
        if ($ch == " " || $ch == "\t" || $ch == "\r" || $ch == "\n") {
            $this->isFinal = true;
            $this->strOffset++;
        } else {
            $this->isError = true;
        }
        return $this;
    }
}
