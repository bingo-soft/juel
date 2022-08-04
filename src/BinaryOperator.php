<?php

namespace Juel;

use El\ELContext;

interface BinaryOperator
{
    public function eval(Bindings $bindings, ELContext $context, AstNode $left, AstNode $right);
}
