# Deploy to Vercel with PHP Support

## Prerequisites

Your Vercel project now uses the `vercel-php` runtime to execute PHP files.

## Quick Deploy

```bash
# Install Vercel CLI (if not already installed)
npm i -g vercel

# Deploy
cd "c:\Users\Administrator\Downloads\finntv.atwebpages.com (3)"
vercel --prod
```

## What Changed

The `vercel.json` file now includes:

```json
{
  "functions": {
    "xtream_server/*.php": {
      "runtime": "vercel-php@0.6.0"
    }
  }
}
```

This tells Vercel to execute PHP files instead of serving them as static files.

## After Deployment

Test your API:

1. **Authentication**:
   ```
   https://finntv.vercel.app/player_api.php?username=test&password=test123
   ```
   Should return JSON with `"auth": 1`

2. **Channels**:
   ```
   https://finntv.vercel.app/player_api.php?username=test&password=test123&action=get_live_streams
   ```
   Should return array of channels

3. **Categories**:
   ```
   https://finntv.vercel.app/player_api.php?username=test&password=test123&action=get_live_categories
   ```
   Should return array of categories

## Configure IPTV App

- **Server**: `https://finntv.vercel.app/`
- **Username**: `test`
- **Password**: `test123`
- **Type**: Xtream Codes API

Channels should now load! 🎉
