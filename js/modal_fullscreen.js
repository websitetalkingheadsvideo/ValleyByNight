/**
 * Modal Fullscreen Functionality
 * Adds fullscreen toggle button to Bootstrap modals
 * 
 * Usage:
 * - Add data-fullscreen="true" to any modal to enable fullscreen functionality
 * - Or call enableModalFullscreen(modalId) programmatically
 */

(function() {
    'use strict';
    
    // Icon characters for fullscreen toggle
    const ICONS = {
        enter: '⤢',  // Arrows pointing out (enter fullscreen)
        exit: '⤡'    // Arrows pointing in (exit fullscreen)
    };
    
    /**
     * Create fullscreen button for modal header
     */
    function createFullscreenButton(modalId) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-outline-light modal-fullscreen-btn';
        button.setAttribute('data-bs-toggle', 'tooltip');
        button.setAttribute('title', 'Toggle Fullscreen');
        button.setAttribute('aria-label', 'Toggle Fullscreen');
        button.innerHTML = `<span class="modal-fullscreen-icon">${ICONS.enter}</span>`;
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleModalFullscreen(modalId);
        });
        return button;
    }
    
    /**
     * Toggle fullscreen mode for a modal
     */
    function toggleModalFullscreen(modalId) {
        const modalEl = document.getElementById(modalId);
        if (!modalEl) {
            console.warn('Modal not found:', modalId);
            return;
        }
        
        const modalDialog = modalEl.querySelector('.modal-dialog');
        const fullscreenBtn = modalEl.querySelector('.modal-fullscreen-btn');
        const fullscreenIcon = modalEl.querySelector('.modal-fullscreen-icon');
        
        if (!modalDialog) {
            console.warn('Modal dialog not found in modal:', modalId);
            return;
        }
        
        const isFullscreen = modalEl.classList.contains('fullscreen');
        
        if (isFullscreen) {
            // Exit fullscreen
            modalEl.classList.remove('fullscreen');
            if (fullscreenIcon) {
                fullscreenIcon.textContent = ICONS.enter;
            }
            if (fullscreenBtn) {
                fullscreenBtn.setAttribute('title', 'Toggle Fullscreen');
                fullscreenBtn.setAttribute('aria-label', 'Toggle Fullscreen');
            }
        } else {
            // Enter fullscreen
            modalEl.classList.add('fullscreen');
            if (fullscreenIcon) {
                fullscreenIcon.textContent = ICONS.exit;
            }
            if (fullscreenBtn) {
                fullscreenBtn.setAttribute('title', 'Exit Fullscreen');
                fullscreenBtn.setAttribute('aria-label', 'Exit Fullscreen');
            }
        }
        
        // Trigger resize event for any listeners (e.g., graphs, charts)
        const resizeEvent = new Event('resize');
        window.dispatchEvent(resizeEvent);
        
        // Call custom resize handler if it exists
        const resizeHandler = modalEl.dataset.fullscreenResizeHandler;
        if (resizeHandler && typeof window[resizeHandler] === 'function') {
            window[resizeHandler](modalEl, !isFullscreen);
        }
    }
    
    /**
     * Enable fullscreen functionality for a modal
     */
    function enableModalFullscreen(modalId) {
        const modalEl = document.getElementById(modalId);
        if (!modalEl) {
            console.warn('Modal not found:', modalId);
            return;
        }
        
        // Check if already enabled
        if (modalEl.dataset.fullscreenEnabled === 'true') {
            return;
        }
        
        // Find modal header
        const modalHeader = modalEl.querySelector('.modal-header');
        if (!modalHeader) {
            console.warn('Modal header not found in modal:', modalId);
            return;
        }
        
        // Check if button already exists
        if (modalHeader.querySelector('.modal-fullscreen-btn')) {
            modalEl.dataset.fullscreenEnabled = 'true';
            return;
        }
        
        // Find close button group or create container
        let buttonContainer = modalHeader.querySelector('.d-flex.gap-2:has(.btn-close)');
        if (!buttonContainer) {
            // Try to find close button
            const closeBtn = modalHeader.querySelector('.btn-close');
            if (closeBtn) {
                // Create container and wrap close button
                buttonContainer = document.createElement('div');
                buttonContainer.className = 'd-flex gap-2 ms-auto';
                closeBtn.parentNode.insertBefore(buttonContainer, closeBtn);
                buttonContainer.appendChild(closeBtn);
            } else {
                // Create container at end of header
                buttonContainer = document.createElement('div');
                buttonContainer.className = 'd-flex gap-2 ms-auto';
                modalHeader.appendChild(buttonContainer);
            }
        }
        
        // Insert fullscreen button before close button
        const fullscreenBtn = createFullscreenButton(modalId);
        const closeBtn = buttonContainer.querySelector('.btn-close');
        if (closeBtn) {
            buttonContainer.insertBefore(fullscreenBtn, closeBtn);
        } else {
            buttonContainer.appendChild(fullscreenBtn);
        }
        
        // Mark as enabled
        modalEl.dataset.fullscreenEnabled = 'true';
        
        // Reset fullscreen state when modal is hidden
        modalEl.addEventListener('hidden.bs.modal', function resetFullscreen() {
            if (modalEl.classList.contains('fullscreen')) {
                toggleModalFullscreen(modalId);
            }
        }, { once: false });
    }
    
    /**
     * Initialize fullscreen for all modals with data-fullscreen attribute
     */
    function initializeFullscreenModals() {
        // Find all modals with data-fullscreen attribute
        const modals = document.querySelectorAll('.modal[data-fullscreen="true"]');
        modals.forEach(function(modal) {
            enableModalFullscreen(modal.id);
        });
    }
    
    /**
     * Expose functions globally
     */
    window.enableModalFullscreen = enableModalFullscreen;
    window.toggleModalFullscreen = toggleModalFullscreen;
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeFullscreenModals);
    } else {
        initializeFullscreenModals();
    }
    
    // Re-initialize after dynamic content is loaded
    // Listen for Bootstrap modal shown event to add fullscreen button dynamically
    document.addEventListener('shown.bs.modal', function(e) {
        const modal = e.target;
        if (modal.dataset.fullscreen === 'true' && modal.dataset.fullscreenEnabled !== 'true') {
            enableModalFullscreen(modal.id);
        }
    });
    
})();

