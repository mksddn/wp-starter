/**
 * Hot Reload Client for WordPress Theme Development
 * Connects to WebSocket server and reloads page on file changes
 */

(function() {
    'use strict';

    console.log('[Hot Reload] Client script loaded');

    // Get WebSocket URL from data attribute or use default
    const scriptTag = document.querySelector('script[data-hot-reload-port]');
    const port = scriptTag ? scriptTag.getAttribute('data-hot-reload-port') : '35729';
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    
    // Try multiple connection strategies
    const hostname = window.location.hostname;
    const wsUrls = [
        `${protocol}//${hostname}:${port}`, // Try current hostname first
        `${protocol}//localhost:${port}`,   // Fallback to localhost
        `${protocol}//127.0.0.1:${port}`    // Fallback to 127.0.0.1
    ];
    
    let currentUrlIndex = 0;
    let ws;
    let reconnectAttempts = 0;
    const maxReconnectAttempts = 10;
    const reconnectDelay = 2000;

    function connect() {
        if (currentUrlIndex >= wsUrls.length) {
            console.warn('[Hot Reload] All connection attempts failed. Hot reload disabled.');
            return;
        }

        const wsUrl = wsUrls[currentUrlIndex];
        
        try {
            console.log(`[Hot Reload] Connecting to: ${wsUrl} (attempt ${currentUrlIndex + 1}/${wsUrls.length})`);
            ws = new WebSocket(wsUrl);

            ws.onopen = function() {
                console.log(`[Hot Reload] ✓ Connected to server at ${wsUrl}`);
                reconnectAttempts = 0;
                currentUrlIndex = 0; // Reset to first URL on successful connection
            };

            ws.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    if (data.type === 'reload') {
                        console.log(`[Hot Reload] Reloading page... (${data.file})`);
                        window.location.reload();
                    }
                } catch (e) {
                    console.error('[Hot Reload] Error parsing message:', e);
                }
            };

            ws.onclose = function(event) {
                if (event.wasClean) {
                    console.log('[Hot Reload] Connection closed cleanly');
                } else {
                    console.log('[Hot Reload] Connection closed unexpectedly');
                }
                attemptReconnect();
            };

            ws.onerror = function(error) {
                console.error(`[Hot Reload] WebSocket error connecting to ${wsUrl}`);
                // Try next URL immediately on error
                currentUrlIndex++;
                if (currentUrlIndex < wsUrls.length) {
                    setTimeout(connect, 500);
                } else {
                    attemptReconnect();
                }
            };
        } catch (e) {
            console.error('[Hot Reload] Connection error:', e);
            currentUrlIndex++;
            if (currentUrlIndex < wsUrls.length) {
                setTimeout(connect, 500);
            } else {
                attemptReconnect();
            }
        }
    }

    function attemptReconnect() {
        if (reconnectAttempts < maxReconnectAttempts) {
            reconnectAttempts++;
            // Reset URL index when retrying
            currentUrlIndex = 0;
            console.log(`[Hot Reload] Attempting to reconnect (${reconnectAttempts}/${maxReconnectAttempts})...`);
            setTimeout(connect, reconnectDelay);
        } else {
            console.warn('[Hot Reload] Max reconnection attempts reached. Hot reload disabled.');
        }
    }

    // Start connection
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', connect);
    } else {
        connect();
    }
})();
