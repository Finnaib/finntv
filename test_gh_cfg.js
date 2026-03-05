const https = require('https');

https.get('https://raw.githubusercontent.com/Finnaib/finntv/main/config.php', (res) => {
    let data = '';
    res.on('data', chunk => { data += chunk; });
    res.on('end', () => {
        let lines = data.split('\n');
        for (let i = 45; i < 75; i++) {
            if (lines[i] !== undefined) console.log(i + ": " + lines[i]);
        }
    });
});
