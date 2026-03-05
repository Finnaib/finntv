const https = require('https');

https.get('https://finntv.vercel.app/admin.html', (res) => {
    let data = '';
    res.on('data', chunk => { data += chunk; });
    res.on('end', () => {
        if (data.includes('Array Error')) {
            console.log("VERSION: RECENT (Has stats fallback logic)");
        } else {
            console.log("VERSION: OLDER (No fallback logic)");
        }
    });
});
