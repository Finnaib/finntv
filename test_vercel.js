const https = require('https');

https.get('https://finntv.vercel.app/admin.html', (res) => {
    let data = '';
    res.on('data', chunk => { data += chunk; });
    res.on('end', () => {
        if (data.includes('Direct GitHub Sync')) {
            console.log("YES: Old UI is still present");
        } else {
            console.log("NO: Old UI is gone");
        }
    });
});
