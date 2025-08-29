class VideoView {
    constructor() {
        this.videoFilename = null;
        this.videoData = null;
        this.downloadUrl = null;
        
        console.log('VideoView constructor called');
        this.loadVideo();
    }

    loadVideo() {
        console.log('Loading video...');
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
            console.log('Fetching video info for:', this.videoFilename);
            const response = await fetch(`api/get-video-info.php?filename=${encodeURIComponent(this.videoFilename)}`);
            const data = await response.json();

            if (data.success) {
                console.log('Video info loaded:', data.video);
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
        console.log('Displaying video...');
        console.log('Video data:', this.videoData);
        console.log('Download URL:', this.downloadUrl);
        
        // Update video player
        const videoPlayer = document.getElementById('video-player');
        const videoSource = document.getElementById('video-source');
        const mainContent = document.getElementById('main-content');
        const directDownload = document.getElementById('direct-download');

        if (!videoPlayer || !videoSource || !mainContent) {
            this.showError('Video player elements not found');
            return;
        }

        // Set video source - handle different possible data structures
        let videoUrl = this.videoData.url || this.videoData.path || this.videoData.videoUrl;
        if (!videoUrl) {
            console.error('No video URL found in data:', this.videoData);
            this.showError('Video URL not found');
            return;
        }
        
        console.log('Setting video source to:', videoUrl);
        videoSource.src = videoUrl;
        
        // Enable video controls and interactions
        videoPlayer.setAttribute('controls', 'controls');
        videoPlayer.style.pointerEvents = 'auto';
        videoPlayer.muted = false;
        videoPlayer.autoplay = false;
        videoPlayer.loop = false;
        
        videoPlayer.load();

        // Set up direct download
        if (directDownload && this.downloadUrl) {
            directDownload.href = this.downloadUrl;
            directDownload.onclick = (e) => {
                // Track download
                console.log('Direct download initiated for:', this.videoFilename);
            };
        }

        // Show main content
        mainContent.style.display = 'flex';
    }

    async generateQRCode() {
        try {
            console.log('Generating QR code...');
            const qrContainer = document.getElementById('qr-code-container');
            const qrLoading = document.getElementById('qr-loading');

            if (!qrContainer || !qrLoading) {
                console.error('QR elements not found');
                return;
            }

            if (!this.downloadUrl) {
                console.error('No download URL available for QR code');
                this.showQRError(qrLoading);
                return;
            }

            // Show loading state
            qrLoading.style.display = 'flex';
            qrContainer.style.display = 'none';

            // Generate QR code using html5-qrcode library
            this.generateHTML5QR(this.downloadUrl, qrLoading, qrContainer);

        } catch (error) {
            console.error('Error generating QR code:', error);
            this.showQRError();
        }
    }

    generateHTML5QR(url, loadingElement, containerElement) {
        try {
            // Create a canvas element for QR code generation
            const canvas = document.createElement('canvas');
            const canvasId = 'qr-canvas-' + Date.now();
            canvas.id = canvasId;
            canvas.width = 200;
            canvas.height = 200;
            
            // Clear container and add canvas
            containerElement.innerHTML = '';
            containerElement.appendChild(canvas);
            
            // Use QRCode from html5-qrcode library
            if (typeof QRCode !== 'undefined') {
                const qr = new QRCode(canvas, {
                    text: url,
                    width: 200,
                    height: 200,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });
                
                // Hide loading and show QR
                loadingElement.style.display = 'none';
                containerElement.style.display = 'flex';
                
                console.log('HTML5 QR code generated successfully');
            } else {
                // Fallback to image-based QR generation
                this.generateImageQR(url, loadingElement, containerElement);
            }
            
        } catch (error) {
            console.error('HTML5 QR generation failed:', error);
            // Fallback to image-based QR generation
            this.generateImageQR(url, loadingElement, containerElement);
        }
    }

    generateImageQR(url, loadingElement, containerElement) {
        try {
            console.log('Generating image-based QR code...');
            
            // Create QR code image element
            const qrImg = document.createElement('img');
            qrImg.style.width = '200px';
            qrImg.style.height = '200px';
            qrImg.style.display = 'block';
            qrImg.style.margin = '0';
            
            // Use QR Server API as primary
            const qrServerUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(url)}`;
            
            qrImg.onload = () => {
                console.log('QR code image loaded successfully');
                containerElement.innerHTML = '';
                containerElement.appendChild(qrImg);
                loadingElement.style.display = 'none';
                containerElement.style.display = 'flex';
            };
            
            qrImg.onerror = () => {
                console.log('QR Server failed, trying Google Charts...');
                // Fallback to Google Charts
                const googleUrl = `https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=${encodeURIComponent(url)}`;
                qrImg.src = googleUrl;
                
                qrImg.onerror = () => {
                    console.error('All QR generation methods failed');
                    this.showQRError(loadingElement);
                };
            };
            
            qrImg.src = qrServerUrl;
            
        } catch (error) {
            console.error('Image QR generation failed:', error);
            this.showQRError(loadingElement);
        }
    }
            }

            // Create the direct download URL for QR code
            const baseUrl = window.location.origin + window.location.pathname.replace('/video-view.html', '');
            const qrUrl = `${baseUrl}/${this.videoData.path}`;
            
            console.log('QR URL:', qrUrl);

            // Use QR Server API as primary method (more reliable)
            this.generateQRWithAPI(qrUrl, qrLoading, qrContainer);

        } catch (error) {
            console.error('Error generating QR code:', error);
            this.showQRError();
        }
    }

    generateQRWithAPI(url, loadingElement, containerElement) {
        try {
            console.log('Generating QR with API for:', url);
            
            // Use QR Server API (more reliable than Google Charts)
            const qrSize = 150;
            const qrImageUrl = `https://api.qrserver.com/v1/create-qr-code/?size=${qrSize}x${qrSize}&data=${encodeURIComponent(url)}`;
            
            const qrImage = new Image();
            qrImage.crossOrigin = 'anonymous';
            qrImage.src = qrImageUrl;
            
            qrImage.onload = () => {
                console.log('QR image loaded successfully');
                loadingElement.style.display = 'none';
                containerElement.innerHTML = '';
                containerElement.appendChild(qrImage);
                containerElement.style.display = 'flex';
            };
            
            qrImage.onerror = () => {
                console.error('QR API failed, trying Google Charts...');
                this.generateGoogleQR(url, loadingElement, containerElement);
            };
            
        } catch (error) {
            console.error('QR API generation failed:', error);
            this.generateGoogleQR(url, loadingElement, containerElement);
        }
    }

    generateGoogleQR(url, loadingElement, containerElement) {
        try {
            console.log('Generating QR with Google Charts for:', url);
            
            const qrSize = 150;
            const qrImageUrl = `https://chart.googleapis.com/chart?chs=${qrSize}x${qrSize}&cht=qr&chl=${encodeURIComponent(url)}`;
            
            const qrImage = new Image();
            qrImage.crossOrigin = 'anonymous';
            qrImage.src = qrImageUrl;
            
            qrImage.onload = () => {
                console.log('Google QR image loaded successfully');
                loadingElement.style.display = 'none';
                containerElement.innerHTML = '';
                containerElement.appendChild(qrImage);
                containerElement.style.display = 'flex';
            };
            
            qrImage.onerror = () => {
                console.error('Google QR also failed, showing error');
                this.showQRError(loadingElement);
            };
            
        } catch (error) {
            console.error('Google QR generation failed:', error);
            this.showQRError(loadingElement);
        }
    }

    showQRError(loadingElement = null) {
        const element = loadingElement || document.getElementById('qr-loading');
        if (element) {
            element.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-warning mb-2" style="font-size: 2rem;"></i>
                    <p class="text-muted mb-2">QR code unavailable</p>
                    <button class="btn btn-primary btn-sm" onclick="videoView.copyDownloadLink()">
                        <i class="fas fa-copy me-1"></i>Copy Link
                    </button>
                </div>
            `;
        }
    }

    copyDownloadLink() {
        const baseUrl = window.location.origin + window.location.pathname.replace('/video-view.html', '');
        const downloadUrl = `${baseUrl}/${this.videoData.path}`;
        
        // Try to copy to clipboard
        if (navigator.clipboard) {
            navigator.clipboard.writeText(downloadUrl).then(() => {
                console.log('Download link copied to clipboard');
                alert('Download link copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy link:', err);
                this.fallbackCopyToClipboard(downloadUrl);
            });
        } else {
            this.fallbackCopyToClipboard(downloadUrl);
        }
    }

    fallbackCopyToClipboard(text) {
        // Fallback method for older browsers
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
            console.log('Fallback copy successful');
            alert('Download link copied to clipboard!');
        } catch (err) {
            console.error('Fallback copy failed:', err);
            alert('Could not copy link. Please copy manually: ' + text);
        } finally {
            document.body.removeChild(textArea);
        }
    }

    showError(message) {
        console.error('Showing error:', message);
        const loadingState = document.getElementById('loading-state');
        const mainContent = document.getElementById('main-content');
        const errorText = document.getElementById('error-text');
        const errorMessage = document.getElementById('error-message');
        
        if (loadingState) loadingState.style.display = 'none';
        if (mainContent) mainContent.style.display = 'none';
        if (errorText) errorText.textContent = message;
        if (errorMessage) errorMessage.style.display = 'block';
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
    console.log('DOM loaded, initializing VideoView...');
    videoView = new VideoView();
});
