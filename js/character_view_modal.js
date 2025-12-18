/**
 * Character View Modal - Image Error Handling
 * Handles image load errors for character portraits
 */

document.addEventListener('DOMContentLoaded', function() {
    // Use event delegation for dynamically added images
    document.addEventListener('error', function(event) {
        const img = event.target;
        if (img && img.classList.contains('character-portrait-image')) {
            const hasPortrait = img.getAttribute('data-has-portrait') === 'true';
            
            // Only show placeholder on error if there's a portrait (fallback available)
            if (hasPortrait) {
                img.classList.add('d-none');
                const placeholder = img.nextElementSibling;
                if (placeholder && placeholder.classList.contains('character-portrait-placeholder')) {
                    placeholder.classList.remove('d-none');
                }
            }
        }
    }, true); // Use capture phase to catch errors
});

