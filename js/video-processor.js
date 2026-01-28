// Global error handling
window.addEventListener('error', (event) => {
    console.error('Global JavaScript error:', event.error);
});

window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason);
    // Prevent the default behavior that logs the error to console
    event.preventDefault();
});

class VideoProcessor {
    constructor() {
        this.currentFile = null;
        this.videoUrl = null;
        this.photoUrl = null;
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        const uploadArea = document.getElementById('upload-area');
        const videoInput = document.getElementById('video-input');
        const photoInput = document.getElementById('photo-input');
        const processBtn = document.getElementById('process-btn');

        // Drag and drop functionality
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                this.handleFileSelect(files[0]);
            }
        });

        // Click to upload (only if direct target is uploadArea)
        uploadArea.addEventListener('click', (e) => {
            if (e.target === uploadArea) {
                videoInput.click();
            }
        });

        videoInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                this.handleFileSelect(e.target.files[0]);
            }
        });

        // Photo input listener
        photoInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                this.handlePhotoSelect(e.target.files[0]);
            }
        });

        // Process button
        processBtn.addEventListener('click', () => {
            this.processVideo();
        });

        // Video time updates - no longer needed for user input
        const videoPreview = document.getElementById('video-preview');
        videoPreview.addEventListener('loadedmetadata', () => {
            // Just log the duration for reference
            console.log('Video duration:', videoPreview.duration.toFixed(1), 'seconds');
            
            // Show warning if video is too short
            if (videoPreview.duration < 7) {
                const warningDiv = document.createElement('div');
                warningDiv.className = 'alert alert-warning mt-2';
                warningDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> Video is shorter than 7 seconds. Automatic slow motion requires at least 7 seconds of footage.';
                document.getElementById('video-controls').appendChild(warningDiv);
            }
        });
    }

    handleFileSelect(file) {
        // Hide only the video upload area, keep photo upload visible
        const uploadArea = document.getElementById('upload-area');
        if (uploadArea) uploadArea.style.display = 'none';
        // Show loader
        let loader = document.getElementById('upload-loader');
        if (!loader) {
            // Create loader element if it doesn't exist
            loader = document.createElement('div');
            loader.id = 'upload-loader';
            loader.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                color: white;
                font-size: 1.5em;
            `;
            loader.innerHTML = '<div>Uploading video, please wait...</div>';
            document.body.appendChild(loader);
        } else {
            loader.style.display = 'flex';
        }
        // Validate file type (accept H.264 formats: .mp4, .m4v, .mov)
        const acceptedTypes = [
            'video/mp4',
            'video/x-m4v',
            'video/quicktime', // .mov
        ];
        const acceptedExtensions = [
            '.mp4', '.m4v', '.mov'
        ];
        const fileType = file.type;
        const fileName = file.name.toLowerCase();
        const isAcceptedType = acceptedTypes.includes(fileType);
        const isAcceptedExt = acceptedExtensions.some(ext => fileName.endsWith(ext));
        if (!(fileType.startsWith('video/') || isAcceptedType || isAcceptedExt)) {
            if (loader) loader.style.display = 'none';
            if (uploadArea) uploadArea.style.display = 'flex';
            alert('Please select a valid video file (MP4, M4V, MOV, H.264).');
            return;
        }
        // Validate file size (max 100MB)
        if (file.size > 100 * 1024 * 1024) {
            if (loader) loader.style.display = 'none';
            if (uploadArea) uploadArea.style.display = 'flex';
            alert('File size must be less than 100MB.');
            return;
        }
        this.currentFile = file;
        this.uploadFile(file);
        // Do NOT reset file input here
    }

    uploadFile(file) {
        const formData = new FormData();
        formData.append('video', file);

        const xhr = new XMLHttpRequest();

        xhr.addEventListener('load', () => {
            try {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        this.videoUrl = response.videoUrl;
                        this.showToast('Video uploaded and saved successfully!', 'success');
                        this.showVideoControls(response.videoUrl);
                    } else {
                        const loader = document.getElementById('upload-loader');
                        const uploadArea = document.getElementById('upload-area');
                        if (loader) loader.style.display = 'none';
                        if (uploadArea) uploadArea.style.display = 'flex';
                        console.error('Upload failed:', response.message);
                        alert('Upload failed: ' + response.message);
                    }
                } else {
                    const loader = document.getElementById('upload-loader');
                    const uploadArea = document.getElementById('upload-area');
                    if (loader) loader.style.display = 'none';
                    if (uploadArea) uploadArea.style.display = 'flex';
                    console.error('Upload failed with status:', xhr.status, xhr.responseText);
                    alert('Upload failed. Please try again.');
                }
            } catch (error) {
                const loader = document.getElementById('upload-loader');
                const uploadArea = document.getElementById('upload-area');
                if (loader) loader.style.display = 'none';
                if (uploadArea) uploadArea.style.display = 'flex';
                console.error('Error processing upload response:', error);
                alert('Upload failed. Please try again.');
            }
            // Reset file input after upload completes
            const videoInput = document.getElementById('video-input');
            if (videoInput) videoInput.value = "";
        });

        xhr.addEventListener('error', (event) => {
            if (loader) loader.style.display = 'none';
            console.error('Upload error event:', event);
            alert('Upload failed. Please try again.');
        });

        xhr.open('POST', 'api/upload.php');
        xhr.send(formData);
    }

    handlePhotoSelect(file) {
        // Validate file type
        const acceptedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        const fileType = file.type.toLowerCase();
        
        if (!acceptedTypes.includes(fileType)) {
            alert('Please select a valid image file (JPG or PNG).');
            return;
        }
        
        // Validate file size (max 10MB)
        if (file.size > 10 * 1024 * 1024) {
            alert('Photo size must be less than 10MB.');
            return;
        }
        
        // Show preview immediately and hide upload area
        const reader = new FileReader();
        reader.onload = (e) => {
            const photoPreview = document.getElementById('photo-preview');
            const photoPreviewSection = document.getElementById('photo-preview-section');
            const photoUploadArea = document.getElementById('photo-upload-area');
            
            if (photoPreview && photoPreviewSection) {
                photoPreview.src = e.target.result;
                photoPreviewSection.style.display = 'block';
            }
            
            // Hide the photo upload area
            if (photoUploadArea) {
                photoUploadArea.style.display = 'none';
            }
        };
        reader.readAsDataURL(file);
        
        this.uploadPhoto(file);
    }

    uploadPhoto(file) {
        const formData = new FormData();
        formData.append('photo', file);

        const xhr = new XMLHttpRequest();

        xhr.addEventListener('load', () => {
            try {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        this.photoUrl = response.photoUrl;
                        this.showToast('Photo uploaded successfully!', 'success');
                        
                        // Update UI to show photo uploaded
                        const photoUploadArea = document.getElementById('photo-upload-area');
                        photoUploadArea.style.borderColor = '#28a745';
                        photoUploadArea.querySelector('.upload-text').textContent = 'Photo Uploaded ✓';
                        photoUploadArea.querySelector('.upload-text').style.color = '#28a745';
                    } else {
                        console.error('Photo upload failed:', response.message);
                        alert('Photo upload failed: ' + response.message);
                    }
                } else {
                    console.error('Photo upload failed with status:', xhr.status, xhr.responseText);
                    alert('Photo upload failed. Please try again.');
                }
            } catch (error) {
                console.error('Error processing photo upload response:', error);
                alert('Photo upload failed. Please try again.');
            }
        });

        xhr.addEventListener('error', (event) => {
            console.error('Photo upload error event:', event);
            alert('Photo upload failed. Please try again.');
        });

        xhr.open('POST', 'api/upload.php');
        xhr.send(formData);
    }

    showVideoControls(videoUrl) {
        const videoControls = document.getElementById('video-controls');
        const videoPreview = document.getElementById('video-preview');
        const loader = document.getElementById('upload-loader');
        if (videoControls) videoControls.style.display = 'block';
        if (videoPreview) {
            videoPreview.src = videoUrl;
            videoPreview.onloadeddata = function() {
                if (loader) loader.style.display = 'none';
            };
        } else {
            if (loader) loader.style.display = 'none';
        }
    }

    processVideo() {
        // Check if photo is uploaded
        if (!this.photoUrl) {
            alert('Please upload a photo before processing the video.');
            return;
        }

        // Fixed values for automatic processing
        const startTime = 4.0;  // Always start at 4 seconds
        const endTime = 7.0;    // Always end at 7 seconds
        const slowFactor = 0.25; // Always 4x slower
        const qualityMode = 'ultra'; // Always best quality with overlay

        const videoPreview = document.getElementById('video-preview');
        
        // Check if video is long enough
        if (videoPreview.duration < 7) {
            alert('Video must be at least 7 seconds long for automatic slow motion processing.');
            return;
        }

        this.showProcessingStatus();

        const formData = new FormData();
        formData.append('videoUrl', this.videoUrl);
        formData.append('photoUrl', this.photoUrl);
        formData.append('startTime', startTime);
        formData.append('endTime', endTime);
        formData.append('slowFactor', slowFactor);
        formData.append('qualityMode', qualityMode);
        formData.append('addOverlay', 'true'); // Enable overlay

        fetch('api/process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Process response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            // Clone the response to read it twice (once for debugging, once for parsing)
            return response.clone().text().then(text => {
                console.log('Raw response (first 500 chars):', text.substring(0, 500));
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Full response:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            console.log('Process response data:', data);
            if (data.success) {
                this.showResults(data.originalVideo, data.processedVideo);
            } else {
                console.error('Processing failed:', data.message);
                this.showToast('Processing failed: ' + data.message, 'error');
                this.hideProcessingStatus();
            }
        })
        .catch(error => {
            console.error('Processing error:', error);
            this.showToast('Processing failed. Please try again.', 'error');
            this.hideProcessingStatus();
        });
    }

    showProcessingStatus() {
        const videoProgress = document.getElementById('video-progress');
        const processBtn = document.getElementById('process-btn');
        
        // Hide process button and show progress
        processBtn.style.display = 'none';
        videoProgress.style.display = 'block';
        
        // Simulate progress (since FFmpeg progress is complex to track)
        this.simulateProgress();
    }

    hideProcessingStatus() {
        const videoProgress = document.getElementById('video-progress');
        const processBtn = document.getElementById('process-btn');
        
        // Hide progress and show process button
        videoProgress.style.display = 'none';
        processBtn.style.display = 'block';
    }

    simulateProgress() {
        const progressBar = document.getElementById('video-progress-bar');
        const progressPercent = document.getElementById('progress-percent');
        let progress = 0;
        
        const interval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 95) progress = 95;
            
            progressBar.style.width = progress + '%';
            progressPercent.textContent = Math.round(progress) + '%';
            
            if (progress >= 95) {
                clearInterval(interval);
            }
        }, 1000);
    }

    showResults(originalVideo, processedVideo) {
        // Complete the progress bar first
        const progressBar = document.getElementById('video-progress-bar');
        const progressPercent = document.getElementById('progress-percent');
        progressBar.style.width = '100%';
        progressPercent.textContent = '100%';
        
        // Wait a moment to show 100% completion
        setTimeout(() => {
            // Hide processing and show results
            document.getElementById('processing-status').style.display = 'none';
            document.getElementById('result-section').style.display = 'block';
            
            document.getElementById('original-video').src = originalVideo;
            document.getElementById('processed-video').src = processedVideo;
            document.getElementById('download-btn').href = processedVideo;
            
            // Show success toast
            this.showToast('Video processing completed successfully!', 'success');
            
            // Auto-refresh page after 5 seconds
            setTimeout(() => {
                location.reload();
            }, 5000);
        }, 1000);
    }
    
    showToast(message, type = 'success') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        // Add to page
        document.body.appendChild(toast);
        
        // Show with animation
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Hide after 4 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => document.body.removeChild(toast), 300);
        }, 4000);
    }
}

// Initialize the video processor when the page loads
document.addEventListener('DOMContentLoaded', () => {
    new VideoProcessor();
});
