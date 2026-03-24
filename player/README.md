# Vita Live TV

A custom IPTV application specifically designed for the **PS Vita Web Browser** to watch live TV, featuring a native UI layout that fits perfectly on the 544p OLED/LCD screen.

## Features:
- **Responsive Landscape Design**: Perfectly tuned for 960x544 horizontal orientation and touch interaction.
- **Glassmorphism Theme**: Inspired by the native PS Vita LiveArea aesthetics (vibrant rounded blues + dark backgrounds).
- **M3U Parser**: Instantly load any `.m3u` or `.m3u8` playlist text file you provide and format it into a selectable list.
- **Native HLS Stream Support**: Bypasses the need for complex un-supported media extensions (MSE). Binds `.m3u8` video streams directly to standard HTML5 video elements for native smooth playback.

## How to use:
1. **PHP Server Required**: To use the Vercel-style streaming proxy (required for `.ts` streams), you should use a PHP-capable server instead of Python. 
2. Open your terminal in the root directory and run: 
   ```cmd
   php -S 0.0.0.0:8080
   ```
   *Note*: If you only use Python (`python -m http.server`), the proxy will not work and some channels may not play.
3. Look up your computer's local IPv4 address (e.g. `192.168.1.5`).
4. On your **PS Vita**, open the **Browser** and navigate to your PC's IP and port, for example: `http://192.168.1.5:8080/`.
5. Enter a link to a raw M3U playlist file into the input box on the sidebar.
   - *Note*: Ensure the M3U link supports CORS so the PS Vita can fetch the contents, or use a local path!
6. Choose a live channel, tap play, and enjoy your IPTV streams directly on the Vita!

## Need custom M3Us?
For example, check out the community GitHub lists like `https://iptv-org.github.io/iptv/index.m3u` or use the script you wrote in the `india.m3u` conversation.
