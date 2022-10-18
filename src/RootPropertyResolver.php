<?php

namespace Juel;

use El\{
    ELContext,
    ELResolver,
    PropertyNotFoundException,
    PropertyNotWritableException
};
use Util\Reflection\MetaObject;

class RootPropertyResolver extends ELResolver
{
    private $map = [];
    private $readOnly;
    
    /**
     * Create a root property resolver
     *
     * @param readOnly
     */
    public function __construct(?bool $readOnly = false)
    {
        $this->readOnly = $readOnly;
    }

    private function isResolvable($base): bool
    {
        return $base === null;
    }

    private function resolve(?ELContext $context, $base, $property): bool
    {
        $context->setPropertyResolved($this->isResolvable($base) && gettype($property) == "string");
        return $context->isPropertyResolved();
    }

    public function getCommonPropertyType(?ELContext $context, $base): ?string
    {
        return $this->isResolvable($context) ? "string" : null;
    }

    public function getFeatureDescriptors(?ELContext $context, $base): ?array
    {
        return null;
    }

    public function getType(?ELContext $context, $base, $property)
    {
        return $this->resolve($context, $base, $property) ? "object" : null;
    }

    public function getValue(?ELContext $context, $base, $property)
    {
        if ($this->resolve($context, $base, $property)) {
            if (!$this->isProperty($property)) {
                $propertyOwner = $this->getPropertyOwner($property);
                if ($propertyOwner !== null) {
                    return $propertyOwner->getValue($property);
                }
                throw new PropertyNotFoundException("Cannot find property " . $property);
            }
            return $this->getProperty($property);
        }
        return null;
    }

    public function getPropertyOwner($property)
    {
        foreach ($this->map as $key => $object) {
            if (is_object($object)) {
                if ($object instanceof MetaObject && $object->hasGetter($property)) {
                    return $object;
                } elseif (property_exists($object, $property)) {
                    return new MetaObject($object);
                }
            }
        }

        return null;
    }

    public function getMetaObjectValue(?ELContext $context, string $property)
    {
        foreach ($this->map as $key => $object) {
            if ($object instanceof MetaObject && $object->isPropertyInitialized($property)) {
                $context->setPropertyResolved(true);
                return $object->getValue($property);
            }
        }
        return null;
    }

    public function getMethodOwner(string $methodName)
    {
        foreach ($this->map as $key => $object) {
            if (is_object($object)) {
                if ($object instanceof MetaObject && $object->hasMethod($methodName)) {
                    return $object;
                } elseif (method_exists($object, $methodName)) {
                    return new MetaObject($object);
                }
            }
        }

        return null;
    }

    public function isReadOnly(?ELContext $context, $base, $property): bool
    {
        return $this->resolve($context, $base, $property) ? $this->readOnly : false;
    }

    public function setValue(?ELContext $context, $base, $property, $value): void
    {
        if ($this->resolve($context, $base, $property)) {
            if ($this->readOnly) {
                throw new PropertyNotWritableException("Resolver is read only!");
            }
            if ($value instanceof MetaObject && $context !== null) {
                $context->setMetaArguments(true);
            }
            $this->setProperty($property, $value);
        }
    }

    public function invoke(?ELContext $context, $base, $method, ?array $params = [])
    {
        if ($this->resolve($context, $base, $method)) {
            throw new \Exception("Cannot invoke method " . $method . " on null");
        }
        return null;
    }

    /**
     * Get property value
     *
     * @param property
     *            property name
     * @return value associated with the given property
     */
    public function getProperty(string $property)
    {
        if (array_key_exists($property, $this->map)) {
            return $this->map[$property];
        }
        return null;
    }

    /**
     * Set property value
     *
     * @param property
     *            property name
     * @param value
     *            property value
     */
    public function setProperty(string $property, $value): void
    {
        $this->map[$property] = $value;
    }

    /**
     * Test property
     *
     * @param property
     *            property name
     * @return <code>true</code> if the given property is associated with a value
     */
    public function isProperty(string $property): bool
    {
        return array_key_exists($property, $this->map);
    }

    /**
     * Get properties
     *
     * @return all property names (in no particular order)
     */
    public function properties(): array
    {
        return array_keys($this->map);
    }
}
