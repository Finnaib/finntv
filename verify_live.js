const https = require('https');

https.get('https://finntv.vercel.app/admin.html', (res) => {
    let data = '';
    res.on('data', chunk => { data += chunk; });
    res.on('end', () => {
        if (data.includes('AbortController')) {
            console.log("VERSION: NEW (Has timeout logic)");
        } else if (data.includes('Direct GitHub Sync')) {
            console.log("VERSION: VERY OLD (Has GitHub UI)");
        } else {
            console.log("VERSION: INTERMEDIATE (No GitHub UI, but no timeout)");
        }

        // Also check if LoadUsers exist
        if (data.includes('async function loadUsers()')) {
            console.log("loadUsers found");
        } else {
            console.log("loadUsers NOT found");
        }
    });
});
