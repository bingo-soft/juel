<?php

namespace Juel;

class Cache implements TreeCache
{
    private $cache = [];

    public function get(?string $expression): ?Tree
    {
        $tree = null;
        if (array_key_exists($expression, $this->cache)) {
            $tree = $this->cache[$expression];
        }
        return $tree;
    }

    public function put(?string $expression, Tree $tree): void
    {
        if ($expression !== null) {
            $this->cache[$expression] = $tree;
        }
    }
}
