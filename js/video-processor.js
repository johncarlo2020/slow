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

        // Click to upload
        uploadArea.addEventListener('click', () => {
            videoInput.click();
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
        // Validate file type
        if (!file.type.startsWith('video/')) {
            alert('Please select a valid video file.');
            return;
        }

        // Validate file size (max 100MB)
        if (file.size > 100 * 1024 * 1024) {
            alert('File size must be less than 100MB.');
            return;
        }

        this.currentFile = file;
        this.uploadFile(file);
    }

    uploadFile(file) {
        const progressContainer = document.getElementById('progress-container');
        const uploadProgress = document.getElementById('upload-progress');
        
        progressContainer.style.display = 'block';

        const formData = new FormData();
        formData.append('video', file);

        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                uploadProgress.style.width = percentComplete + '%';
                uploadProgress.textContent = Math.round(percentComplete) + '%';
            }
        });

        xhr.addEventListener('load', () => {
            try {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        this.videoUrl = response.videoUrl;
                        this.showVideoControls(response.videoUrl);
                        progressContainer.style.display = 'none';
                    } else {
                        console.error('Upload failed:', response.message);
                        alert('Upload failed: ' + response.message);
                        progressContainer.style.display = 'none';
                    }
                } else {
                    console.error('Upload failed with status:', xhr.status, xhr.responseText);
                    alert('Upload failed. Please try again.');
                    progressContainer.style.display = 'none';
                }
            } catch (error) {
                console.error('Error processing upload response:', error);
                alert('Upload failed. Please try again.');
                progressContainer.style.display = 'none';
            }
        });

        xhr.addEventListener('error', (event) => {
            console.error('Upload error event:', event);
            alert('Upload failed. Please try again.');
            progressContainer.style.display = 'none';
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
                alert('Processing failed: ' + data.message);
                this.hideProcessingStatus();
            }
        })
        .catch(error => {
            console.error('Processing error:', error);
            alert('Processing failed. Please try again.');
            this.hideProcessingStatus();
        });
    }

    showProcessingStatus() {
        document.getElementById('video-controls').style.display = 'none';
        document.getElementById('processing-status').style.display = 'block';
        
        // Simulate progress (since FFmpeg progress is complex to track)
        this.simulateProgress();
    }

    hideProcessingStatus() {
        document.getElementById('processing-status').style.display = 'none';
        document.getElementById('video-controls').style.display = 'block';
    }

    simulateProgress() {
        const progressBar = document.getElementById('process-progress');
        let progress = 0;
        
        const interval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 95) progress = 95;
            
            progressBar.style.width = progress + '%';
            
            if (progress >= 95) {
                clearInterval(interval);
            }
        }, 1000);
    }

    showResults(originalVideo, processedVideo) {
        document.getElementById('processing-status').style.display = 'none';
        document.getElementById('result-section').style.display = 'block';
        
        document.getElementById('original-video').src = originalVideo;
        document.getElementById('processed-video').src = processedVideo;
        document.getElementById('download-btn').href = processedVideo;
        
        // Complete the progress bar
        document.getElementById('process-progress').style.width = '100%';
    }
}

// Initialize the video processor when the page loads
document.addEventListener('DOMContentLoaded', () => {
    new VideoProcessor();
});
