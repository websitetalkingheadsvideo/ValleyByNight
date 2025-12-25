/**
 * Coterie Agent - Auto-save role on blur
 */
document.addEventListener('DOMContentLoaded', function() {
  // Find all role input fields in the roster table
  const roleInputs = document.querySelectorAll('input[name="new_role"]');
  
  roleInputs.forEach(function(input) {
    // Store the original value to detect changes
    let originalValue = input.value;
    
    // Auto-save on blur (when input loses focus)
    input.addEventListener('blur', function() {
      const currentValue = input.value.trim();
      
      // Only submit if the value actually changed
      if (currentValue !== originalValue) {
        // Find the parent form and submit it
        const form = input.closest('form');
        if (form) {
          form.submit();
        }
      }
    });
    
    // Also save on Enter key press
    input.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault(); // Prevent form submission (we'll do it manually)
        input.blur(); // Trigger blur event which will handle the save
      }
    });
    
    // Update original value when user types (for change detection)
    input.addEventListener('input', function() {
      // This allows us to track changes in real-time
      // The original value is still what it was on page load
    });
  });
});

