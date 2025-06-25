<?php
/**
 * @license MIT
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace BracketSpace\Notification\Dependencies\League\Flysystem;

use RuntimeException;

class PathTraversalDetected extends RuntimeException implements FilesystemException
{
    /**
     * @var string
     */
    private $path;

    public function path(): string
    {
        return $this->path;
    }

    public static function forPath(string $path): PathTraversalDetected
    {
        $e = new PathTraversalDetected("Path traversal detected: {$path}");
        $e->path = $path;

        return $e;
    }
}
