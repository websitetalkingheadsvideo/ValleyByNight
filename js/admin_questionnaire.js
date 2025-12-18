/**
 * Admin Questionnaire JavaScript - Valley by Night
 * Handles questionnaire admin functionality
 */

function toggleEdit(id) {
    const editForm = document.getElementById("edit-" + id);
    if (editForm.style.display === "none" || editForm.style.display === "") {
        editForm.style.display = "table-row";
    } else {
        editForm.style.display = "none";
    }
}

// Initialize event listeners on page load
document.addEventListener('DOMContentLoaded', function() {
    // Toggle Edit buttons - using event delegation
    document.body.addEventListener('click', function(event) {
        const button = event.target.closest('.toggle-edit-btn');
        if (button) {
            const questionId = button.getAttribute('data-question-id');
            if (questionId) {
                toggleEdit(parseInt(questionId, 10));
            }
        }
    });
    
    // Delete question forms - using event delegation
    document.body.addEventListener('submit', function(event) {
        const form = event.target.closest('.delete-question-form');
        if (form) {
            if (!confirm("Are you sure you want to delete this question?")) {
                event.preventDefault();
            }
        }
    });
});