class VideoView {
    constructor() {
        this.videoFilename = null;
        this.videoData = null;
        this.downloadUrl = null;
        
        this.initializeEventListeners();
        this.loadVideo();
    }

    initializeEventListeners() {
        // All action buttons have been removed from the UI
        // QR code functionality is handled separately in generateQRCode method
        console.log('Event listeners initialized - no buttons to bind');
    }

    loadVideo() {
        // Get video filename from URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        this.videoFilename = urlParams.get('video');

        if (!this.videoFilename) {
            this.showError('No video specified in URL');
            return;
        }

        this.fetchVideoInfo();
    }

    async fetchVideoInfo() {
        try {
            const response = await fetch(`api/get-video-info.php?filename=${encodeURIComponent(this.videoFilename)}`);
            const data = await response.json();

            if (data.success) {
                this.videoData = data.video;
                this.downloadUrl = data.downloadUrl;
                this.displayVideo();
                this.generateQRCode();
            } else {
                throw new Error(data.message || 'Failed to load video information');
            }
        } catch (error) {
            console.error('Error loading video:', error);
            this.showError('Failed to load video: ' + error.message);
        }
    }

    displayVideo() {
        // Update video player
        const videoPlayer = document.getElementById('video-player');
        const videoSource = document.getElementById('video-source');
        
        if (!videoPlayer || !videoSource) {
            console.error('Video elements not found in DOM');
            this.showError('Video player not found');
            return;
        }
        
        videoSource.src = this.videoData.path;
        videoPlayer.load();

        // Show main content
        const loadingState = document.getElementById('loading-state');
        const mainContent = document.getElementById('main-content');
        
        if (loadingState) loadingState.style.display = 'none';
        if (mainContent) mainContent.style.display = 'block';

        // Update page title
        document.title = `${this.videoData.displayName} - Video View`;
    }

    async generateQRCode() {
        try {
            const qrContainer = document.getElementById('qr-code-container');
            const qrCanvas = document.getElementById('qr-canvas');
            const qrLoading = document.getElementById('qr-loading');

            // Create the mobile download URL for QR code
            const baseUrl = window.location.origin + window.location.pathname.replace('/video-view.html', '');
            const qrUrl = `${baseUrl}/mobile-download.html?video=${encodeURIComponent(this.videoFilename)}`;

            // Use Google Charts API for QR generation (more reliable)
            this.generateGoogleChartsQR(qrUrl, qrLoading, qrContainer);

        } catch (error) {
            console.error('Error generating QR code:', error);
            
            // Try fallback method
            const baseUrl = window.location.origin + window.location.pathname.replace('/video-view.html', '');
            const qrUrl = `${baseUrl}/mobile-download.html?video=${encodeURIComponent(this.videoFilename)}`;
            this.generateFallbackQR(qrUrl, document.getElementById('qr-loading'), document.getElementById('qr-code-container'));
        }
    }

    generateGoogleChartsQR(url, loadingElement, containerElement) {
        try {
            // Use Google Charts QR API
            const qrImageUrl = `https://chart.googleapis.com/chart?cht=qr&chl=${encodeURIComponent(url)}&chs=200x200&choe=UTF-8&chld=M|0`;
            
            // Create image element
            const qrImage = document.createElement('img');
            qrImage.src = qrImageUrl;
            qrImage.alt = 'QR Code';
            qrImage.style.cssText = 'max-width: 200px; border: 2px solid #f0f0f0; border-radius: 10px; display: block; margin: 0 auto;';
            
            qrImage.onload = () => {
                loadingElement.style.display = 'none';
                containerElement.innerHTML = '';
                containerElement.appendChild(qrImage);
                containerElement.style.display = 'block';
            };
            
            qrImage.onerror = () => {
                console.warn('Google Charts QR failed, trying fallback');
                this.generateFallbackQR(url, loadingElement, containerElement);
            };
            
        } catch (error) {
            console.error('Google Charts QR generation failed:', error);
            this.generateFallbackQR(url, loadingElement, containerElement);
        }
    }

    generateFallbackQR(url, loadingElement, containerElement) {
        try {
            // Create a simple QR code using QR Server API as backup
            const qrImageUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(url)}`;
            
            const qrImage = document.createElement('img');
            qrImage.src = qrImageUrl;
            qrImage.alt = 'QR Code';
            qrImage.style.cssText = 'max-width: 200px; border: 2px solid #f0f0f0; border-radius: 10px; display: block; margin: 0 auto;';
            
            qrImage.onload = () => {
                loadingElement.style.display = 'none';
                containerElement.innerHTML = '';
                containerElement.appendChild(qrImage);
                containerElement.style.display = 'block';
            };
            
            qrImage.onerror = () => {
                loadingElement.innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle text-warning fs-2 mb-2"></i>
                        <p class="text-muted">QR code unavailable</p>
                        <button class="btn btn-primary btn-sm" onclick="videoView.copyDownloadLink()">
                            <i class="fas fa-copy me-1"></i>Copy Download Link
                        </button>
                    </div>
                `;
            };
            
        } catch (fallbackError) {
            console.error('Fallback QR generation failed:', fallbackError);
            loadingElement.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-warning fs-2 mb-2"></i>
                    <p class="text-muted">QR code unavailable</p>
                    <button class="btn btn-primary btn-sm" onclick="videoView.copyDownloadLink()">
                        <i class="fas fa-copy me-1"></i>Copy Download Link
                    </button>
                </div>
            `;
        }
    }

    copyDownloadLink() {
        const baseUrl = window.location.origin + window.location.pathname.replace('/video-view.html', '');
        const downloadUrl = `${baseUrl}/mobile-download.html?video=${encodeURIComponent(this.videoFilename)}`;
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(downloadUrl).then(() => {
                this.showToast('Download link copied to clipboard!', 'success');
            }).catch(err => {
                console.error('Failed to copy link:', err);
                this.fallbackCopyToClipboard(downloadUrl);
            });
        } else {
            this.fallbackCopyToClipboard(downloadUrl);
        }
    }

    fallbackCopyToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            this.showToast('Download link copied to clipboard!', 'success');
        } catch (err) {
            console.error('Failed to copy link:', err);
            this.showToast('Failed to copy link. Please copy manually: ' + text, 'error');
        }
        
        document.body.removeChild(textArea);
    }

    shareVideo() {
        const baseUrl = window.location.origin + window.location.pathname.replace('/video-view.html', '');
        const shareUrl = `${baseUrl}/mobile-download.html?video=${encodeURIComponent(this.videoFilename)}`;
        const shareText = `Check out this video: ${this.videoFilename}`;

        if (navigator.share) {
            navigator.share({
                title: 'Video Download',
                text: shareText,
                url: shareUrl
            }).catch(err => {
                console.error('Error sharing:', err);
                this.copyDownloadLink(); // Fallback to copying link
            });
        } else {
            this.copyDownloadLink(); // Fallback to copying link
        }
    }

    showToast(message, type = 'info') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            ${message}
        `;
        
        document.body.appendChild(toast);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (document.body.contains(toast)) {
                document.body.removeChild(toast);
            }
        }, 3000);
    }

    async copyDownloadLink() {
        try {
            const baseUrl = window.location.origin + window.location.pathname.replace('/video-view.html', '');
            const downloadLink = `${baseUrl}/${this.downloadUrl}`;
            
            await navigator.clipboard.writeText(downloadLink);
            this.showToast('Download link copied to clipboard', 'success');
        } catch (error) {
            console.error('Error copying link:', error);
            
            // Fallback for browsers that don't support clipboard API
            const textArea = document.createElement('textarea');
            const baseUrl = window.location.origin + window.location.pathname.replace('/video-view.html', '');
            textArea.value = `${baseUrl}/${this.downloadUrl}`;
            document.body.appendChild(textArea);
            textArea.select();
            
            try {
                document.execCommand('copy');
                this.showToast('Download link copied to clipboard', 'success');
            } catch (fallbackError) {
                this.showToast('Failed to copy link', 'error');
            }
            
            document.body.removeChild(textArea);
        }
    }

    showError(message) {
        document.getElementById('loading-state').style.display = 'none';
        document.getElementById('main-content').style.display = 'none';
        document.getElementById('error-text').textContent = message;
        document.getElementById('error-message').style.display = 'block';
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

// Initialize when page loads
let videoView;
document.addEventListener('DOMContentLoaded', () => {
    videoView = new VideoView();
});
