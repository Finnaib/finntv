const fs = require('fs');
const path = require('path');

const publicDir = path.join(__dirname, 'public');
if (!fs.existsSync(publicDir)) {
    fs.mkdirSync(publicDir, { recursive: true });
}

// Copy static HTML files
['index.html', 'admin.html', 'user_portal.html'].forEach(file => {
    const src = path.join(__dirname, file);
    if (fs.existsSync(src)) {
        fs.copyFileSync(src, path.join(publicDir, file));
    }
});

// Copy static asset directories
['player', 'm3u'].forEach(dir => {
    const src = path.join(__dirname, dir);
    if (fs.existsSync(src)) {
        fs.cpSync(src, path.join(publicDir, dir), { recursive: true });
    }
});

console.log('Public build output directory successfully generated.');
