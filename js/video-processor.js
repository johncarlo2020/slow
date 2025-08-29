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
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        const uploadArea = document.getElementById('upload-area');
        const videoInput = document.getElementById('video-input');
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
                alert('Please select a valid video file (MP4, M4V, MOV, H.264).');
                return;
            }

        // Validate file size (max 100MB)
        if (file.size > 100 * 1024 * 1024) {
            alert('File size must be less than 100MB.');
            return;
        }

        this.currentFile = file;
        this.uploadFile(file);
        // Do NOT reset file input here
    }

    uploadFile(file) {
        // Skip progress display for simplified interface
        
        const formData = new FormData();
        formData.append('video', file);

        const xhr = new XMLHttpRequest();

        xhr.addEventListener('load', () => {
            try {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        this.videoUrl = response.videoUrl;
                        this.showVideoControls(response.videoUrl);
                    } else {
                        console.error('Upload failed:', response.message);
                        alert('Upload failed: ' + response.message);
                    }
                } else {
                    console.error('Upload failed with status:', xhr.status, xhr.responseText);
                    alert('Upload failed. Please try again.');
                }
            } catch (error) {
                console.error('Error processing upload response:', error);
                alert('Upload failed. Please try again.');
            }
            // Reset file input after upload completes
            const videoInput = document.getElementById('video-input');
            if (videoInput) videoInput.value = "";
        });

        xhr.addEventListener('error', (event) => {
            console.error('Upload error event:', event);
            alert('Upload failed. Please try again.');
        });

        xhr.open('POST', 'api/upload.php');
        xhr.send(formData);
    }

    showVideoControls(videoUrl) {
        const uploadSection = document.getElementById('upload-section');
        const videoControls = document.getElementById('video-controls');
        const videoPreview = document.getElementById('video-preview');

        uploadSection.style.display = 'none';
        videoControls.style.display = 'block';
        videoPreview.src = videoUrl;
    }

    processVideo() {
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
            return response.json();
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
