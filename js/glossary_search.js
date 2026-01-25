/**
 * glossary_search.js
 * 
 * Search functionality for the World of Darkness Glossary
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('glossary-search-input');
        const searchClear = document.getElementById('glossary-search-clear');
        const searchResults = document.getElementById('glossary-search-results');
        const glossaryTerms = document.querySelectorAll('.glossary-term');
        const glossarySections = document.querySelectorAll('.glossary-section');

        if (!searchInput) {
            return;
        }

        // Search function
        function performSearch(query) {
            const searchTerm = query.trim().toLowerCase();
            let visibleCount = 0;
            let highlightedCount = 0;

            if (searchTerm === '') {
                // Show all terms
                glossaryTerms.forEach(term => {
                    term.classList.remove('hidden', 'highlighted');
                    visibleCount++;
                });
                glossarySections.forEach(section => {
                    section.style.display = '';
                });
                searchResults.textContent = '';
                searchResults.classList.remove('has-results');
                searchClear.style.display = 'none';
                return;
            }

            // Search through terms and categorize matches
            const termMatches = new Map(); // section -> { exact: [], partial: [] }
            
            glossaryTerms.forEach(term => {
                const searchableText = term.getAttribute('data-searchable') || '';
                const termName = term.getAttribute('data-term') || '';
                const section = term.closest('.glossary-section');
                
                if (!section) return;
                
                const sectionId = section.getAttribute('data-section') || '';
                
                if (!termMatches.has(sectionId)) {
                    termMatches.set(sectionId, { exact: [], partial: [] });
                }
                
                const isExactMatch = termName === searchTerm;
                const isPartialMatch = searchableText.includes(searchTerm) || termName.includes(searchTerm);
                
                if (isExactMatch) {
                    term.classList.remove('hidden');
                    term.classList.add('highlighted');
                    termMatches.get(sectionId).exact.push(term);
                    visibleCount++;
                } else if (isPartialMatch) {
                    term.classList.remove('hidden');
                    term.classList.add('highlighted');
                    termMatches.get(sectionId).partial.push(term);
                    visibleCount++;
                } else {
                    term.classList.add('hidden');
                    term.classList.remove('highlighted');
                }
            });

            // Get the glossary container to reorder sections
            const glossaryContainer = document.getElementById('glossary-content');
            
            // Separate sections into those with exact matches and those without
            const sectionsWithExact = [];
            const sectionsWithPartialOnly = [];
            const sectionsToHide = [];
            
            glossarySections.forEach(section => {
                const sectionId = section.getAttribute('data-section') || '';
                const matches = termMatches.get(sectionId);
                
                if (!matches || (matches.exact.length === 0 && matches.partial.length === 0)) {
                    section.style.display = 'none';
                    sectionsToHide.push(section);
                    return;
                }
                
                section.style.display = '';
                
                if (matches.exact.length > 0) {
                    sectionsWithExact.push(section);
                } else {
                    sectionsWithPartialOnly.push(section);
                }
            });
            
            // Reorder sections: sections with exact matches first, then sections with partial matches only
            if (glossaryContainer) {
                [...sectionsWithExact, ...sectionsWithPartialOnly, ...sectionsToHide].forEach(section => {
                    glossaryContainer.appendChild(section);
                });
            }
            
            // Reorder terms within each section: exact matches first, then partial matches
            glossarySections.forEach(section => {
                const sectionId = section.getAttribute('data-section') || '';
                const matches = termMatches.get(sectionId);
                
                if (!matches || (matches.exact.length === 0 && matches.partial.length === 0)) {
                    return;
                }
                
                // Get all terms in this section (including hidden ones)
                const allTerms = Array.from(section.querySelectorAll('.glossary-term'));
                
                // Create ordered list: exact matches first, then partial matches, then hidden terms
                const orderedTerms = [
                    ...matches.exact,
                    ...matches.partial,
                    ...allTerms.filter(term => term.classList.contains('hidden'))
                ];
                
                // Reorder in DOM
                orderedTerms.forEach(term => {
                    section.appendChild(term);
                });
            });

            // Update results message
            if (visibleCount === 0) {
                searchResults.textContent = 'No results found for "' + query + '"';
                searchResults.classList.remove('has-results');
            } else {
                searchResults.textContent = 'Found ' + visibleCount + ' result' + (visibleCount !== 1 ? 's' : '') + ' for "' + query + '"';
                searchResults.classList.add('has-results');
            }

            // Show clear button
            searchClear.style.display = 'inline-block';
        }

        // Event listeners
        searchInput.addEventListener('input', function() {
            performSearch(this.value);
        });

        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                performSearch('');
                this.blur();
            }
        });

        searchClear.addEventListener('click', function() {
            searchInput.value = '';
            performSearch('');
            searchInput.focus();
        });

        // Highlight search term in visible results (optional enhancement)
        function highlightSearchTerm(text, searchTerm) {
            if (!searchTerm) return text;
            const regex = new RegExp(`(${searchTerm})`, 'gi');
            return text.replace(regex, '<mark>$1</mark>');
        }
    });
})();
