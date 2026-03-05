const https = require('https');

const req = https.request({
    hostname: 'finntv.vercel.app',
    path: '/api/player_api.php?username=shoaibwwe01@gmail.com&password=Fatima786@&action=get_users',
    method: 'GET'
}, (res) => {
    console.log('STATUS:', res.statusCode);
    let rawData = '';
    res.on('data', (chunk) => { rawData += chunk; });
    res.on('end', () => {
        console.log('BODY_LENGTH:', rawData.length);
        try {
            const json = JSON.parse(rawData);
            console.log('JSON_COUNT:', json.length);
            console.log('FIRST:', json[0]);
        } catch (e) {
            console.log('JSON_ERROR:', e.message);
            console.log('PREVIEW:', rawData.substring(0, 200));
        }
    });
});
req.end();
