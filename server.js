const express = require('express');
const app = express();
const path = require('path');

// ...existing code...

app.get('/settings', (req, res) => {
  res.sendFile(path.join(__dirname, 'frontend/user-portal/pages/settings.html'));
});

// ...existing code...

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Server is running on port ${PORT}`);
});