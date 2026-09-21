<?php
declare(strict_types=1);

namespace PhlongTaIam;

class Acceptors
{
    /** @var object[] Rule/dictionary objects that can start a new acceptor. */
    public array $creators = [];

    /** @var object[] Acceptors still alive at the current position. */
    public array $current = [];

    /** @var array<string, object> Tags claimed at the current position. */
    public array $tag = [];

    public function reset(): void
    {
        $this->current = [];
    }

    public function transit(string $ch): void
    {
        foreach ($this->creators as $creator) {
            $acceptor = $creator->createAcceptor($this->tag);
            if (!is_null($acceptor))
                $this->current[] = $acceptor;
        }

        $_current = [];
        $this->tag = [];

        for ($i = 0; $i < count($this->current); $i++) {
            $_acceptor = $this->current[$i];
            $acceptor = $_acceptor->transit($ch);

            if (!$acceptor->isError) {
                $_current[] = $acceptor;
                $this->tag[$acceptor->tag] = $acceptor;
            }
        }
        $this->current = $_current;
    }

    /**
     * @return object[]
     */
    public function getFinalAcceptors(): array
    {
        $finalAcceptors = [];
        foreach ($this->current as $acceptor) {
            if ($acceptor->isFinal) {
                $finalAcceptors[] = $acceptor;
            }
        }
        return $finalAcceptors;
    }
}
