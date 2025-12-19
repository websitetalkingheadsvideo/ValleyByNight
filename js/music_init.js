/**
 * Music System Initialization
 * Standalone initialization script for admin pages and other non-modular contexts
 */

(function() {
    'use strict';
    
    // Check if MusicManager is already loaded
    if (typeof MusicManager === 'undefined') {
        console.warn('[MusicInit] MusicManager class not found. Make sure js/modules/systems/MusicManager.js is loaded.');
        return;
    }
    
    // Initialize music manager on page load
    document.addEventListener('DOMContentLoaded', function() {
        try {
            // Create MusicManager instance (no EventManager in admin pages)
            window.musicManager = new MusicManager(null);
            
            // Enable debug mode in development
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                window.musicManager.setDebugMode(true);
            }
            
            console.log('[MusicInit] Music system initialized');
        } catch (error) {
            console.error('[MusicInit] Failed to initialize music system:', error);
        }
    });
})();

