export const config = {
    runtime: 'edge',
};

export default async function handler(req) {
    if (req.method === 'OPTIONS') {
        return new Response(null, {
            status: 200,
            headers: {
                'Access-Control-Allow-Origin': '*',
                'Access-Control-Allow-Methods': 'GET, OPTIONS',
                'Access-Control-Allow-Headers': '*',
            },
        });
    }

    try {
        const url = new URL(req.url);
        const targetBase64 = url.searchParams.get('url');
        
        if (!targetBase64) {
            return new Response('Missing URL parameter', { status: 400 });
        }
        
        const decodedUrl = atob(targetBase64);
        
        // Pass relevant headers, especially Range for seeking
        const fetchHeaders = new Headers();
        fetchHeaders.set('User-Agent', 'VLC/3.0.16 LibVLC/3.0.16');
        
        // Forward client IP to prevent provider blocks
        const clientIp = req.headers.get('x-forwarded-for') || req.headers.get('x-real-ip');
        if (clientIp) {
            fetchHeaders.set('X-Forwarded-For', clientIp);
            fetchHeaders.set('X-Real-IP', clientIp);
        }
        
        const range = req.headers.get('range');
        if (range) fetchHeaders.set('range', range);

        const response = await fetch(decodedUrl, {
            method: req.method,
            headers: fetchHeaders,
            redirect: 'follow'
        });

        const resHeaders = new Headers(response.headers);
        resHeaders.set('Access-Control-Allow-Origin', '*');
        
        // Fix missing content type if needed
        if (!resHeaders.has('content-type')) {
            if (decodedUrl.includes('.mkv')) resHeaders.set('content-type', 'video/x-matroska');
            else if (decodedUrl.includes('.avi')) resHeaders.set('content-type', 'video/x-msvideo');
            else resHeaders.set('content-type', 'video/mp4');
        }

        return new Response(response.body, {
            status: response.status,
            statusText: response.statusText,
            headers: resHeaders
        });
    } catch (err) {
        return new Response(`Proxy Error: ${err.message}`, { status: 500 });
    }
}
