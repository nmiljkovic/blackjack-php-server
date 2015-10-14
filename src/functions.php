<?php

namespace Blackjack;

function json_parse($string)
{
    $json = @json_decode($string, true);
    if (!$json) {
        throw new \RuntimeException('Unable to parse json string: '.json_last_error_msg());
    }

    return $json;
}


