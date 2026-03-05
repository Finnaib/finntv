const https = require('https');

const req = https.request({
    hostname: 'finntv.vercel.app',
    path: '/api/player_api.php?username=shoaibwwe01@gmail.com&password=Fatima786@&action=get_stats',
    method: 'GET'
}, (res) => {
    console.log('STATUS:', res.statusCode);
    let rawData = '';
    res.on('data', (chunk) => { rawData += chunk; });
    res.on('end', () => {
        console.log('BODY:', rawData);
    });
});
req.end();
