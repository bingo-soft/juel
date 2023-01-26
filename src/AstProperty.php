<?php

namespace Juel;

use El\{
    ELContext,
    ELException,
    MethodInfo,
    MethodNotFoundException,
    PropertyNotFoundException,
    ValueReference
};

abstract class AstProperty extends AstNode
{
    protected $prefix;
    protected $lvalue;
    protected $strict; // allow null as property value?

    public function __construct(?AstNode $prefix, bool $lvalue, bool $strict)
    {
        $this->prefix = $prefix;
        $this->lvalue = $lvalue;
        $this->strict = $strict;
    }

    abstract public function getProperty(Bindings $bindings, ELContext $context);

    public function getPrefix(): AstNode
    {
        return $this->prefix;
    }

    public function getValueReference(Bindings $bindings, ELContext $context): ?ValueReference
    {
        return new ValueReference($this->prefix->eval($bindings, $context), $this->getProperty($bindings, $context));
    }

    public function eval(Bindings $bindings, ELContext $context)
    {
        //if we have meta arguments and property is resolved
        $metaProperty = null;
        if ($this instanceof AstDot) {
            $metaProperty = $this->getFullProperty();
        }
        
        $context->setPropertyResolved(false);

        $resolver = $context->getELResolver();
        if (method_exists($resolver, 'getObjectValue')) {
            $result = $resolver->getObjectValue($context, $metaProperty);
            if ($context->isPropertyResolved()) {
                return $result;
            }
        }
        
        //otherwise
        $base = $this->prefix->eval($bindings, $context);
        if ($base === null) {
            return null;
        }
        $property = $this->getProperty($bindings, $context);
        if ($property === null && $this->strict) {
            return null;
        }
        
        $result = $context->getELResolver()->getValue($context, $base, $property);

        if (!$context->isPropertyResolved()) {
            throw new PropertyNotFoundException(LocalMessages::get("error.property.notfound", $property, $base));
        }
        return $result;
    }

    public function isLiteralText(): bool
    {
        return false;
    }

    public function isLeftValue(): bool
    {
        return $this->lvalue;
    }

    public function isMethodInvocation(): bool
    {
        return false;
    }

    public function getType(Bindings $bindings, ELContext $context): ?string
    {
        if (!$this->lvalue) {
            return null;
        }
        $base = $this->prefix->eval($bindings, $context);
        if ($base === null) {
            throw new PropertyNotFoundException(LocalMessages::get("error.property.base.null", $this->prefix));
        }
        $property = $this->getProperty($bindings, $context);
        if ($property === null && $this->strict) {
            throw new PropertyNotFoundException(LocalMessages::get("error.property.notfound", "null", $base));
        }
        $context->setPropertyResolved(false);
        $result = $context->getELResolver()->getType($context, $base, $property);
        if (!$context->isPropertyResolved()) {
            throw new PropertyNotFoundException(LocalMessages::get("error.property.notfound", $property, $base));
        }
        return $result;
    }

    public function isReadOnly(Bindings $bindings, ELContext $context): bool
    {
        if (!$this->lvalue) {
            return true;
        }
        $base = $this->prefix->eval($bindings, $context);
        if ($base === null) {
            throw new PropertyNotFoundException(LocalMessages::get("error.property.base.null", $this->prefix));
        }
        $property = $this->getProperty($bindings, $context);
        if ($property === null && $this->strict) {
            throw new PropertyNotFoundException(LocalMessages::get("error.property.notfound", "null", $base));
        }
        $context->setPropertyResolved(false);
        $result = $context->getELResolver()->isReadOnly($context, $base, $property);
        if (!$context->isPropertyResolved()) {
            throw new PropertyNotFoundException(LocalMessages::get("error.property.notfound", $property, $base));
        }
        return $result;
    }

    public function setValue(Bindings $bindings, ELContext $context, $value): void
    {
        if (!$this->lvalue) {
            throw new ELException(LocalMessages::get("error.value.set.rvalue", $this->getStructuralId($bindings)));
        }
        $base = $this->prefix->eval($bindings, $context);
        if ($base === null) {
            throw new PropertyNotFoundException(LocalMessages::get("error.property.base.null", $this->prefix));
        }
        $property = $this->getProperty($bindings, $context);
        if ($property === null && $this->strict) {
            throw new PropertyNotFoundException(LocalMessages::get("error.property.notfound", "null", $base));
        }
        $context->setPropertyResolved(false);
        $context->getELResolver()->setValue($context, $base, $property, $value);
        if (!$context->isPropertyResolved()) {
            throw new PropertyNotFoundException(LocalMessages::get("error.property.notfound", $property, $base));
        }
    }

    protected function findMethod(string $name, string $clazz, ?string $returnType = null): \ReflectionMethod
    {
        $method = null;
        try {
            $method = (new \ReflectionClass($clazz))->getMethod($name);
        } catch (\Exception $e) {
            throw new \Exception(LocalMessages::get("error.property.method.notfound", $name, $clazz));
        }
        if ($returnType !== null && $returnType != $method->getReturnType()) {
            throw new \Exception(LocalMessages::get("error.property.method.notfound", $name, $clazz));
        }
        return $method;
    }

    public function getMethodInfo(Bindings $bindings, ELContext $context, ?string $returnType = null): ?MethodInfo
    {
        $base = $this->prefix->eval($bindings, $context);
        if ($base === null) {
            throw new PropertyNotFoundException(LocalMessages::get("error.property.base.null", $this->prefix));
        }
        $property = $this->getProperty($bindings, $context);
        if ($property === null && $this->strict) {
            throw new \Exception(LocalMessages::get("error.property.method.notfound", "null", $base));
        }
        $name = $bindings->convert($property, "string");
        $method = $this->findMethod($name, get_class($base), $returnType);
        return new MethodInfo($method->getName(), $method->getReturnType());
    }

    public function invoke(Bindings $bindings, ELContext $context, ?string $returnType = null, array $paramValues = [])
    {
        $base = $this->prefix->eval($bindings, $context);
        if ($base === null) {
            throw new PropertyNotFoundException(LocalMessages::get("error.property.base.null", $this->prefix));
        }
        $property = $this->getProperty($bindings, $context);
        if ($property === null && $this->strict) {
            throw new \Exception(LocalMessages::get("error.property.method.notfound", "null", $base));
        }
        $name = $bindings->convert($property, "string");
        $method = $this->findMethod($name, get_class($base), $returnType);
        try {
            return $method->invoke($base, ...$paramValues);
        } catch (\Exception $e) {
            throw new ELException(LocalMessages::get("error.property.method.invocation", $name, get_class($base), $e));
        }
    }

    public function getChild(int $i): ?AstNode
    {
        return $i == 0 ? $this->prefix : null;
    }
}
