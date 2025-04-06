const express = require("express");
const path = require("path");
const { createProxyMiddleware } = require('http-proxy-middleware');
const app = express();
const port = 3000;

// Serve static files (CSS, images, etc.)
app.use("/assets", express.static(path.join(__dirname, "assets")));
app.use("/style", express.static(path.join(__dirname, "style")));
app.use("/js", express.static(path.join(__dirname, "js"))); // Serve the js directory
app.use("/components", express.static(path.join(__dirname, "components"), {
  setHeaders: (res, filePath) => {
    if (filePath.endsWith('.js')) {
      res.setHeader('Content-Type', 'application/javascript');
    }
  }
})); // Serve the components directory

// Proxy API requests to backend (Laravel) on port 8000
app.use('/api', createProxyMiddleware({
  target: 'http://localhost:8000',
  changeOrigin: true,
  pathRewrite: { '^/api': '/api' },
}));

// Custom slugs and routes
app.get("/", (req, res) =>
  res.sendFile(path.join(__dirname, "pages/home.html")),
);
app.get("/auth", (req, res) =>
  res.sendFile(path.join(__dirname, "pages/auth.html")),
);
app.get("/about", (req, res) =>
  res.sendFile(path.join(__dirname, "pages/about.html")),
);
app.get("/catalog", (req, res) =>
  res.sendFile(path.join(__dirname, "pages/catalog.html")),
);
app.get("/leaderboard", (req, res) =>
  res.sendFile(path.join(__dirname, "pages/leaderboard.html")),
);
app.get("/forum-discussions", (req, res) =>
  res.sendFile(path.join(__dirname, "pages/forum-discussions.html")),
);
app.get("/profile/:username", (req, res) =>
  res.sendFile(path.join(__dirname, "pages/profile.html")),
);
app.get("/game/:gameId", (req, res) =>
  res.sendFile(path.join(__dirname, "pages/game.html")),
);
app.get("/community", (req, res) =>
  res.sendFile(path.join(__dirname, "pages/community.html")),
);
app.get("/chat", (req, res) =>
  res.sendFile(path.join(__dirname, "pages/chat.html"))
);
app.get('/groupsettings.html', (req, res) => {
  res.sendFile(path.join(__dirname, 'groupsettings.html'));
});

app.get('/settings', (req, res) => {
  res.sendFile(path.join(__dirname, 'pages/settings.html'));
});

app.get("/search", (req, res) =>
  res.sendFile(path.join(__dirname, "pages/search.html"))
);

app.get("/play-game", (req, res) => {
  res.sendFile(path.join(__dirname, "pages/play-game.html"));
});

app.listen(port, () => {
  console.log(`Server running on http://localhost:${port}`);
});
