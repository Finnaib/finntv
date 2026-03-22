document.addEventListener("DOMContentLoaded", function() {
    var player = document.getElementById("video-player"),
        list = document.getElementById("channel-list"),
        title = document.getElementById("channel-name"),
        search = document.getElementById("search-input"),
        pagi = document.getElementById("pagination-container"),
        channels = [], filtered = [], cursor = 0, LIMIT = 50;

    function init() {
        var m3u = new URLSearchParams(window.location.search).get("m3u");
        if (m3u) fetchM3U(m3u);
    }

    search.oninput = function() {
        var query = search.value.toLowerCase();
        filtered = channels.filter(function(c) {
            return (c.t + c.g).toLowerCase().indexOf(query) !== -1;
        });
        cursor = 0; list.innerHTML = ""; render();
    };

    function fetchM3U(url) {
        list.innerHTML = "<div style='padding:20px;text-align:center;'>Syncing...</div>";
        var xhr = new XMLHttpRequest();
        xhr.open("GET", url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) parse(xhr.responseText);
        };
        xhr.send();
    }

    function parse(txt) {
        channels = [];
        var lines = txt.split("\n"), cur = null;
        for (var i=0; i<lines.length; i++) {
            var l = lines[i].trim();
            if (l.indexOf("#EXTINF:") === 0) {
                cur = { t: "Unknown", g: "Live", u: "" };
                var g = l.match(/group-title=\"([^\"]+)\"/);
                if (g) cur.g = g[1];
                var p = l.split(",");
                if (p.length > 1) cur.t = p[p.length-1].trim();
            } else if (l.indexOf("http") === 0 && cur) {
                cur.u = l; channels.push(cur); cur = null;
            }
        }
        filtered = channels; list.innerHTML = ""; render();
    }

    function render() {
        var chunk = filtered.slice(cursor, cursor + LIMIT);
        var frag = document.createDocumentFragment();
        chunk.forEach(function(c) {
            var el = document.createElement("div");
            el.className = "v-card";
            el.innerHTML = "<span class='v-name'>" + c.t + "</span><span class='v-grp'>" + c.g + "</span>";
            el.onclick = function() {
                var all = document.querySelectorAll(".v-card");
                for(var j=0; j<all.length; j++) all[j].classList.remove("active");
                el.classList.add("active");
                title.innerText = c.t;
                player.src = c.u; player.load(); player.play().catch(function(){});
            };
            frag.appendChild(el);
        });
        list.appendChild(frag);
        cursor += LIMIT;
        pagi.innerHTML = "";
        if (cursor < filtered.length) {
            var btn = document.createElement("button");
            btn.id = "load-more-btn";
            btn.innerText = "Load More Channels (" + (filtered.length - cursor) + " more)";
            btn.onclick = render;
            pagi.appendChild(btn);
        }
    }
    init();
});
