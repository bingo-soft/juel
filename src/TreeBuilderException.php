<?php

namespace Juel;

use El\ELException;

class TreeBuilderException extends ELException
{
    private $expression;
    private $position;
    private $encountered;
    private $expected;

    public function __construct(string $expression, int $position, string $encountered, string $expected, string $message)
    {
        parent::__construct(LocalMessages::get("error.build", $expression, $message));
        $this->expression = $expression;
        $this->position = $position;
        $this->encountered = $encountered;
        $this->expected = $expected;
    }

    /**
     * @return string the expression string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * @return int the error position
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @return string the substring (or description) that has been encountered
     */
    public function getEncountered(): string
    {
        return $this->encountered;
    }

    /**
     * @return string the substring (or description) that was expected
     */
    public function getExpected(): string
    {
        return $this->expected;
    }
}
