#!/bin/bash
# TiviMate Docker Diagnostic Script
# Run this on your VPS to diagnose TiviMate issues

echo "=========================================="
echo "🔍 TiviMate Docker Diagnostic Tool"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if Docker is running
echo "1. Checking Docker status..."
if sudo docker ps > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Docker is running${NC}"
else
    echo -e "${RED}✗ Docker is not running${NC}"
    exit 1
fi
echo ""

# Check if container is running
echo "2. Checking xtream_server container..."
CONTAINER_NAME=$(sudo docker ps --format '{{.Names}}' | grep -E 'xtream_server|finntv')
if [ -n "$CONTAINER_NAME" ]; then
    echo -e "${GREEN}✓ Container '$CONTAINER_NAME' is running${NC}"
    sudo docker ps | grep -E 'xtream_server|finntv'
else
    echo -e "${RED}✗ No xtream_server or finntv container is running${NC}"
    echo "Try: sudo docker-compose up -d"
    exit 1
fi
echo ""

# Check container logs for errors
echo "3. Checking recent container logs..."
echo -e "${YELLOW}Last 10 log lines:${NC}"
sudo docker logs --tail 10 $CONTAINER_NAME
echo ""

# Check if port 80 is accessible
echo "4. Testing port 80 accessibility..."
if curl -s http://localhost/ > /dev/null; then
    echo -e "${GREEN}✓ Port 80 is accessible locally${NC}"
else
    echo -e "${RED}✗ Port 80 is not accessible${NC}"
    echo "Check firewall settings"
fi
echo ""

# Get server IP
echo "5. Detecting server IP addresses..."
echo -e "${YELLOW}Server IPs:${NC}"
hostname -I
echo ""

# Test API endpoints
echo "6. Testing API endpoints..."
TEST_USER="finn"
TEST_PASS="finn123"

echo "Testing authentication..."
AUTH_RESPONSE=$(curl -s "http://localhost/player_api.php?username=$TEST_USER&password=$TEST_PASS")
if echo "$AUTH_RESPONSE" | grep -q '"auth":1'; then
    echo -e "${GREEN}✓ Authentication successful${NC}"
else
    echo -e "${RED}✗ Authentication failed${NC}"
    echo "Response: $AUTH_RESPONSE"
fi
echo ""

echo "Testing live categories..."
CAT_RESPONSE=$(curl -s "http://localhost/player_api.php?username=$TEST_USER&password=$TEST_PASS&action=get_live_categories")
CAT_COUNT=$(echo "$CAT_RESPONSE" | grep -o "category_id" | wc -l)
if [ "$CAT_COUNT" -gt 0 ]; then
    echo -e "${GREEN}✓ Found $CAT_COUNT categories${NC}"
else
    echo -e "${RED}✗ No categories found${NC}"
    echo "Response preview: ${CAT_RESPONSE:0:200}"
fi
echo ""

echo "Testing live streams..."
STREAMS_RESPONSE=$(curl -s "http://localhost/player_api.php?username=$TEST_USER&password=$TEST_PASS&action=get_live_streams")
STREAM_COUNT=$(echo "$STREAMS_RESPONSE" | grep -o "stream_id" | wc -l)
if [ "$STREAM_COUNT" -gt 0 ]; then
    echo -e "${GREEN}✓ Found $STREAM_COUNT streams${NC}"
else
    echo -e "${RED}✗ No streams found${NC}"
    echo "Response preview: ${STREAMS_RESPONSE:0:200}"
fi
echo ""

# Check data.json
echo "7. Checking data.json file..."
if sudo docker exec $CONTAINER_NAME test -f /var/www/html/data/data.json; then
    FILE_SIZE=$(sudo docker exec $CONTAINER_NAME stat -c%s /var/www/html/data/data.json 2>/dev/null)
    echo -e "${GREEN}✓ data.json exists (Size: $FILE_SIZE bytes)${NC}"
else
    echo -e "${RED}✗ data.json not found${NC}"
    echo "Run: sudo docker exec $CONTAINER_NAME php /var/www/html/build_data.php"
fi
echo ""

# TiviMate Configuration Guide
echo "=========================================="
echo "📱 TiviMate Configuration"
echo "=========================================="
echo ""
echo "Use these settings in TiviMate:"
echo ""
echo "Connection Type: Xtream Codes API"
echo "Server URL: http://YOUR_VPS_IP"
echo "           (Replace YOUR_VPS_IP with one of the IPs shown above)"
echo "           (NO trailing slash, NO /player_api.php)"
echo "Username: $TEST_USER"
echo "Password: $TEST_PASS"
echo ""
echo -e "${YELLOW}Example URLs to try:${NC}"
for ip in $(hostname -I); do
    echo "  http://$ip"
done
echo ""

# Web-based tester
echo "=========================================="
echo "🌐 Web-based Tester"
echo "=========================================="
echo ""
echo "For detailed testing, open in browser:"
for ip in $(hostname -I); do
    echo "  http://$ip/test_tivimate.php"
done
echo ""

echo "=========================================="
echo "✅ Diagnostic Complete"
echo "=========================================="
