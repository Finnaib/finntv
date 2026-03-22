document.addEventListener("DOMContentLoaded", function () {
  var urlInput = document.getElementById("m3u-url");
  var loadBtn = document.getElementById("load-btn");
  var list = document.getElementById("channel-list");
  var count = document.getElementById("channel-count");
  var video = document.getElementById("video-player");
  var name = document.getElementById("channel-name");
  var search = document.getElementById("search-input");
  var channels = [];
  var filtered = [];
  var isVita = navigator.userAgent.indexOf(" Vita ") !== -1;

  function init() {
    var params = new URLSearchParams(window.location.search);
    var m3u = params.get("m3u");
    if (m3u) {
      urlInput.value = m3u;
      fetchM3U(m3u);
    }
  }

  loadBtn.onclick = function() { fetchM3U(urlInput.value); };

  search.oninput = function() {
    var q = search.value.toLowerCase();
    filtered = channels.filter(function(c) {
      return (c.title + c.group).toLowerCase().indexOf(q) !== -1;
    });
    render();
  };

  function fetchM3U(url) {
    list.innerHTML = "Loading...";
    var xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4 && xhr.status === 200) {
        parse(xhr.responseText);
      }
    };
    xhr.send();
  }

  function parse(txt) {
    channels = [];
    var lines = txt.split("\n");
    var cur = null;
    for (var i = 0; i < lines.length; i++) {
        var l = lines[i].trim();
        if (l.indexOf("#EXTINF:") === 0) {
            cur = { title: "Unknown", group: "Live", url: "" };
            var g = l.match(/group-title="([^"]+)"/);
            if (g) cur.group = g[1];
            var parts = l.split(",");
            if (parts.length > 1) cur.title = parts[parts.length-1].trim();
        } else if (l.indexOf("http") === 0 && cur) {
            cur.url = l;
            channels.push(cur);
            cur = null;
        }
    }
    filtered = channels;
    render();
  }

  function render() {
    count.innerText = filtered.length;
    list.innerHTML = "";
    var table = document.createElement("table");
    table.style.width = "100%";
    table.style.borderCollapse = "collapse";
    
    filtered.slice(0, 100).forEach(function(c) {
        var tr = document.createElement("tr");
        var td = document.createElement("td");
        td.style.padding = "10px";
        td.style.border = "1px solid #333";
        td.style.color = "#fff";
        td.innerText = c.title + " (" + c.group + ")";
        td.onclick = function() { play(c); };
        tr.appendChild(td);
        table.appendChild(tr);
    });
    list.appendChild(table);
  }

  function play(c) {
    name.innerText = c.title;
    video.src = c.url;
    video.load();
    video.play().catch(function(){});
    window.scrollTo(0,0);
  }

  init();
});
