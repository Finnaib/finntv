#!/bin/bash
# Debug TiviMate Live Channels Issue

echo "=========================================="
echo "🔍 Debugging Live Channels in TiviMate"
echo "=========================================="
echo ""

CONTAINER_NAME=$(sudo docker ps --format '{{.Names}}' | grep -E 'xtream_server|finntv' | head -n 1)

if [ -z "$CONTAINER_NAME" ]; then
    echo "❌ No container found"
    exit 1
fi

echo "Container: $CONTAINER_NAME"
echo ""

# Check data.json content
echo "1. Checking data.json structure..."
sudo docker exec $CONTAINER_NAME php -r '
$data = json_decode(file_get_contents("/var/www/html/data/data.json"), true);
echo "Live Streams: " . count($data["live_streams"]) . "\n";
echo "Live Categories: " . count($data["live_categories"]) . "\n";
echo "VOD Streams: " . count($data["vod_streams"]) . "\n";
echo "VOD Categories: " . count($data["vod_categories"]) . "\n";
echo "Series: " . count($data["series"]) . "\n";
echo "\nFirst Live Stream:\n";
if (count($data["live_streams"]) > 0) {
    print_r($data["live_streams"][0]);
} else {
    echo "NO LIVE STREAMS FOUND!\n";
}
'

echo ""
echo "2. Testing API endpoint directly..."
curl -s "http://localhost/player_api.php?username=finn&password=finn123&action=get_live_streams" | head -c 500

echo ""
echo ""
echo "3. Checking M3U files..."
sudo docker exec $CONTAINER_NAME ls -lh /var/www/html/m3u/

echo ""
echo "=========================================="
echo "If live_streams is 0, the issue is in parsing."
echo "If live_streams has data but API returns empty, it's a PHP issue."
echo "=========================================="
