<?php namespace Tberk\Laravel5Mailjet\Facades;

use Illuminate\Support\Facades\Facade;

class Mailjet  extends Facade{
    protected static function getFacadeAccessor() {
        return 'mailjet';
    }
}
