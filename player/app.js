document.addEventListener("DOMContentLoaded", function() {
    var player = document.getElementById("player"),
        list = document.getElementById("list"),
        title = document.getElementById("ch-title"),
        search = document.getElementById("search"),
        channels = [], filtered = [];

    function init() {
        var m3u = new URLSearchParams(window.location.search).get("m3u");
        if (m3u) fetch(m3u);
    }

    search.oninput = function() {
        var query = search.value.toLowerCase();
        filtered = channels.filter(c => (c.title + c.group).toLowerCase().includes(query));
        render();
    };

    function fetch(url) {
        list.innerHTML = "<div class='msg'>Syncing...</div>";
        var xhr = new XMLHttpRequest();
        xhr.open("GET", url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) parse(xhr.responseText);
        };
        xhr.onerror = function() { list.innerHTML = "<div class='msg'>Network Block.</div>"; };
        xhr.send();
    }

    function parse(txt) {
        channels = [];
        var lines = txt.split("\n"), cur = null;
        for (var i=0; i<lines.length; i++) {
            var l = lines[i].trim();
            if (l.indexOf("#EXTINF:") === 0) {
                cur = { title: "Unknown", group: "Live", url: "" };
                var g = l.match(/group-title="([^"]+)"/);
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
        filtered.slice(0, 500).forEach(c => {
            var el = document.createElement("div");
            el.className = "card";
            el.innerHTML = "<span class='card-name'>" + c.title + "</span><span class='card-grp'>" + c.group + "</span>";
            el.onclick = function() {
                document.querySelectorAll(".card").forEach(x => x.classList.remove("active"));
                el.classList.add("active");
                title.innerText = c.title;
                player.src = c.url; player.load(); player.play().catch(()=>{});
            };
            frag.appendChild(el);
        });
        list.appendChild(frag);
    }
    init();
});
