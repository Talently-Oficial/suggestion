<?php

namespace Suggestion\Facades;

use Illuminate\Support\Facades\Facade;
use Suggestion\SuggestionClient;

class Suggestion extends Facade
{

    protected static function getFacadeAccessor(): string
    {
        return SuggestionClient::class;
    }

}