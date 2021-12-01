<?php

function arrayToJsonString($data)
{
    $temp = '{';
    $i = 0;
    foreach ($data as $key => $value) {
        $i === (count($data)-1) ? $temp .= "$key:$value" : $temp .= "$key:$value, ";
        $i++;
    }
    $temp .= '}';

    return $temp;
}
