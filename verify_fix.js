const https = require('https');

https.get('https://finntv.vercel.app/admin.html', (res) => {
    let data = '';
    res.on('data', chunk => { data += chunk; });
    res.on('end', () => {
        if (!data.includes('uptime')) {
            console.log("FIX DEPLOYED: uptime reference removed.");
        } else {
            console.log("OLD VERSION: uptime reference still exists.");
        }
    });
});
