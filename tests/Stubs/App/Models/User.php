<?php

namespace App\Models;

/**
 * Test stand-in for the host application's user model.
 *
 * The package's Logic::user() is typed against App\Models\User, so the test
 * suite needs the class to exist even though the host model is not part of it.
 */
class User
{
    public function belongsToAction(string $action): bool
    {
        return false;
    }
}
