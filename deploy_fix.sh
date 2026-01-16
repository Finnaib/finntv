#!/bin/bash
# Deploy the TiviMate fix to Docker container

echo "🚀 Deploying TiviMate compatibility fix..."
echo ""

CONTAINER_NAME=$(sudo docker ps --format '{{.Names}}' | grep -E 'xtream_server|finntv' | head -n 1)

if [ -z "$CONTAINER_NAME" ]; then
    echo "❌ No container found"
    exit 1
fi

echo "Container: $CONTAINER_NAME"
echo ""

# Copy the fixed file to container
echo "1. Copying fixed player_api.php to container..."
sudo docker cp core/player_api.php $CONTAINER_NAME:/var/www/html/core/player_api.php

# Set permissions
echo "2. Setting permissions..."
sudo docker exec $CONTAINER_NAME chown www-data:www-data /var/www/html/core/player_api.php
sudo docker exec $CONTAINER_NAME chmod 644 /var/www/html/core/player_api.php

# Restart container to clear any PHP opcache
echo "3. Restarting container..."
sudo docker-compose restart

echo ""
echo "✅ Fix deployed!"
echo ""
echo "Now test the API:"
echo "  curl 'http://localhost/player_api.php?username=finn&password=finn123&action=get_live_streams' | head -c 500"
echo ""
echo "Then refresh TiviMate:"
echo "  Settings → Playlists → Long-press → Update playlist"
