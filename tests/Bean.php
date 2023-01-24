<?php

namespace Tests;

class Bean
{
    public $id;

    protected $field = 'value';

    public function __construct(?string $property)
    {
        $this->id = $property;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $property): void
    {
        $this->id = $property;
    }
}
