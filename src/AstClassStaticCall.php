<?php

namespace Juel;

use El\ELContext;

class AstClassStaticCall extends AstRightValue
{
    private $value;
    private $params;

    public function __construct(string $value, ?AstParameters $params)
    {
        $this->value = $value;
        $this->params = $params;
    }

    public function eval(Bindings $bindings, ELContext $context)
    {
        $parts = explode('::', substr($this->value, 1));
        $clazz = $parts[0];
        if (class_exists($clazz)) {
            //static function or constant
            if (strpos($parts[1], '$') === false) {
                if (method_exists($clazz, $parts[1])) {
                    return $this->invoke($bindings, $context, $clazz, $parts[1]);
                }
                return constant($clazz . '::' . $parts[1]);
            } else { //static field
                $ref = new \ReflectionClass($clazz);
                $propName = substr($parts[1], 1);                
                if ($ref->hasProperty($propName)) {
                    $prop = $ref->getProperty($propName);
                    if ($prop->isStatic()) {
                        return $prop->getValue(null);
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

    private function invoke(Bindings $bindings, ELContext $context, string $clazz, string $method)
    {
        $ref = new \ReflectionClass($clazz);
        $method = $ref->getMethod($method);

        $parameters = $method->getParameters();
        $types = [];
        if (!empty($parameters)) {
            foreach ($parameters as $param) {
                $type = $param->getType();
                if ($type !== null) {
                    $types[] = $type->getName();
                } else {
                    $types[] = "undefined";
                }
            }
        }
        $params = [];
        for ($i = 0; $i < count($parameters); $i++) {
            $node = $this->getParam($i);
            if ($node !== null) {
                $param = $node->eval($bindings, $context);
                if ($param !== null && $types[$i] != "undefined") {
                    $params[$i] = $bindings->convert($param, $types[$i]);
                } else {
                    $params[$i] = $param;
                }
            }
        }

        return $method->invoke(null, ...$params);;
    }

    public function getParamCount(): int
    {
        return $this->params->getCardinality();
    }

    protected function getParam(int $i): ?AstNode
    {
        return $this->params->getChild($i);
    }

    public function appendStructure(string &$b, Bindings $bindings): void
    {
    }

    public function getCardinality(): int
    {
        return 1;
    }

    public function getChild(int $i): ?AstNode
    {
        return $i == 0 ? $this->params : null;
    }
}
