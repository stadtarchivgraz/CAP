<?php
/**
 * @license MIT
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace BracketSpace\Notification\Dependencies\League\Flysystem;

interface FilesystemOperationFailed extends FilesystemException
{
    public const OPERATION_WRITE = 'WRITE';
    public const OPERATION_UPDATE = 'UPDATE';
    public const OPERATION_FILE_EXISTS = 'FILE_EXISTS';
    public const OPERATION_CREATE_DIRECTORY = 'CREATE_DIRECTORY';
    public const OPERATION_DELETE = 'DELETE';
    public const OPERATION_DELETE_DIRECTORY = 'DELETE_DIRECTORY';
    public const OPERATION_MOVE = 'MOVE';
    public const OPERATION_RETRIEVE_METADATA = 'RETRIEVE_METADATA';
    public const OPERATION_COPY = 'COPY';
    public const OPERATION_READ = 'READ';
    public const OPERATION_SET_VISIBILITY = 'SET_VISIBILITY';

    public function operation(): string;
}
