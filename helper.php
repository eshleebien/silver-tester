<?php

function getFormToken($content)
{
    $_token = '';
    preg_match('/(?!.*_token.*value=\")(\w{40}(?="))/', $content, $_token);

    return $_token[0];
}

function getCodeFromUri($uri)
{
    preg_match('/(?!=)([A-Z0-9])\w+/', $uri, $code);

    return $code[0];

}
