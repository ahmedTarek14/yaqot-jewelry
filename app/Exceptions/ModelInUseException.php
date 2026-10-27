<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when deleting a record that products still reference.
 * The admin should deactivate it instead.
 */
class ModelInUseException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('admin.messages.in_use'));
    }
}
