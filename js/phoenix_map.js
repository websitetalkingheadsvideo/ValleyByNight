/**
 * Phoenix Map Interactive Viewer
 * Handles zoom, pan, and location marker display
 */

(function() {
    'use strict';

    // State
    let scale = 1;
    let panX = 0;
    let panY = 0;
    let isDragging = false;
    let dragStartX = 0;
    let dragStartY = 0;
    let initialPanX = 0;
    let initialPanY = 0;
    let showMarkers = true;
    let editMode = false;

    // Elements
    const mapContainer = document.getElementById('mapContainer');
    const mapImage = document.getElementById('phoenixMap');
    const markersOverlay = document.getElementById('markersOverlay');
    const locationsList = document.getElementById('locationsList');
    const locationsLegend = document.getElementById('locationsLegend');
    const zoomInBtn = document.getElementById('zoomInBtn');
    const zoomOutBtn = document.getElementById('zoomOutBtn');
    const resetZoomBtn = document.getElementById('resetZoomBtn');
    const showMarkersCheckbox = document.getElementById('showMarkers');

    // Initialize
    function init() {
        if (!mapContainer || !mapImage) {
            console.error('Map elements not found');
            return;
        }

        setupEventListeners();
        loadMapImage();
        renderMarkers();
        renderLocationsList();
    }

    // Load map image and get dimensions
    function loadMapImage() {
        mapImage.addEventListener('load', function() {
            updateMapTransform();
            calculateMarkerPositions();
        });
        
        // If image already loaded
        if (mapImage.complete) {
            updateMapTransform();
            calculateMarkerPositions();
        }
    }

    // Setup event listeners
    function setupEventListeners() {
        // Zoom controls
        if (zoomInBtn) {
            zoomInBtn.addEventListener('click', () => zoom(1.2));
        }
        if (zoomOutBtn) {
            zoomOutBtn.addEventListener('click', () => zoom(0.8));
        }
        if (resetZoomBtn) {
            resetZoomBtn.addEventListener('click', resetZoom);
        }

        // Markers toggle
        if (showMarkersCheckbox) {
            showMarkersCheckbox.addEventListener('change', function(e) {
                showMarkers = e.target.checked;
                toggleMarkers();
            });
        }

        // Edit mode toggle (admin only)
        const editModeCheckbox = document.getElementById('editMode');
        const locationSelect = document.getElementById('locationSelect');
        if (editModeCheckbox && typeof isAdmin !== 'undefined' && isAdmin) {
            editModeCheckbox.addEventListener('change', function(e) {
                editMode = e.target.checked;
                if (locationSelect) {
                    locationSelect.style.display = editMode ? 'inline-block' : 'none';
                }
                mapContainer.style.cursor = editMode ? 'crosshair' : 'grab';
                if (!editMode) {
                    mapContainer.classList.remove('edit-mode');
                } else {
                    mapContainer.classList.add('edit-mode');
                }
            });

            // Location selection
            if (locationSelect) {
                locationSelect.addEventListener('change', function(e) {
                    if (e.target.value) {
                        mapContainer.style.cursor = 'crosshair';
                    } else {
                        mapContainer.style.cursor = 'default';
                    }
                });
            }
        }

        // Mouse drag (disabled in edit mode)
        mapContainer.addEventListener('mousedown', function(e) {
            if (editMode && e.target.id === 'phoenixMap') {
                handleMapClick(e);
            } else if (!editMode) {
                startDrag(e);
            }
        });
        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', endDrag);

        // Touch support
        mapContainer.addEventListener('touchstart', startDragTouch, { passive: false });
        mapContainer.addEventListener('touchmove', dragTouch, { passive: false });
        mapContainer.addEventListener('touchend', endDrag);
    }

    // Zoom functions
    function zoom(factor) {
        const oldScale = scale;
        scale = Math.max(0.5, Math.min(5, scale * factor));
        
        // Zoom toward center
        const rect = mapContainer.getBoundingClientRect();
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;
        
        const dx = (centerX - panX) * (scale / oldScale - 1);
        const dy = (centerY - panY) * (scale / oldScale - 1);
        
        panX -= dx;
        panY -= dy;
        
        constrainPan();
        updateMapTransform();
    }

    function resetZoom() {
        scale = 1;
        panX = 0;
        panY = 0;
        updateMapTransform();
    }

    // Drag functions
    function startDrag(e) {
        if (e.button !== 0 && e.type !== 'touchstart') return; // Left mouse button only
        
        isDragging = true;
        mapContainer.classList.add('dragging');
        
        const clientX = e.clientX || (e.touches && e.touches[0].clientX);
        const clientY = e.clientY || (e.touches && e.touches[0].clientY);
        
        dragStartX = clientX;
        dragStartY = clientY;
        initialPanX = panX;
        initialPanY = panY;
    }

    function drag(e) {
        if (!isDragging) return;
        
        const clientX = e.clientX || (e.touches && e.touches[0].clientX);
        const clientY = e.clientY || (e.touches && e.touches[0].clientY);
        
        const deltaX = clientX - dragStartX;
        const deltaY = clientY - dragStartY;
        
        panX = initialPanX + deltaX;
        panY = initialPanY + deltaY;
        
        constrainPan();
        updateMapTransform();
    }

    function startDragTouch(e) {
        if (e.touches.length === 1) {
            startDrag(e);
        }
    }

    function dragTouch(e) {
        if (e.touches.length === 1) {
            drag(e);
        }
    }

    function endDrag() {
        isDragging = false;
        mapContainer.classList.remove('dragging');
    }

    // Constrain panning to keep map visible
    function constrainPan() {
        const rect = mapContainer.getBoundingClientRect();
        const imgWidth = mapImage.naturalWidth * scale;
        const imgHeight = mapImage.naturalHeight * scale;
        
        const maxPanX = Math.max(0, imgWidth - rect.width);
        const maxPanY = Math.max(0, imgHeight - rect.height);
        
        panX = Math.max(-maxPanX, Math.min(0, panX));
        panY = Math.max(-maxPanY, Math.min(0, panY));
    }

    // Update map transform
    function updateMapTransform() {
        mapImage.style.transform = `translate(${panX}px, ${panY}px) scale(${scale})`;
        
        // Update markers overlay transform to match
        if (markersOverlay) {
            markersOverlay.style.transform = `translate(${panX}px, ${panY}px) scale(${scale})`;
        }
    }

    // Calculate marker positions - use pixel coordinates if available, otherwise try lat/lng conversion
    function calculateMarkerPositions() {
        const locations = (typeof mapLocations !== 'undefined' && Array.isArray(mapLocations)) ? mapLocations : [];
        if (locations.length === 0 || !mapImage || !mapImage.complete) return;
        
        const imgWidth = mapImage.naturalWidth;
        const imgHeight = mapImage.naturalHeight;
        
        locations.forEach(location => {
            // Prefer pixel coordinates if they exist
            if (location.map_pixel_x !== null && location.map_pixel_x !== undefined &&
                location.map_pixel_y !== null && location.map_pixel_y !== undefined) {
                location.pixelX = parseFloat(location.map_pixel_x);
                location.pixelY = parseFloat(location.map_pixel_y);
            }
            // Fallback to lat/lng conversion (may be inaccurate)
            else if (location.latitude && location.longitude) {
                const mapBounds = {
                    // Approximate bounds for Phoenix, AZ
                    // NOTE: These may need manual adjustment - consider using pixel coordinates instead
                    north: 33.6,
                    south: 33.3,
                    east: -111.9,
                    west: -112.2
                };
                
                // Convert lat/lng to pixel coordinates
                const xPercent = (parseFloat(location.longitude) - mapBounds.west) / (mapBounds.east - mapBounds.west);
                const yPercent = 1 - (parseFloat(location.latitude) - mapBounds.south) / (mapBounds.north - mapBounds.south);
                
                // Clamp to map boundaries
                location.pixelX = Math.max(0, Math.min(imgWidth, xPercent * imgWidth));
                location.pixelY = Math.max(0, Math.min(imgHeight, yPercent * imgHeight));
            }
        });
        
        renderMarkers();
    }

    // Handle map click in edit mode to place marker
    function handleMapClick(e) {
        if (!editMode) return;
        
        const locationSelect = document.getElementById('locationSelect');
        if (!locationSelect || !locationSelect.value) {
            alert('Please select a location first from the dropdown.');
            return;
        }

        // Prevent default dragging behavior
        e.preventDefault();
        e.stopPropagation();

        // Get the actual rendered image position and size
        const imgRect = mapImage.getBoundingClientRect();
        const imgNaturalWidth = mapImage.naturalWidth;
        const imgNaturalHeight = mapImage.naturalHeight;
        
        // Click position relative to the rendered image
        const clickX = e.clientX - imgRect.left;
        const clickY = e.clientY - imgRect.top;
        
        // Convert to percentage of rendered image size
        const percentX = clickX / imgRect.width;
        const percentY = clickY / imgRect.height;
        
        // Convert to natural image pixel coordinates
        const pixelX = percentX * imgNaturalWidth;
        const pixelY = percentY * imgNaturalHeight;
        
        // Clamp to image bounds
        const clampedX = Math.max(0, Math.min(imgNaturalWidth, pixelX));
        const clampedY = Math.max(0, Math.min(imgNaturalHeight, pixelY));
        
        // Save marker position
        saveMarkerPosition(locationSelect.value, clampedX, clampedY);
    }

    // Save marker position via API
    function saveMarkerPosition(locationId, x, y) {
        fetch('admin/api_update_map_position.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                location_id: locationId,
                map_pixel_x: x,
                map_pixel_y: y
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update local location data
                const location = mapLocations.find(loc => loc.id == locationId);
                if (location) {
                    location.map_pixel_x = x;
                    location.map_pixel_y = y;
                    location.pixelX = x;
                    location.pixelY = y;
                    renderMarkers();
                }
                alert('Marker position saved!');
                // Optionally clear selection
                // document.getElementById('locationSelect').value = '';
            } else {
                alert('Error saving position: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving marker position. Check console for details.');
        });
    }

    // Render location markers
    function renderMarkers() {
        if (!markersOverlay) return;
        
        // Check if mapLocations exists and is an array
        const locations = (typeof mapLocations !== 'undefined' && Array.isArray(mapLocations)) ? mapLocations : [];
        
        markersOverlay.innerHTML = '';
        
        if (!showMarkers || locations.length === 0) return;
        
        locations.forEach(location => {
            if (location.pixelX !== undefined && location.pixelY !== undefined) {
                const marker = createMarker(location);
                markersOverlay.appendChild(marker);
            }
        });
    }

    // Create a marker element
    function createMarker(location) {
        const marker = document.createElement('div');
        marker.className = 'location-marker';
        marker.style.left = location.pixelX + 'px';
        marker.style.top = location.pixelY + 'px';
        
        const typeClass = getTypeClass(location.type);
        
        marker.innerHTML = `
            <svg class="marker-pin ${typeClass}" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
            </svg>
            <div class="marker-tooltip">
                <div class="tooltip-name">${escapeHtml(location.name)}</div>
                <div class="tooltip-type">${escapeHtml(location.type)}</div>
            </div>
        `;
        
        // Tooltip hover
        const tooltip = marker.querySelector('.marker-tooltip');
        marker.addEventListener('mouseenter', () => {
            tooltip.classList.add('show');
        });
        marker.addEventListener('mouseleave', () => {
            tooltip.classList.remove('show');
        });
        
        // Click handler - could navigate to location detail
        marker.addEventListener('click', () => {
            console.log('Clicked location:', location);
            // Could open location modal or navigate to location page
        });
        
        return marker;
    }

    // Get CSS class for location type
    function getTypeClass(type) {
        const typeMap = {
            'Haven': 'haven',
            'Elysium': 'elysium',
            'Domain': 'domain',
            'Business': 'business',
            'Hunting Ground': 'hunting-ground',
            'Hunting Grounds': 'hunting-ground'
        };
        return typeMap[type] || 'other';
    }

    // Render locations list
    function renderLocationsList() {
        if (!locationsList) return;
        
        const locations = (typeof mapLocations !== 'undefined' && Array.isArray(mapLocations)) ? mapLocations : [];
        
        locationsList.innerHTML = '';
        
        if (locations.length === 0) {
            locationsList.innerHTML = '<div class="col-12 text-center text-muted">No locations with coordinates available.</div>';
            return;
        }
        
        locations.forEach(location => {
            const item = document.createElement('div');
            item.className = 'col-12 col-md-6 col-lg-4 location-item';
            
            const typeClass = getTypeClass(location.type);
            
            item.innerHTML = `
                <div class="location-name">${escapeHtml(location.name)}</div>
                <div class="location-meta">
                    <span class="badge bg-dark">${escapeHtml(location.type)}</span>
                    ${location.district ? `<span class="text-muted">${escapeHtml(location.district)}</span>` : ''}
                </div>
            `;
            
            // Highlight marker on hover
            item.addEventListener('mouseenter', () => {
                const markers = markersOverlay.querySelectorAll('.location-marker');
                const index = locations.indexOf(location);
                if (markers[index]) {
                    markers[index].style.filter = 'brightness(1.5)';
                    markers[index].style.transform = 'translate(-50%, -50%) scale(1.4)';
                }
            });
            
            item.addEventListener('mouseleave', () => {
                const markers = markersOverlay.querySelectorAll('.location-marker');
                const index = locations.indexOf(location);
                if (markers[index]) {
                    markers[index].style.filter = '';
                    markers[index].style.transform = 'translate(-50%, -50%)';
                }
            });
            
            locationsList.appendChild(item);
        });
        
        if (!showMarkers) {
            locationsLegend.style.display = 'none';
        }
    }

    // Toggle markers visibility
    function toggleMarkers() {
        if (showMarkers) {
            renderMarkers();
            if (locationsLegend) {
                locationsLegend.style.display = 'block';
            }
        } else {
            if (markersOverlay) {
                markersOverlay.innerHTML = '';
            }
            if (locationsLegend) {
                locationsLegend.style.display = 'none';
            }
        }
    }

    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

