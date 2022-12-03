<?php

namespace Juel;

use El\ELContext;

class AstDot extends AstProperty
{
    protected $property;

    public function __construct(AstNode $base, string $property, bool $lvalue)
    {
        parent::__construct($base, $lvalue, true);
        $this->property = $property;
    }

    public function getProperty(Bindings $bindings, ELContext $context)
    {
        return $this->property;
    }

    public function getFullProperty(): string
    {
        $props = [ $this->property ];
        $flag = true;
        $cur = $this->prefix;
        while ($cur !== null) {
            if ($cur instanceof AstDot) {
                $props[] = $cur->property;
                $cur = $cur->prefix;
            } elseif ($cur instanceof AstIdentifier) {
                $props[] = strval($cur);
                $cur = null;
            }
        }
        return implode('.', array_reverse($props));
    }

    public function __toString()
    {
        return ". " . $this->property;
    }

    public function appendStructure(string &$b, Bindings $bindings): void
    {
        $this->getChild[0]->appendStructure($b, $bindings);
        $b .= ".";
        $b .= $this->property;
    }

    public function getCardinality(): int
    {
        return 1;
    }
}
