<?php

namespace App\LeanEngine;

use function basename;
use function dirname;
use function exec;
use function sprintf;

class ClearCache
{
    private $dir;

    private $env;

    public function __construct(string $dir, string $env)
    {
        $this->dir = $dir;
        $this->env = $env;
    }

    public function __invoke()
    {
        $cache_dir_parent = dirname($this->dir);
        $cache_dir_this = basename($this->dir);
        $dir_to_remove = $this->env === $cache_dir_this ? $cache_dir_parent : $this->dir;
        $dir_to_remove_parent = dirname($dir_to_remove);
        $list_before_remove = exec(sprintf('ls -la "%s"', $dir_to_remove_parent));
        $remove = exec(sprintf('rm -rf "%s"', $dir_to_remove));
        $list_after_remove = exec(sprintf('ls -la "%s"', $dir_to_remove_parent));

        return [
            'cache_dir' => $this->dir,
            'cache_dir_parent' => $cache_dir_parent,
            'environment' => $this->env,
            'cache_dir_this' => $cache_dir_this,
            'dir_to_remove' => $dir_to_remove,
            'dir_to_remove_parent' => $dir_to_remove_parent,
            'list_before_remove' => $list_before_remove,
            'remove' => $remove,
            'list_after_remove' => $list_after_remove
        ];
    }
}
