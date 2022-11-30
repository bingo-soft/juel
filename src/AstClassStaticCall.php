<?php

namespace Juel;

use El\ELContext;

class AstClassStaticCall extends AstRightValue
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function eval(Bindings $bindings, ELContext $context)
    {
        $parts = explode('::', substr($this->value, 1));
        $clazz = $parts[0];
        if (class_exists($clazz)) {
            //constant or static field, not a function field
            if (strpos($parts[1], '(') === false) {
                if (strpos($parts[1], '$') === false) {
                    return constant($parts[0] . '::' . $parts[1]);
                } else {
                    $propName = substr($parts[1], 1);
                    $ref = new \ReflectionClass($parts[0]);
                    if ($ref->hasProperty($propName)) {
                        $prop = $ref->getProperty($propName);
                        if ($prop->isStatic()) {
                            return $prop->getValue(null);
                        }
                    }
                }
            }
        }
        return null;
    }

    public function __toString()
    {
        return $this->value;
    }

    public function appendStructure(string &$b, Bindings $bindings): void
    {
    }

    public function getCardinality(): int
    {
        return 0;
    }

    public function getChild(int $i): ?AstNode
    {
        return null;
    }
}
