/**
 * Modal Accessibility Helper
 * Provides accessibility enhancements for Bootstrap modals
 */

(function() {
    'use strict';
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeModalA11y);
    } else {
        initializeModalA11y();
    }
    
    function initializeModalA11y() {
        // Add ARIA attributes to modals if not already present
        const modals = document.querySelectorAll('.modal');
        modals.forEach(function(modal) {
            if (!modal.hasAttribute('role')) {
                modal.setAttribute('role', 'dialog');
            }
            if (!modal.hasAttribute('aria-modal')) {
                modal.setAttribute('aria-modal', 'true');
            }
        });
        
        // Ensure close buttons have proper ARIA labels
        const closeButtons = document.querySelectorAll('.modal .btn-close');
        closeButtons.forEach(function(btn) {
            if (!btn.hasAttribute('aria-label')) {
                btn.setAttribute('aria-label', 'Close');
            }
        });
    }
    
    // Re-initialize when new modals are added dynamically
    document.addEventListener('shown.bs.modal', function(e) {
        const modal = e.target;
        if (!modal.hasAttribute('role')) {
            modal.setAttribute('role', 'dialog');
        }
        if (!modal.hasAttribute('aria-modal')) {
            modal.setAttribute('aria-modal', 'true');
        }
    });
})();

