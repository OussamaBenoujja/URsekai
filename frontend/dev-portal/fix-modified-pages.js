/**
 * Script to restore original navbar code in developer portal HTML pages
 * This script will undo the changes made by update-navbars.js
 */

const fs = require('fs');
const path = require('path');

// Path to the pages directory
const PAGES_DIR = path.join(__dirname, 'pages');

// Standard header HTML template
const HEADER_HTML = `<header>
    <div class="logo">URSEKAI Developer</div>
    <button id="theme-toggle" class="theme-toggle" title="Toggle Dark Mode">
        <i class="fas fa-moon"></i>
    </button>
    <nav>
        <ul>
            <li>
                <a href="/developer"
                    ><i class="fas fa-chart-line"></i> Dashboard</a
                >
            </li>
            <li>
                <a href="/developer/games"
                    ><i class="fas fa-gamepad"></i> My Games</a
                >
            </li>
            <li>
                <a href="/developer/upload"
                    ><i class="fas fa-cloud-upload-alt"></i> Upload
                    Game</a
                >
            </li>
            <li>
                <a href="/developer/test-game"
                    ><i class="fas fa-vial"></i> Test Game</a
                >
            </li>
            <li>
                <a href="/"
                    ><i class="fas fa-home"></i> Back to Main Site</a
                >
            </li>
            <li>
                <a href="#" id="logout-link"
                    ><i class="fas fa-sign-out-alt"></i> Logout</a
                >
            </li>
        </ul>
    </nav>
</header>`;

// Process each file in pages directory
fs.readdir(PAGES_DIR, (err, files) => {
  if (err) {
    console.error('Error reading directory:', err);
    return;
  }
  
  // Filter for HTML files
  const htmlFiles = files.filter(file => file.endsWith('.html'));
  console.log(`Found ${htmlFiles.length} HTML files to process`);
  
  // Process each file
  htmlFiles.forEach(filename => {
    const filePath = path.join(PAGES_DIR, filename);
    console.log(`Processing ${filename}...`);
    
    try {
      // Read file content
      let content = fs.readFileSync(filePath, 'utf8');
      let modified = false;
      
      // Check if there's a navbar placeholder
      if (content.includes('id="navbar-placeholder"')) {
        // Replace navbar placeholder with standard header HTML
        content = content.replace(/<!-- Navbar placeholder.*?<div id="navbar-placeholder"><\/div>/s, HEADER_HTML);
        modified = true;
      }
      
      // Remove the navbar script tag if it exists
      if (content.includes('navbar.js')) {
        content = content.replace(/\n?\s*<!-- Load the shared navbar component -->\s*\n?\s*<script src="\.\.\/components\/navbar\.js"><\/script>/g, '');
        modified = true;
      }
      
      if (modified) {
        // Write updated content back to file
        fs.writeFileSync(filePath, content, 'utf8');
        console.log(`  Restored ${filename} successfully`);
      } else {
        console.log(`  No modifications needed for ${filename}`);
      }
      
    } catch (error) {
      console.error(`  Error restoring ${filename}:`, error);
    }
  });
  
  console.log('All files processed!');
});