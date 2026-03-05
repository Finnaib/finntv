const https = require('https');

const user = encodeURIComponent('shoaibwwe01@gmail.com');
const pass = encodeURIComponent('Fatima786@');
const t = Date.now();

const url = `https://finntv.vercel.app/player_api.php?username=${user}&password=${pass}&action=get_users&_t=${t}`;

https.get(url, (res) => {
    let data = '';
    res.on('data', chunk => { data += chunk; });
    res.on('end', () => {
        console.log("STATUS:", res.statusCode);
        console.log("BODY:", data);
        try {
            const json = JSON.parse(data);
            console.log("IS_ARRAY:", Array.isArray(json));
        } catch (e) {
            console.log("JSON_ERROR:", e.message);
        }
    });
});
