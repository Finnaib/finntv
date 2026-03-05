const https = require('https');
const fs = require('fs');

https.get('https://finntv.vercel.app/admin.html', (res) => {
    let data = '';
    res.on('data', chunk => { data += chunk; });
    res.on('end', () => {
        fs.writeFileSync('admin_remote_correct.html', data, 'utf-8');
    });
});
