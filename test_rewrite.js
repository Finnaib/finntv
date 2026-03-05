const https = require('https');

const req = https.request({
    hostname: 'finntv.vercel.app',
    path: '/player_api.php?username=shoaibwwe01@gmail.com&password=Fatima786@&action=get_users',
    method: 'GET'
}, (res) => {
    console.log('STATUS:', res.statusCode);
    let rawData = '';
    res.on('data', (chunk) => { rawData += chunk; });
    res.on('end', () => { console.log('BODY:', rawData); });
});

req.on('error', (e) => {
    console.error(e);
});
req.end();
