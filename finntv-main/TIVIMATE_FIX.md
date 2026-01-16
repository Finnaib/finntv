# TiviMate Not Showing Channels - Quick Fix Guide (Docker VPS)

## ✅ Status Check
- Data: **6,778 live streams** and **118 categories** ✓
- Deployment: **Docker on VPS** ✓
- Config: Dynamic base_url, proxy mode ✓

## 🔧 Most Common Issues & Fixes

### 1. Wrong URL Format in TiviMate ⚠️

**WRONG:**
- ❌ `http://your-ip/player_api.php`
- ❌ `http://your-ip/`  (trailing slash)
- ❌ `http://your-ip:80/`

**CORRECT:**
- ✅ `http://your-ip` (NO trailing slash, NO /player_api.php)

**Example:**
```
Connection Type: Xtream Codes API
Server URL: http://123.45.67.89
Username: finn
Password: finn123
```

### 2. Data Not Loaded in Container

If the container doesn't have access to `data/data.json`:

```bash
# On your VPS, run:
docker exec xtream_server ls -lh /var/www/html/data/data.json

# If file doesn't exist or is empty, rebuild it:
docker exec xtream_server php /var/www/html/build_data.php
```

### 3. Firewall Blocking Port 80

```bash
# Check if port 80 is open:
sudo ufw status

# If blocked, allow it:
sudo ufw allow 80/tcp
```

### 4. TiviMate Cache Issues

In TiviMate app:
1. Go to **Settings → Playlists**
2. Long-press your playlist → **Remove**
3. Go to **Settings → General → Clear cache**
4. Restart TiviMate
5. Add playlist again with correct URL format

### 5. Initial Sync Takes Time

With 6,778 channels, TiviMate needs **1-3 minutes** for initial sync:
- Don't close the app during first sync
- You'll see "Loading..." or spinning icon
- Wait until it completes

## 🧪 Diagnostic Steps

### Step 1: Run Diagnostic Script on VPS

```bash
# Upload diagnose_tivimate.sh to your VPS, then:
chmod +x diagnose_tivimate.sh
./diagnose_tivimate.sh
```

This will test:
- Docker container status
- API endpoints
- Data file existence
- Port accessibility

### Step 2: Test in Browser

Open in your browser:
```
http://YOUR_VPS_IP/test_tivimate.php
```

All tests should show **green checkmarks** ✓

### Step 3: Test API Manually

```bash
# Replace YOUR_VPS_IP with your actual IP
curl "http://YOUR_VPS_IP/player_api.php?username=finn&password=finn123&action=get_live_categories"
```

Should return JSON with categories.

## 📋 Quick Checklist

Before contacting support, verify:

- [ ] Docker container is running: `docker ps | grep xtream_server`
- [ ] Port 80 is accessible: `curl http://localhost/`
- [ ] data.json exists: `docker exec xtream_server ls -lh /var/www/html/data/data.json`
- [ ] API responds: `curl "http://YOUR_IP/player_api.php?username=finn&password=finn123"`
- [ ] TiviMate URL has NO trailing slash
- [ ] TiviMate URL does NOT include `/player_api.php`
- [ ] Waited 2+ minutes for initial sync
- [ ] Cleared TiviMate cache

## 🚀 If Everything Else Fails

### Rebuild Docker Container

```bash
cd /path/to/xtream_server
docker-compose down
docker-compose up -d --build
```

### Rebuild Data Cache

```bash
docker exec xtream_server php /var/www/html/build_data.php
docker-compose restart
```

### Check Container Logs

```bash
docker logs -f xtream_server
```

Look for PHP errors or warnings.

## 📱 Correct TiviMate Setup (Step-by-Step)

1. Open TiviMate
2. Go to **Settings** (gear icon)
3. Select **Playlists**
4. Click **Add Playlist** (+)
5. Choose **Xtream Codes API**
6. Enter:
   - **Name:** FinnTV (or any name)
   - **Server URL:** `http://YOUR_VPS_IP` (NO slash at end!)
   - **Username:** `finn`
   - **Password:** `finn123`
7. Click **Next**
8. Wait for sync (1-3 minutes)
9. Click **Done**

## 🔍 Still Not Working?

Run the diagnostic script and share the output:
```bash
./diagnose_tivimate.sh > diagnostic_output.txt
cat diagnostic_output.txt
```

Or test in browser and screenshot the results from:
```
http://YOUR_VPS_IP/test_tivimate.php
```
