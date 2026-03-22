# Vita Live TV

A custom IPTV application specifically designed for the **PS Vita Web Browser** to watch live TV, featuring a native UI layout that fits perfectly on the 544p OLED/LCD screen.

## Features:
- **Responsive Landscape Design**: Perfectly tuned for 960x544 horizontal orientation and touch interaction.
- **Glassmorphism Theme**: Inspired by the native PS Vita LiveArea aesthetics (vibrant rounded blues + dark backgrounds).
- **M3U Parser**: Instantly load any `.m3u` or `.m3u8` playlist text file you provide and format it into a selectable list.
- **Native HLS Stream Support**: Bypasses the need for complex un-supported media extensions (MSE). Binds `.m3u8` video streams directly to standard HTML5 video elements for native smooth playback.

## How to use:
1. Since the PS Vita Web Browser restricts fetching local files directly, the easiest way to serve this app is to host it via a local development server on your PC. 
2. Open your terminal in this directory and run a local server, for example using Python:
   ```cmd
   python -m http.server 8080
   ```
   Or using Node.js:
   ```cmd
   npx serve -l 8080
   ```
3. Look up your computer's local IPv4 address (e.g. `192.168.1.5`).
4. On your **PS Vita**, open the **Browser** and navigate to your PC's IP and port, for example: `http://192.168.1.5:8080/`.
5. Enter a link to a raw M3U playlist file into the input box on the sidebar.
   - *Note*: Ensure the M3U link supports CORS so the PS Vita can fetch the contents, or use a local path!
6. Choose a live channel, tap play, and enjoy your IPTV streams directly on the Vita!

## Need custom M3Us?
For example, check out the community GitHub lists like `https://iptv-org.github.io/iptv/index.m3u` or use the script you wrote in the `india.m3u` conversation.
