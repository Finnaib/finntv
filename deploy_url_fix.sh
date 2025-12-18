#!/bin/bash
# Deploy TiviMate URL fix

echo "🚀 Deploying TiviMate URL fix..."

CONTAINER_NAME=$(sudo docker ps --format '{{.Names}}' | grep -E 'xtream_server|finntv' | head -n 1)

# Copy fixed config
sudo docker cp config.php $CONTAINER_NAME:/var/www/html/config.php
sudo docker exec $CONTAINER_NAME chown www-data:www-data /var/www/html/config.php

# Restart
sudo docker-compose restart

echo ""
echo "✅ Fix deployed!"
echo ""
echo "Test the fix:"
echo "  curl 'http://localhost/player_api.php?username=finn&password=finn123' | grep '\"url\"'"
echo ""
echo "Should show: \"url\": \"35.223.81.47\""
echo ""
echo "Then refresh TiviMate!"
