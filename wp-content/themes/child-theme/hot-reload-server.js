#!/usr/bin/env node

/**
 * Hot Reload Server for WordPress Theme Development
 * Watches theme files and sends reload signals to browser via WebSocket
 */

const chokidar = require('chokidar');
const WebSocket = require('ws');
const path = require('path');

// Get theme directory path (parent directory of this script)
const THEME_DIR = __dirname;
const PORT = process.env.HOT_RELOAD_PORT || 35729;
const HOST = '0.0.0.0'; // Listen on all interfaces for external device access

// WebSocket server - listen on all interfaces for external device access
const wss = new WebSocket.Server({ host: HOST, port: PORT });

let clients = [];

wss.on('connection', (ws, req) => {
    clients.push(ws);
    console.log(`[Hot Reload] Client connected from ${req.socket.remoteAddress}. Total: ${clients.length}`);

    ws.on('close', () => {
        clients = clients.filter(client => client !== ws);
        console.log(`[Hot Reload] Client disconnected. Total: ${clients.length}`);
    });

    ws.on('error', (error) => {
        console.error('[Hot Reload] WebSocket error:', error.message);
    });
});

// Broadcast reload signal to all connected clients
function broadcastReload(filePath) {
    const message = JSON.stringify({
        type: 'reload',
        file: path.relative(THEME_DIR, filePath),
        timestamp: Date.now()
    });

    const activeClients = clients.filter(client => client.readyState === WebSocket.OPEN);
    
    if (activeClients.length === 0) {
        console.log(`[Hot Reload] No active clients connected`);
        return;
    }

    activeClients.forEach(client => {
        client.send(message);
    });

    console.log(`[Hot Reload] Reload signal sent to ${activeClients.length} client(s)`);
}

// Watch all files in theme directory
const watcher = chokidar.watch(THEME_DIR, {
    ignored: [
        /(^|[\/\\])\../, // Ignore dotfiles
        /node_modules/,
        /vendor/,
        /\.git/,
        /package-lock\.json/,
        /composer\.lock/,
        /\.DS_Store/
    ],
    persistent: true,
    ignoreInitial: true
});

watcher
    .on('ready', () => {
        console.log(`[Hot Reload] Ready. Watching for changes...`);
    })
    .on('change', (filePath) => {
        const relativePath = path.relative(THEME_DIR, filePath);
        console.log(`[Hot Reload] Changed: ${relativePath}`);
        broadcastReload(filePath);
    })
    .on('add', (filePath) => {
        const relativePath = path.relative(THEME_DIR, filePath);
        console.log(`[Hot Reload] Added: ${relativePath}`);
        broadcastReload(filePath);
    })
    .on('unlink', (filePath) => {
        const relativePath = path.relative(THEME_DIR, filePath);
        console.log(`[Hot Reload] Deleted: ${relativePath}`);
        broadcastReload(filePath);
    })
    .on('error', (error) => {
        console.error('[Hot Reload] Watcher error:', error);
    });

// Graceful shutdown handler
function shutdown() {
    console.log('\n[Hot Reload] Shutting down...');
    
    // Close file watcher
    watcher.close();
    
    // Close all active client connections
    const activeClients = clients.filter(client => client.readyState === WebSocket.OPEN);
    if (activeClients.length > 0) {
        console.log(`[Hot Reload] Closing ${activeClients.length} active connection(s)...`);
        activeClients.forEach(client => {
            client.close();
        });
    }
    
    // Set timeout for forced shutdown
    const shutdownTimeout = setTimeout(() => {
        console.log('[Hot Reload] Forced shutdown after timeout');
        process.exit(1);
    }, 2000);
    
    // Close WebSocket server
    wss.close(() => {
        clearTimeout(shutdownTimeout);
        console.log('[Hot Reload] Server stopped');
        process.exit(0);
    });
}

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);
