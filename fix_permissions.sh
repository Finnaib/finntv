#!/bin/bash
# Fix permissions for data directory in Docker container

echo "🔧 Fixing data directory permissions..."

# Get container name
CONTAINER_NAME=$(sudo docker ps --format '{{.Names}}' | grep -E 'xtream_server|finntv' | head -n 1)

if [ -z "$CONTAINER_NAME" ]; then
    echo "❌ No container found"
    exit 1
fi

echo "Container: $CONTAINER_NAME"

# Fix permissions inside container
echo "Setting permissions..."
sudo docker exec $CONTAINER_NAME chown -R www-data:www-data /var/www/html/data
sudo docker exec $CONTAINER_NAME chmod -R 755 /var/www/html/data

# Verify
echo ""
echo "Verifying permissions..."
sudo docker exec $CONTAINER_NAME ls -la /var/www/html/data/

echo ""
echo "✅ Permissions fixed!"
echo ""
echo "Now rebuild data.json:"
echo "  sudo docker exec $CONTAINER_NAME php /var/www/html/build_data.php"
  