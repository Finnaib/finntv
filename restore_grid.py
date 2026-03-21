import os

filepath = r'c:\Users\Finnaib\Desktop\finntv\index.html'
with open(filepath, 'rb') as f:
    content = f.read()

# Define the full grid as we want it
full_grid = b"""        <div class="categories-grid">
            <div class="category-card" data-url="https://finntv.vercel.app/m3u/asia.m3u" data-name="Asia Channels">
                <div class="category-icon">\xf0\x9f\x93\xba</div>
                <div class="category-info">
                    <h3>Asia Channels</h3>
                    <p>Click to copy URL</p>
                </div>
            </div>

            <div class="category-card" data-url="https://finntv.vercel.app/m3u/egypt.m3u" data-name="Egypt Channels">
                <div class="category-icon">\xf0\x9f\x93\xba</div>
                <div class="category-info">
                    <h3>Egypt Channels</h3>
                    <p>Click to copy URL</p>
                </div>
            </div>

            <div class="category-card" data-url="https://finntv.vercel.app/m3u/india.m3u" data-name="India Channels">
                <div class="category-icon">\xf0\x9f\x93\xba</div>
                <div class="category-info">
                    <h3>India Channels</h3>
                    <p>Click to copy URL</p>
                </div>
            </div>

            <div class="category-card" data-url="https://finntv.vercel.app/m3u/world.m3u" data-name="World Channels">
                <div class="category-icon">\xf0\x9f\x93\xba</div>
                <div class="category-info">
                    <h3>World Channels</h3>
                    <p>Click to copy URL</p>
                </div>
            </div>

            <div class="category-card" data-url="https://finntv.vercel.app/m3u/indonesia.m3u" data-name="Indonesia Channels">
                <div class="category-icon">\xf0\x9f\x93\xba</div>
                <div class="category-info">
                    <h3>Indonesia Channels</h3>
                    <p>Click to copy URL</p>
                </div>
            </div>

            <div class="category-card" data-url="https://finntv.vercel.app/m3u/sport.m3u" data-name="Sport Channels">
                <div class="category-icon">\xe2\x9a\xbd</div>
                <div class="category-info">
                    <h3>Sport Channels</h3>
                    <p>Click to copy URL</p>
                </div>
            </div>
        </div>"""

# Replace the current messed up grid
# Identify the grid start and end
import re
new_content = re.sub(rb'\s+<div class="categories-grid">.*?</div>\s+</div>\s+</section>', 
                     b'\n' + full_grid + b'\n        </div>\n    </section>', 
                     content, flags=re.DOTALL)

if new_content != content:
    with open(filepath, 'wb') as f:
        f.write(new_content)
    print("Successfully restored categories grid.")
else:
    print("Regex failed to find categories grid.")
