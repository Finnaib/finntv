# Deployment Guide: Streaming Server with Proxy (Docker)

You can run this on **any Linux server** (GCP, AWS, DigitalOcean, or your local machine).

## Prerequisites
1.  **Docker** and **Docker Compose** installed.
    - Ubuntu: `sudo apt update && sudo apt install docker.io docker-compose`

## Installation Steps
1.  **Upload Files**: Copy all files in the `xtream_server` folder to your server (e.g., `/opt/finntv`).
2.  **Permission Check**: Ensure `config.php` and `m3u/` folder are readable.
    - Run: `chmod -R 777 data` (Essential for cache generation!)
    - Run: `chmod -R 755 .`
3.  **Start Server**:
    - Run: `docker-compose up -d --build`
4.  **Verify**:
    - Check if running: `docker ps`
    - Open browser: `http://YOUR_SERVER_IP/`

## Configuration
- **Edit Streams**: Put `.m3u` files in the `m3u/` folder.
- **Edit Settings**: Open `config.php`.
    - `base_url`: Set this to `http://YOUR_SERVER_IP/` (Important!).
    - `stream_mode`: Set to `'proxy'` (Secure) or `'redirect'` (Fast).

## Troubleshooting
- **Logs**: `docker-compose logs -f`
- **Restart**: `docker-compose restart`
- **Stop**: `docker-compose down`
