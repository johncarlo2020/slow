class TemplateAudioUploader {
    constructor() {
        this.initializeEventListeners();
        this.loadCurrentFiles();
    }

    initializeEventListeners() {
        // Template upload
        const templateArea = document.getElementById('template-upload-area');
        const templateInput = document.getElementById('template-input');

        this.setupDragAndDrop(templateArea, templateInput, 'template');
        
        templateInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                this.handleFileSelect(e.target.files[0], 'template');
            }
        });

        // Audio upload
        const audioArea = document.getElementById('audio-upload-area');
        const audioInput = document.getElementById('audio-input');

        this.setupDragAndDrop(audioArea, audioInput, 'audio');
        
        audioInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                this.handleFileSelect(e.target.files[0], 'audio');
            }
        });
    }

    setupDragAndDrop(area, input, type) {
        area.addEventListener('dragover', (e) => {
            e.preventDefault();
            area.classList.add('dragover');
        });

        area.addEventListener('dragleave', () => {
            area.classList.remove('dragover');
        });

        area.addEventListener('drop', (e) => {
            e.preventDefault();
            area.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                this.handleFileSelect(files[0], type);
            }
        });

        area.addEventListener('click', () => {
            input.click();
        });
    }

    handleFileSelect(file, type) {
        // Validate file type
        if (type === 'template') {
            if (!file.type.startsWith('image/')) {
                this.showStatus('Please select a valid image file (PNG, JPG, etc.)', 'error', type);
                return;
            }
            if (file.size > 10 * 1024 * 1024) { // 10MB limit
                this.showStatus('Template file must be less than 10MB', 'error', type);
                return;
            }
        } else if (type === 'audio') {
            if (!file.type.startsWith('audio/')) {
                this.showStatus('Please select a valid audio file (MP3, WAV, etc.)', 'error', type);
                return;
            }
            if (file.size > 20 * 1024 * 1024) { // 20MB limit
                this.showStatus('Audio file must be less than 20MB', 'error', type);
                return;
            }
        }

        this.uploadFile(file, type);
    }

    uploadFile(file, type) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('type', type);

        // Show upload progress
        this.showStatus('Uploading...', 'info', type);

        fetch('api/upload-assets.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                this.showStatus(data.message, 'success', type);
                this.showPreview(file, type);
                // Reload current files to show the update
                setTimeout(() => {
                    this.loadCurrentFiles();
                }, 1000);
            } else {
                this.showStatus('Upload failed: ' + data.message, 'error', type);
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            this.showStatus('Upload failed. Please try again.', 'error', type);
        });
    }

    showPreview(file, type) {
        const previewContainer = document.getElementById(`${type}-preview`);
        previewContainer.innerHTML = '';

        if (type === 'template' && file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.className = 'file-preview';
            img.alt = 'Template Preview';
            previewContainer.appendChild(img);
        } else if (type === 'audio' && file.type.startsWith('audio/')) {
            const audio = document.createElement('audio');
            audio.src = URL.createObjectURL(file);
            audio.controls = true;
            audio.className = 'w-100 mt-2';
            previewContainer.appendChild(audio);
        }
    }

    showStatus(message, type, fileType) {
        const statusContainer = document.getElementById(`${fileType}-status`);
        statusContainer.innerHTML = `<div class="status-message status-${type}">${message}</div>`;
        
        // Auto-hide success messages after 3 seconds
        if (type === 'success') {
            setTimeout(() => {
                statusContainer.innerHTML = '';
            }, 3000);
        }
    }

    async loadCurrentFiles() {
        try {
            const response = await fetch('api/get-current-assets.php');
            const data = await response.json();

            if (data.success) {
                this.displayCurrentFiles(data.template, 'template');
                this.displayCurrentFiles(data.audio, 'audio');
            } else {
                console.error('Failed to load current files:', data.message);
            }
        } catch (error) {
            console.error('Error loading current files:', error);
            document.getElementById('current-template').innerHTML = '<div class="text-muted">Error loading template info</div>';
            document.getElementById('current-audio').innerHTML = '<div class="text-muted">Error loading audio info</div>';
        }
    }

    displayCurrentFiles(fileInfo, type) {
        const container = document.getElementById(`current-${type}`);
        
        if (!fileInfo || !fileInfo.exists) {
            container.innerHTML = `
                <div class="text-center text-muted">
                    <i class="fas fa-${type === 'template' ? 'image' : 'music'} fa-2x mb-2"></i>
                    <p>No ${type} uploaded yet</p>
                </div>
            `;
            return;
        }

        const sizeFormatted = this.formatFileSize(fileInfo.size);
        const dateFormatted = new Date(fileInfo.modified * 1000).toLocaleString();

        container.innerHTML = `
            <div class="file-item">
                <div class="file-info">
                    <strong>${fileInfo.filename}</strong><br>
                    <small class="text-muted">
                        Size: ${sizeFormatted} • Modified: ${dateFormatted}
                    </small>
                </div>
                <div class="file-actions">
                    ${type === 'template' ? 
                        `<button class="btn btn-sm btn-outline-primary" onclick="uploader.previewTemplate('${fileInfo.path}')">
                            <i class="fas fa-eye"></i> Preview
                        </button>` : 
                        `<button class="btn btn-sm btn-outline-primary" onclick="uploader.previewAudio('${fileInfo.path}')">
                            <i class="fas fa-play"></i> Play
                        </button>`
                    }
                    <button class="btn btn-sm btn-outline-danger" onclick="uploader.deleteFile('${type}')">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        `;
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    previewTemplate(path) {
        // Create modal or popup to show template preview
        const modal = document.createElement('div');
        modal.innerHTML = `
            <div class="modal fade" id="templateModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Template Preview</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="${path}" class="img-fluid" alt="Template Preview">
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        const modalInstance = new bootstrap.Modal(document.getElementById('templateModal'));
        modalInstance.show();
        
        // Clean up modal when hidden
        document.getElementById('templateModal').addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }

    previewAudio(path) {
        // Create audio preview
        const audio = new Audio(path);
        audio.play().catch(error => {
            console.error('Error playing audio:', error);
            alert('Could not play audio file');
        });
    }

    async deleteFile(type) {
        if (!confirm(`Are you sure you want to delete the current ${type}?`)) {
            return;
        }

        try {
            const response = await fetch('api/delete-asset.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ type: type })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showStatus(data.message, 'success', type);
                this.loadCurrentFiles();
            } else {
                this.showStatus('Delete failed: ' + data.message, 'error', type);
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.showStatus('Delete failed. Please try again.', 'error', type);
        }
    }
}

// Initialize when page loads
let uploader;
document.addEventListener('DOMContentLoaded', () => {
    uploader = new TemplateAudioUploader();
});
