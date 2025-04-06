const express = require('express');
const path = require('path');
const app = express();

const PORT = process.env.PORT || 3002;
const adminPortalPath = __dirname;

// Serve static files (js, css, images, etc.)
app.use(express.static(adminPortalPath));

// Serve pages (HTML files)
app.get('/', (req, res) => {
  res.sendFile(path.join(adminPortalPath, 'pages', 'admin-login.html'));
});

app.get('/dashboard', (req, res) => {
  res.sendFile(path.join(adminPortalPath, 'pages', 'dashboard.html'));
});

// Fallback for other HTML pages in /pages
app.get('/:page', (req, res) => {
  res.sendFile(path.join(adminPortalPath, 'pages', req.params.page));
});

app.listen(PORT, () => {
  console.log(`Admin portal running at http://localhost:${PORT}`);
});