document.addEventListener("DOMContentLoaded", function() {
    var player = document.getElementById("player"),
        list = document.getElementById("list"),
        title = document.getElementById("ch-title"),
        search = document.getElementById("search"),
        channels = [], filtered = [];

    // Ensure HLS support for PS Vita NetFront browser
    function playStream(url) {
        if (player.canPlayType('application/vnd.apple.mpegurl')) {
            player.src = url;
            player.load();
            player.play().catch(function(e) { console.log(e); });
        } else {
            // Revert to direct source as fallback
            player.innerHTML = '<source src=\"' + url + '\" type=\"application/x-mpegURL\">';
            player.load();
            player.play().catch(function(e) { console.log(e); });
        }
    }

    function init() {
        var m3u = new URLSearchParams(window.location.search).get("m3u");
        if (m3u) fetchM3U(m3u);
    }

    search.oninput = function() {
        var query = search.value.toLowerCase();
        filtered = channels.filter(function(c) {
            return (c.title + c.group).toLowerCase().indexOf(query) !== -1;
        });
        render();
    };

    function fetchM3U(url) {
        list.innerHTML = "<div class='msg'>Syncing...</div>";
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
                cur = { title: "Unknown", group: "Live", url: "" };
                var g = l.match(/group-title=\"([^\"]+)\"/);
                if (g) cur.group = g[1];
                var p = l.split(",");
                if (p.length > 1) cur.title = p[p.length-1].trim();
            } else if (l.indexOf("http") === 0 && cur) {
                cur.url = l; channels.push(cur); cur = null;
            }
        }
        filtered = channels; render();
    }

    function render() {
        list.innerHTML = "";
        var frag = document.createDocumentFragment();
        filtered.slice(0, 300).forEach(function(c) {
            var el = document.createElement("div");
            el.className = "card";
            el.innerHTML = "<span class='card-name'>" + c.title + "</span><span class='card-grp'>" + c.group + "</span>";
            el.onclick = function() {
                var all = document.querySelectorAll(".card");
                for(var j=0; j<all.length; j++) all[j].classList.remove("active");
                el.classList.add("active");
                title.innerText = c.title;
                playStream(c.url);
            };
            frag.appendChild(el);
        });
        list.appendChild(frag);
    }
    init();
});
