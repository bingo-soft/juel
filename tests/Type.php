<?php

namespace Tests;

enum Type: string
{
    case EMPLOYEE = 'EMPLOYEE';
    case DIRECTOR = 'DIRECTOR';

    public function toString()
    {
        return $this->value;
    }
}
