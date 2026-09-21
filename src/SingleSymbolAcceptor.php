<?php
declare(strict_types=1);

namespace PhlongTaIam;

class SingleSymbolAcceptor extends AbstractAcceptor
{
    public string $tag = "SINSYM";
    public string $type = "SINSYM";

    public function transit(string $ch): static
    {
        if ($this->strOffset == 0 && mb_strpos("()/-", $ch, 0, "UTF-8") !== false) {
            $this->isFinal = true;
            $this->strOffset++;
        } else {
            $this->isError = true;
        }
        return $this;
    }
}
