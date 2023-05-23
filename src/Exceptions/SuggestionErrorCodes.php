<?php

namespace Suggestion\Exceptions;

class SuggestionErrorCodes
{
    const GENERIC_ERROR = 'SUGGESTION_001';
    const CONNECTION_ERROR = 'SUGGESTION_002';
    const SERVER_ERROR = 'SUGGESTION_003';
    const UNEXPECTED_ERROR = 'SUGGESTION_004';

    const BAD_REQUEST = 'SUGGESTION_400';
    const UNAUTHORIZED = 'SUGGESTION_401';
    const FORBIDDEN = 'SUGGESTION_403';
    const NOT_FOUND = 'SUGGESTION_404';
    const SERVER_EXCEPTION = 'SUGGESTION_500';
}