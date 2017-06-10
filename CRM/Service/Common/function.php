<?php
function curl_post($url, $fields)
{
    $ch = curl_init ();
    curl_setopt ( $ch, CURLOPT_URL, $url);
    curl_setopt ( $ch, CURLOPT_POST, 1 );
    // curl_setopt ( $ch, CURLOPT_HEADER, 0 );
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );
    // curl_setopt ($ch, CURLOPT_HTTPHEADER, array("Expect:"));
    $rs = curl_exec($ch);
    curl_close ($ch);

    return $rs;
}