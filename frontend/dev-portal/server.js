const express = require("express");
const path = require("path");
const fs = require("fs");
const moment = require("moment");

const app = express();
const PORT = 3001;

// Middleware for logging requests
app.use((req, res, next) => {
  const timestamp = moment().format("YYYY-MM-DD HH:mm:ss");
  console.log(`[${timestamp}] ${req.method} ${req.url}`);
  next();
});

// Serve static files from the current directory
app.use(express.static(path.join(__dirname)));

// Serve dev-portal/style files at /dev-portal/style
app.use('/dev-portal/style', express.static(path.join(__dirname, 'style')));

// Explicitly serve files from components directory
app.use("/dev-portal/components", express.static(path.join(__dirname, "components")));

// Route for the root path - redirect to dashboard
app.get("/", (req, res) => {
  res.redirect("/developer");
});

// Route for the developer dashboard
app.get("/developer", (req, res) => {
  res.sendFile(path.join(__dirname, "pages", "index.html"));
});



app.get("/developer/game/:id/analytics", (req, res) => {
  const analyticsPage = path.join(__dirname, "pages", "game-metrics.html");
  console.log(`Checking for analytics page at: ${analyticsPage}`);

  if (fs.existsSync(analyticsPage)) {
    console.log("Analytics page found. Serving the file.");
    res.sendFile(analyticsPage);
  } else {
    console.log("Analytics page not found. Sending 404.");
    res.status(404).send("Analytics page not found.");
  }
});

// Route for registration page
app.get("/developer/register", (req, res) => {
  res.sendFile(path.join(__dirname, "pages", "register.html"));
});

// Route for login page
app.get("/developer/login", (req, res) => {
  res.sendFile(path.join(__dirname, "pages", "login.html"));
});



// Route for games list
app.get("/developer/games", (req, res) => {
  res.sendFile(path.join(__dirname, "pages", "games.html"));
});

// Route for upload game
app.get("/developer/upload", (req, res) => {
  res.sendFile(path.join(__dirname, "pages", "upload.html"));
});

// Route for test game
app.get("/developer/test-game", (req, res) => {
  res.sendFile(path.join(__dirname, "pages", "test-game.html"));
});

// Route for developer settings
app.get("/developer/settings", (req, res) => {
  res.sendFile(path.join(__dirname, "pages", "settings.html"));
});

// Route for developer profile page
app.get("/developer/profile", (req, res) => {
  res.sendFile(path.join(__dirname, "pages", "profile.html"));
});

// Route for individual game management
app.get("/developer/game/:id", (req, res) => {
  // Check if a specific game page exists
  const gamePage = path.join(__dirname, "pages", "game.html");

  if (fs.existsSync(gamePage)) {
    res.sendFile(gamePage);
  } else {
    // If no specific game page exists, send the games list page
    res.sendFile(path.join(__dirname, "pages", "games.html"));
  }
});

// Route for game analytics
app.get("/developer/game/:id/analytics", (req, res) => {
  // Check if analytics page exists
  const analyticsPage = path.join(__dirname, "pages", "analytics.html");

  if (fs.existsSync(analyticsPage)) {
    res.sendFile(analyticsPage);
  } else {
    // If no analytics page exists yet, redirect to game management
    res.redirect(`/developer/game/${req.params.id}`);
  }
});

// Route for game editing
app.get("/developer/game/:id/edit", (req, res) => {
  const editPage = path.join(__dirname, "pages", "edit.html");
  if (fs.existsSync(editPage)) {
    res.sendFile(editPage);
  } else {
    // If edit page doesn't exist yet, redirect to upload page
    res.sendFile(path.join(__dirname, "pages", "upload.html"));
  }
});

// Route for game preview
app.get("/developer/game/:id/preview", (req, res) => {
  const previewPage = path.join(__dirname, "pages", "preview.html");
  if (fs.existsSync(previewPage)) {
    res.sendFile(previewPage);
  } else {
    // If preview page doesn't exist yet
    res.status(404).send("Preview page not found. We're working on it!");
  }
});

// Route for managing game versions
app.get("/developer/game/:id/manage", (req, res) => {
  const managePage = path.join(__dirname, "pages", "manage-game.html");
  if (fs.existsSync(managePage)) {
    res.sendFile(managePage);
  } else {
    // Fallback if the manage page doesn't exist yet
    res.status(404).send("Manage page not found.");
  }
});


// Fallback route for all other developer requests
app.get("/developer/*", (req, res) => {
  res.redirect("/developer");
});

app.get("/developer/game/:gameId/metrics", (req, res) =>
  res.sendFile(path.join(__dirname, "pages/game-metrics.html")),
);

// Error handler middleware
app.use((err, req, res, next) => {
  console.error(`[ERROR] ${err.stack}`);
  res.status(500).send("Something broke!");
});

// Start the server
app.listen(PORT, () => {
  console.log(`Developer portal server running on http://localhost:${PORT}`);
  console.log(`Server started at: ${moment().format("YYYY-MM-DD HH:mm:ss")}`);
  console.log("Available routes:");
  console.log("  - /developer (Dashboard)");
  console.log("  - /developer/login (Login page)");
  console.log("  - /developer/register (Registration page)");
  console.log("  - /developer/games (Games list)");
  console.log("  - /developer/upload (Upload new game)");
  console.log("  - /developer/test-game (Test Game page)");
  console.log("  - /developer/game/:id (Individual game management)");
});
