<?php

namespace Tests;

class SimpleClass
{
    public $prop = true;

    public $prop2 = 2;

    private $prop3 = 3;

    public $propFloat = 5.21;

    private $privateFloat = 4.66;

    public $otherFloat = 2.34;

    public SimpleClass $simple;

    public function getSimpleType(): SimpleClass
    {
        return $this->simple;
    }

    public function setSimpleType(SimpleClass $simple): void
    {
        $this->simple = $simple;
    }

    public $alpha1 = 101;

    public static function sin($value): float
    {
        return sin($value);
    }

    public static function cos($value): float
    {
        return cos($value);
    }

    public static function inArray($needle, array $haystack): bool
    {
        return in_array($needle, $haystack);
    }

    public function foo(): int
    {
        return 1;
    }

    public function bar(): int
    {
        return 2;
    }

    private function goo(): int
    {
        return 3;
    }

    public function beta2(): int
    {
        return 6;
    }
}
