#!/bin/bash
# Deploy the classification fix

echo "🚀 Deploying live channel classification fix..."
echo ""

CONTAINER_NAME=$(sudo docker ps --format '{{.Names}}' | grep -E 'xtream_server|finntv' | head -n 1)

# Copy fixed config
echo "1. Copying fixed config.php..."
sudo docker cp config.php $CONTAINER_NAME:/var/www/html/config.php
sudo docker exec $CONTAINER_NAME chown www-data:www-data /var/www/html/config.php

# Rebuild data.json with new classification
echo "2. Rebuilding data.json with correct classification..."
sudo docker exec $CONTAINER_NAME php /var/www/html/build_data.php

# Restart
echo "3. Restarting container..."
sudo docker-compose restart

echo ""
echo "✅ Fix deployed!"
echo ""
echo "Now refresh TiviMate:"
echo "  Settings → Playlists → Update playlist"
