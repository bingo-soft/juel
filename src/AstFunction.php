<?php

namespace Juel;

use El\{
    ELContext,
    ELException
};

class AstFunction extends AstRightValue implements FunctionNode
{
    private $index;
    private $name;
    private $params;
    private $varargs;

    public function __construct(string $name, int $index, AstParameters $params, bool $varargs = false)
    {
        $this->name = $name;
        $this->index = $index;
        $this->params = $params;
        $this->varargs = $varargs;
    }

    /**
     * Invoke method.
     * @param bindings
     * @param context
     * @param base
     * @param method
     * @return method result
     * @throws InvocationTargetException
     * @throws IllegalAccessException
     */
    public function invoke(Bindings $bindings, ELContext $context, $base, /*\ReflectionMethod | \ReflectionFunction */$method)
    {
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
        if ($method instanceof \ReflectionMethod) {
            return $method->invoke($base, ...$params);
        } else { /* \ReflectionFunction */
            return $method->invoke(...$params);
        }
    }

    public function eval(Bindings $bindings, ELContext $context)
    {
        $method = $bindings->getFunction($this->index);

        //Check global scope
        $base = null;
        if ($method === null && function_exists($this->name)) {
            $method = new \ReflectionFunction($this->name);
        } else {//Check context variables
            $resolver = $context->getELResolver()->getRootPropertyResolver();
            $methodOwner = $resolver->getMethodOwner($this->name);
            if ($methodOwner !== null) {
                $base = $methodOwner->getOriginalObject();
                $method = $methodOwner->getMethod($this->name);
                if ($method->isPrivate() || $method->isProtected()) {
                    $method->setAccessible(true);
                }
            }
        }
        try {
            return $this->invoke($bindings, $context, $base, $method);
        } catch (\Exception $e) {
            throw new ELException(LocalMessages::get("error.function.invocation", $this->name));
        }
    }

    public function __toString()
    {
        return $this->name;
    }

    public function appendStructure(string &$b, Bindings $bindings): void
    {
        $b .= $bindings !== null && $bindings->isFunctionBound($this->index) ? "<fn>" : $this->name;
        $this->params->appendStructure($b, $bindings);
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isVarArgs(): bool
    {
        return $this->varargs;
    }

    public function getParamCount(): int
    {
        return $this->params->getCardinality();
    }

    protected function getParam(int $i): ?AstNode
    {
        return $this->params->getChild($i);
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
