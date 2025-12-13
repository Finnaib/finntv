const live = require('../functions/live');

// Mock Event
function testPath(path) {
    console.log(`\nTesting Path: ${path}`);
    const res = live.handler({
        httpMethod: 'GET',
        path: path,
        headers: { host: 'localhost' }
    }).then(r => {
        console.log(`Status: ${r.statusCode}`);
        console.log(`Debug Extracted ID: ${r.headers['X-Debug-Extracted-ID']}`);
        console.log(`Location: ${r.headers['Location'] || 'NONE'}`);
    });
}

// Build Data first to ensure ID map exists
require('./build_data');

setTimeout(() => {
    testPath('/.netlify/functions/live/user/pass/24039.ts'); // Typical Netlify rewrite
    testPath('/live/user/pass/24039.m3u8'); // HLS request
    testPath('/24039.ts'); // Direct simple request
    testPath('/live/24039'); // No extension (Expected Fail or match nothing)
    testPath('/some/weird/path/24039.mk'); // Weird ext (Expected Fail)
}, 1000);
