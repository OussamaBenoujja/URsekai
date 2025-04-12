    }
    */
}

let playUrl = null; // Store the play URL globally
let playAssetId = null; // Store the assetId for the main_game asset

// Fetch all game assets and set playUrl to the /game-test/{gameId}/{assetId} route
async function fetchMainGameAsset(gameId) {
    try {
        const response = await fetch(`${API_BASE}/api/v1/games/${gameId}/assets?type=main_game`); // Use correct public endpoint
        if (!response.ok) throw new Error('Failed to fetch game asset');
        const data = await response.json();
        if (data.data && data.data.length > 0) {
            // Use the first main_game asset
            const mainGameAsset = data.data[0];
            playAssetId = mainGameAsset.asset_id;
            playUrl = `http://localhost:3000/play-game?gameId=${gameId}&assetId=${playAssetId}`;
        } else {
            playUrl = null;
            playAssetId = null;
        }
    } catch (err) {
        playUrl = null;
        playAssetId = null;
        console.error('Error fetching main game asset:', err);
    }
}
