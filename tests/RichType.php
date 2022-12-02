<?php

namespace Tests;

class RichType
{
    private int $richField;
    private $richType;
    private $richProperty;
    private $richMap = [];
    private $richList = ["bar"];
    private Type $type;

    public function getRichType(): RichType
    {
        return $this->richType;
    }

    public function setRichType(RichType $richType): void
    {
        $this->richType = $richType;
    }

    public function getRichProperty(): string
    {
        return $this->richProperty;
    }

    public function setRichProperty(string $richProperty): void
    {
        $this->richProperty = $richProperty;
    }

    public function getRichList(): array
    {
        return $this->richList;
    }

    public function setRichList(array $richList): void
    {
        $this->richList = $richList;
    }

    public function getRichMap(): array
    {
        return $this->richMap;
    }

    public function setRichMap(array $richMap): void
    {
        $this->richMap = $richMap;
    }

    public function setEnumType(Type $type): void
    {
        $this->type = $type;
    }

    public function getEnumType(): Type
    {
        return $this->type;
    }
}
