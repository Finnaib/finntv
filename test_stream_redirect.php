<?php
// Test a specific stream URL from data.json
// This will help determine if the issue is with our redirect or the provider URLs

$test_stream_id = 356; // beIN Baby from earlier test
$test_url = "https://finntv.vercel.app/live/test/test/{$test_stream_id}.ts";

echo "Testing stream redirect: $test_url\n\n";

// Use curl to follow redirect and check final destination
$ch = curl_init($test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$redirect_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

echo "HTTP Status: $http_code\n";
echo "Redirected to: $redirect_url\n";
echo "Content-Type: $content_type\n\n";

if ($http_code !== 200) {
    echo "❌ Stream URL is NOT accessible (provider may be down or geo-blocked)\n";
} else {
    echo "✅ Stream URL is accessible\n";
}

curl_close($ch);
