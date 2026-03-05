const https = require('https');

const data = new URLSearchParams({
    'username': 'bobtest3',
    'password': 'bobtest3pass',
    'max_connections': 5,
    'exp_date': '2026-10-10',
    'action': 'create_user',
    'auth_user': 'shoaibwwe01@gmail.com',
    'auth_pass': 'Fatima786@'
}).toString();

const req = https.request({
    hostname: 'finntv.vercel.app',
    path: '/api/admin_actions.php',
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Content-Length': data.length
    }
}, (res) => {
    let rawData = '';
    res.on('data', (chunk) => { rawData += chunk; });
    res.on('end', () => { console.log(rawData); });
});

req.on('error', (e) => {
    console.error(e);
});
req.write(data);
req.end();
