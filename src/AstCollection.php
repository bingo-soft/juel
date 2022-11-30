<?php

namespace Juel;

use El\ELContext;

class AstCollection extends AstRightValue
{
    private $nodes;

    public function __construct(array $nodes)
    {
        $this->nodes = $nodes;
    }

    public function eval(Bindings $bindings, ELContext $context)
    {
        $res = [];
        foreach ($this->nodes as $node) {
            $res[] = $node->eval($bindings, $context);
        }
        return $res;
    }

    public function __toString()
    {
        $strBuffer = [];
        foreach ($this->nodes as $node) {
            $strBuffer[] = $node->__toString();
        }
        return '[ ' . implode(',', $strBuffer) . ']';
    }

    public function appendStructure(string &$b, Bindings $bindings): void
    {
        for ($i = 0; $i < $this->getCardinality(); $i++) {
            $this->nodes[$i]->appendStructure($b, $bindings);
        }
    }

    public function getCardinality(): int
    {
        return count($this->nodes);
    }

    public function getChild(int $i): ?AstNode
    {
        if (array_key_exists($i, $this->nodes)) {
            return $this->nodes[$i];
        }
        return null;
    }
}
