<?php
namespace App\Exception;

class UsernameAlreadyExistsException extends \Exception
{
    protected $message = 'Username already taken';
}