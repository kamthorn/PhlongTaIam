<?php
declare(strict_types=1);

namespace PhlongTaIam;

class DictAcceptor extends AbstractAcceptor
{
    public string $tag = "DICT";
    public string $type = "DICT";

    public Dict $dict;

    /** Left bound of the dictionary range still matching the prefix read so far. */
    public int $l;

    /** Right bound of that range. */
    public int $r;

    public function __construct(Dict $dict)
    {
        $this->dict = $dict;
        $this->l = 0;
        $this->r = count($dict->dict) - 1;
    }

    public function transit(string $ch): static
    {
        return $this->dict->transit($this, $ch);
    }
}
