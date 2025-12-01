/**
 * Logo Animation
 * Adds hover and animation effects to the Valley by Night logo
 */

(function() {
    'use strict';
    
    function initializeLogoAnimation() {
        const logoLink = document.querySelector('.logo-link');
        const logoSvg = document.querySelector('.logo-svg');
        
        if (!logoLink || !logoSvg) {
            // Logo elements not found - this is fine for pages without logo
            return;
        }
        
        // Add hover effects
        logoLink.addEventListener('mouseenter', function() {
            if (logoSvg) {
                const border = logoSvg.querySelector('.logo-border');
                const text = logoSvg.querySelector('.logo-text');
                if (border) {
                    border.style.stroke = '#b30000';
                    border.style.filter = 'drop-shadow(0 0 8px rgba(139, 0, 0, 0.6))';
                }
                if (text) {
                    text.style.fill = '#b30000';
                    text.style.filter = 'drop-shadow(0 0 4px rgba(139, 0, 0, 0.8))';
                }
            }
        });
        
        logoLink.addEventListener('mouseleave', function() {
            if (logoSvg) {
                const border = logoSvg.querySelector('.logo-border');
                const text = logoSvg.querySelector('.logo-text');
                if (border) {
                    border.style.stroke = '#8B0000';
                    border.style.filter = 'drop-shadow(0 0 2px rgba(0, 0, 0, 0.8))';
                }
                if (text) {
                    text.style.fill = '#8B0000';
                    text.style.filter = 'drop-shadow(0 0 2px rgba(0, 0, 0, 0.8))';
                }
            }
        });
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeLogoAnimation);
    } else {
        initializeLogoAnimation();
    }
})();

