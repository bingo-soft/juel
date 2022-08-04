<?php

namespace Juel;

use El\ELContext;

interface UnaryOperator
{
    public function eval(Bindings $bindings, ELContext $context, AstNode $node);
}
