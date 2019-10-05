<?php

namespace App\Exceptions;

/**
 * Created by PhpStorm.
 * User: Mainul
 * Date: 05/10/2019
 * Time: 02:58 م
 */


class ValidationException extends \Exception {
    public function __construct ($message, $code = 422) {
        throw new \Exception($message, 422);
    }
}