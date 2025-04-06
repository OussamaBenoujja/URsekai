const fs = require('fs');
const path = require('path');

// Path to the index.html file
const INDEX_FILE = path.join(__dirname, 'pages', 'index.html');

// Header HTML to insert
const HEADER_HTML = `
<header>
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
</header>
`;

try {
    // Read the file
    let content = fs.readFileSync(INDEX_FILE, 'utf8');
    
    // Check if there's a navbar placeholder
    if (content.includes('id="navbar-placeholder"')) {
        // Remove navbar placeholder
        content = content.replace(/<!-- Navbar placeholder.*?<div id="navbar-placeholder"><\/div>\n?/s, '');
    }
    
    // Check if header is missing
    if (!content.includes('<header>')) {
        // Find the body tag
        const bodyTagMatch = content.match(/<body[^>]*>/i);
        if (bodyTagMatch) {
            // Get the position right after the opening body tag
            const insertPosition = bodyTagMatch.index + bodyTagMatch[0].length;
            
            // Insert the header HTML right after the body tag
            content = 
                content.substring(0, insertPosition) + 
                '\n        ' + HEADER_HTML + '\n' + 
                content.substring(insertPosition);
        }
    }
    
    // Remove any navbar script tags
    content = content.replace(/\n?\s*<!-- Load the shared navbar component -->\s*\n?\s*<script src="\.\.\/components\/navbar\.js"><\/script>\n?/g, '');
    
    // Write the file back
    fs.writeFileSync(INDEX_FILE, content, 'utf8');
    console.log('Fixed index.html successfully');
    
} catch (error) {
    console.error('Error fixing index.html:', error);
}