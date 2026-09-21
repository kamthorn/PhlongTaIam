<?php
declare(strict_types=1);

namespace PhlongTaIam;

class DictAcceptor extends AbstractAcceptor
{
    public string $tag = "DICT";
    public string $type = "DICT";

    public Dict $dict;

    /** The characters consumed so far. */
    public string $prefix = '';

    public function __construct(Dict $dict)
    {
        $this->dict = $dict;
    }

    public function transit(string $ch): static
    {
        $prefix = $this->prefix . $ch;
        $isWord = $this->dict->lookup($prefix);

        if ($isWord === null) {
            $this->isError = true;
        } else {
            $this->prefix = $prefix;
            $this->strOffset++;
            $this->isFinal = $isWord;
        }
        return $this;
    }
}
