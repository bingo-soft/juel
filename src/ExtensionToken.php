<?php

namespace Juel;

class ExtensionToken extends Token
{
    public function __construct(string $image)
    {
        parent::__construct(Symbol::EXTENSION, $image);
    }
}
