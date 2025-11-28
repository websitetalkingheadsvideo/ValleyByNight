/**
 * Character Image Upload Module
 * Handles image upload, preview, and removal for character portraits
 */

class CharacterImageManager {
    constructor(characterId = null) {
        this.characterId = characterId;
        this.currentImagePath = null;
        this.characterData = null;
        this.selectedFile = null;
        
        this.init();
    }
    
    init() {
        // Set up file input change handler
        const fileInput = document.getElementById('characterImageInput');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                this.handleFileSelect(e);
            });
        } else {
            console.warn('[CharacterImageManager] File input not found!');
        }
        
        // Set up upload button
        const uploadBtn = document.getElementById('uploadCharacterImageBtn');
        if (uploadBtn) {
            uploadBtn.addEventListener('click', (e) => this.handleUploadImage(e));
        }
        
        // Set up remove button
        const removeBtn = document.getElementById('removeCharacterImageBtn');
        if (removeBtn) {
            removeBtn.addEventListener('click', (e) => this.handleRemoveImage(e));
        }
    }
    
    /**
     * Handle file selection with preview
     */
    handleFileSelect(event) {
        const file = event.target.files[0];
        if (!file) {
            return;
        }
        
        console.log('[CharacterImageManager] File selected:', file.name, file.type, file.size);
        
        // Validate file type (frontend mirror of backend)
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            this.showError('Invalid file type. Please select a JPG, PNG, GIF, or WEBP image.');
            event.target.value = ''; // Clear selection
            return;
        }
        
        // Validate file size (2MB max)
        const maxSize = 2 * 1024 * 1024; // 2MB
        if (file.size > maxSize) {
            this.showError('File size exceeds 2MB limit. Please select a smaller image.');
            event.target.value = '';
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = (e) => {
            this.showPreview(e.target.result);
            // Store file for later upload
            this.selectedFile = file;
        };
        reader.onerror = (e) => {
            console.error('[CharacterImageManager] FileReader error:', e);
            this.showError('Error reading image file.');
        };
        reader.readAsDataURL(file);
    }
    
    /**
     * Handle remove button click
     */
    async handleRemoveImage(event) {
        event.preventDefault();
        await this.removeImage();
    }
    
    /**
     * Handle upload button click
     */
    async handleUploadImage(event) {
        event.preventDefault();
        if (this.selectedFile && this.characterId) {
            const success = await this.uploadImage(this.selectedFile);
            if (success) {
                // Hide upload button
                const uploadBtn = document.getElementById('uploadCharacterImageBtn');
                if (uploadBtn) uploadBtn.style.display = 'none';
                
                // Clear file input
                const fileInput = document.getElementById('characterImageInput');
                if (fileInput) fileInput.value = '';
                this.selectedFile = null;
            }
        }
    }
    
    /**
     * Upload image to server
     */
    async uploadImage(file) {
        if (!this.characterId) {
            this.showError('Cannot upload image: Character ID not set');
            return;
        }
        
        const formData = new FormData();
        formData.append('image', file);
        formData.append('character_id', this.characterId);
        
        try {
            const response = await fetch('includes/upload_character_image.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.currentImagePath = result.image_path;
                this.showSuccess('Image uploaded successfully!');
                return true;
            } else {
                this.showError(result.message || 'Upload failed');
                return false;
            }
        } catch (error) {
            this.showError('Upload error: ' + error.message);
            return false;
        }
    }
    
    /**
     * Remove image from server
     */
    async removeImage() {
        if (!this.characterId) {
            this.showError('Cannot remove image: Character ID not set');
            return;
        }
        
        if (!confirm('Are you sure you want to remove the character image?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('character_id', this.characterId);
        
        try {
            const response = await fetch('remove_character_image.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.currentImagePath = null;
                this.clearPreview();
                this.showSuccess('Image removed successfully!');
                return true;
            } else {
                this.showError(result.message || 'Remove failed');
                return false;
            }
        } catch (error) {
            this.showError('Remove error: ' + error.message);
            return false;
        }
    }
    
    /**
     * Show image preview
     */
    showPreview(imageData) {
        const preview = document.getElementById('characterImagePreview');
        const placeholder = document.getElementById('characterImagePlaceholder');
        
        if (preview) {
            preview.src = imageData;
            preview.style.display = 'block';
            preview.classList.remove('clan-icon');
        }
        
        if (placeholder) {
            placeholder.style.display = 'none';
        }
        
        // Show upload button
        const uploadBtn = document.getElementById('uploadCharacterImageBtn');
        if (uploadBtn) {
            uploadBtn.style.display = 'inline-block';
        }
        
        // Hide remove button (no image uploaded yet)
        const removeBtn = document.getElementById('removeCharacterImageBtn');
        if (removeBtn) {
            removeBtn.style.display = 'none';
        }
    }
    
    /**
     * Clear preview and show placeholder
     */
    clearPreview() {
        const preview = document.getElementById('characterImagePreview');
        if (preview) {
            preview.style.display = 'none';
        }
        
        const placeholder = document.getElementById('characterImagePlaceholder');
        if (placeholder) {
            placeholder.style.display = 'block';
        }
        
        const uploadBtn = document.getElementById('uploadCharacterImageBtn');
        if (uploadBtn) {
            uploadBtn.style.display = 'none';
        }
        
        const removeBtn = document.getElementById('removeCharacterImageBtn');
        if (removeBtn) {
            removeBtn.style.display = 'none';
        }
    }
    
    /**
     * Set character ID and load image
     */
    setCharacterId(characterId, characterData = null) {
        this.characterId = characterId;
        this.characterData = characterData;
        
        // Load image if character data provided
        if (characterData && characterData.character && characterData.character.character_image) {
            this.currentImagePath = characterData.character.character_image;
            this.displayImage(characterData.character.character_image, characterData.character.clan);
        } else if (characterData && characterData.character) {
            // Show clan SVG or default
            this.displayImage(null, characterData.character.clan);
        }
    }
    
    /**
     * Display character image with fallbacks
     */
    displayImage(imagePath, clan) {
        const preview = document.getElementById('characterImagePreview');
        const placeholder = document.getElementById('characterImagePlaceholder');
        
        if (imagePath) {
            // User uploaded image - construct full path from filename
            // imagePath is just a filename, need to prepend the uploads directory
            // Use relative path that works from character editor context
            const fullImagePath = imagePath.startsWith('/') || imagePath.startsWith('http') ? imagePath : `uploads/characters/${imagePath}`;
            if (preview) {
                preview.src = fullImagePath;
                preview.style.display = 'block';
                preview.classList.remove('clan-icon');
            }
            if (placeholder) placeholder.style.display = 'none';
            
            // Show remove button
            const removeBtn = document.getElementById('removeCharacterImageBtn');
            if (removeBtn) {
                removeBtn.style.display = 'inline-block';
            }
            
            // Hide upload button
            const uploadBtn = document.getElementById('uploadCharacterImageBtn');
            if (uploadBtn) {
                uploadBtn.style.display = 'none';
            }
        } else {
            // No user image - show clan logo fallback if clan is set
            if (clan) {
                // Map clan names to logo filenames
                const clanMap = {
                    'assamite': 'LogoClanAssamite.webp',
                    'brujah': 'LogoClanBrujah.webp',
                    'followers of set': 'LogoClanFollowersofSet.webp',
                    'setite': 'LogoClanFollowersofSet.webp',
                    'daughter of cacophony': 'LogoBloodlineDaughtersofCacophony.webp',
                    'daughters of cacophony': 'LogoBloodlineDaughtersofCacophony.webp',
                    'gangrel': 'LogoClanGangrel.webp',
                    'giovanni': 'LogoClanGiovanni.webp',
                    'lasombra': 'LogoClanLasombra.webp',
                    'malkavian': 'LogoClanMalkavian.webp',
                    'nosferatu': 'LogoClanNosferatu.webp',
                    'ravnos': 'LogoClanRavnos.webp',
                    'toreador': 'LogoClanToreador.webp',
                    'tremere': 'LogoClanTremere.webp',
                    'tzimisce': 'LogoClanTzimisce.webp',
                    'ventrue': 'LogoClanVentrue.webp',
                    'caitiff': 'LogoBloodlineCaitiff.webp',
                    'ghoul': 'Ghoul_Symbol.webp'
                };
                
                // Clean clan name and get logo filename
                let cleanClan = String(clan).trim().toLowerCase();
                // Remove emoji and special characters
                cleanClan = cleanClan.replace(/[\uD800-\uDBFF][\uDC00-\uDFFF]/g, '');
                cleanClan = cleanClan.replace(/[^\x00-\x7F]/g, '');
                
                const logoFile = clanMap[cleanClan] || null;
                
                if (logoFile) {
                    // Use relative path that works from character editor context
                    const clanLogoPath = `images/Clan Logos/${logoFile}`;
                    if (preview) {
                        preview.src = clanLogoPath;
                        preview.style.display = 'block';
                        preview.classList.add('clan-icon');
                    }
                    if (placeholder) placeholder.style.display = 'none';
                } else {
                    // Clan not found in map - show default placeholder
                    if (preview) {
                        preview.style.display = 'none';
                        preview.src = '';
                    }
                    if (placeholder) placeholder.style.display = 'flex';
                }
            } else {
                // No clan either - show default placeholder
                if (preview) {
                    preview.style.display = 'none';
                    preview.src = '';
                }
                if (placeholder) placeholder.style.display = 'flex';
            }
        }
    }
    
    /**
     * Helper functions for notification
     */
    showSuccess(message) {
        // Use existing notification system if available
        if (typeof showNotification === 'function') {
            showNotification('✅ ' + message, 'success');
        } else {
            console.log('Success:', message);
        }
    }
    
    showError(message) {
        // Use existing notification system if available
        if (typeof showNotification === 'function') {
            showNotification('❌ ' + message, 'error');
        } else {
            console.error('Error:', message);
            alert('Error: ' + message);
        }
    }
}

// Global instance
let characterImageManager = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('[CharacterImageManager] Initializing on DOMContentLoaded');
    try {
        characterImageManager = new CharacterImageManager();
        // Also expose on window for easier access
        window.characterImageManager = characterImageManager;
        console.log('[CharacterImageManager] CharacterImageManager instance created');
    } catch (error) {
        console.error('[CharacterImageManager] Error creating instance:', error);
    }
    
    // Also set up a direct listener on the file input as a backup
    setTimeout(() => {
        const fileInput = document.getElementById('characterImageInput');
        if (fileInput && characterImageManager) {
            // Add backup change listener
            fileInput.addEventListener('change', function(e) {
                if (e.target.files && e.target.files.length > 0 && characterImageManager) {
                    characterImageManager.handleFileSelect(e);
                }
            });
        }
    }, 1000);
});

