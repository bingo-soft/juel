<?php

namespace Juel;

class MethodWrapper
{
    public $method;
    public $name;
    public $class;
    public $parameterTypes;

    public function __construct(\ReflectionMethod $method)
    {
        $this->method = $method;
        $this->name = $method->name;
        $this->class = $method->class;
        $parameters = $this->method->getParameters();
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
        $this->parameterTypes = $types;
    }

    public function __serialize(): array
    {
        return [
            'name' => $this->name,
            'class' => $this->class,
            'parameterTypes' => $this->parameterTypes
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->name = $data['name'];
        $this->class = $data['class'];
        $this->parameterTypes = $data['parameterTypes'];

        $class = new \ReflectionClass($this->class);
        $methods = $class->getMethods();
        foreach ($methods as $method) {
            if ($method->name == $this->name) {
                $this->method = $method;
                break;
            }
        }
    }
}
