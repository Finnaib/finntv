<?php

$url = "http://mhav1.com:80/live/ht2990/742579548/3601.ts";

function test_connection($url, $use_ua = false) {
    echo "Testing connection to: $url\n";
    echo "User-Agent: " . ($use_ua ? "Yes" : "No") . "\n";
    
    $opts = [];
    if ($use_ua) {
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: VLC/3.0.16 LibVLC/3.0.16\r\n" .
                            "Accept: */*\r\n" .
                            "Connection: close\r\n"
            ]
        ];
    }
    
    $context = stream_context_create($opts);
    
    $start = microtime(true);
    $fp = @fopen($url, 'rb', false, $context);
    $end = microtime(true);
    
    if ($fp) {
        $meta = stream_get_meta_data($fp);
        echo "SUCCESS! Connected in " . round($end - $start, 4) . "s.\n";
        // echo "Headers: " . print_r($meta['wrapper_data'], true) . "\n";
        $data = fread($fp, 1024);
        echo "Read " . strlen($data) . " bytes.\n";
        fclose($fp);
        return true;
    } else {
        echo "FAILED! Could not open stream.\n";
        $error = error_get_last();
        if ($error) {
            echo "Error: " . $error['message'] . "\n";
        }
        return false;
    }
}

echo "--- Test 1: Basic fopen (No Headers) ---\n";
$res1 = test_connection($url, false);

echo "\n--- Test 2: fopen with User-Agent ---\n";
$res2 = test_connection($url, true);

if (!$res1 && $res2) {
    echo "\nCONCLUSION: Provider BLOCKS requests without User-Agent. Fix confirmed.\n";
} elseif ($res1) {
    echo "\nCONCLUSION: Provider DOES NOT block basic requests. Issue might be something else (e.g. Vercel IP block).\n";
} else {
    echo "\nCONCLUSION: Both failed. Provider might be down or blocking this IP entirely.\n";
}
