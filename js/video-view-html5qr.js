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

            if (!this.videoFilename) {
                console.error('No video filename available for QR code');
                this.showQRError(qrLoading);
                return;
            }

            // Show loading state
            qrLoading.style.display = 'flex';
            qrContainer.style.display = 'none';

            // Create URL to mobile download page with video parameter
            const baseUrl = window.location.origin + window.location.pathname.replace('/video-view.html', '');
            const mobileDownloadUrl = `${baseUrl}/mobile-download.html?video=${encodeURIComponent(this.videoFilename)}`;
            
            console.log('QR Code URL:', mobileDownloadUrl);

            // Try HTML5 QR code generation first
            if (typeof QRCode !== 'undefined') {
                this.generateHTML5QR(mobileDownloadUrl, qrLoading, qrContainer);
            } else {
                // Fallback to API-based QR generation
                this.generateAPIQR(mobileDownloadUrl, qrLoading, qrContainer);
            }

        } catch (error) {
            console.error('Error generating QR code:', error);
            this.showQRError();
        }
    }

    createDownloadPageUrl() {
        try {
            // Create a user-friendly download page URL
            const baseUrl = window.location.origin;
            const downloadPath = window.location.pathname.replace('/video-view.html', '/download.html');
            
            // Include video filename as parameter for the download page
            const downloadPageUrl = `${baseUrl}${downloadPath}?video=${encodeURIComponent(this.videoFilename)}`;
            
            return downloadPageUrl;
        } catch (error) {
            console.error('Error creating download page URL:', error);
            return null;
        }
    }

    generateHTML5QR(url, loadingElement, containerElement) {
        try {
            console.log('Generating HTML5 QR code for:', url);
            
            // Create a div for QR code
            const qrDiv = document.createElement('div');
            qrDiv.style.width = '200px';
            qrDiv.style.height = '200px';
            qrDiv.style.margin = '0 auto';
            
            // Clear container and add div
            containerElement.innerHTML = '';
            containerElement.appendChild(qrDiv);
            
            // Generate QR code using html5-qrcode
            if (typeof QRCode !== 'undefined') {
                new QRCode(qrDiv, {
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
                throw new Error('QRCode library not available');
            }
            
        } catch (error) {
            console.error('HTML5 QR generation failed:', error);
            // Fallback to API-based QR generation
            this.generateAPIQR(url, loadingElement, containerElement);
        }
    }

    generateAPIQR(url, loadingElement, containerElement) {
        try {
            console.log('Generating API-based QR code for:', url);
            
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
            console.error('API QR generation failed:', error);
            this.showQRError(loadingElement);
        }
    }

    showQRError(loadingElement = null) {
        console.log('Showing QR error...');
        
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
        
        const qrContainer = document.getElementById('qr-code-container');
        if (qrContainer) {
            qrContainer.innerHTML = `
                <div style="text-align: center; padding: 20px; color: #666;">
                    <i class="fas fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <p>Failed to generate QR code</p>
                    <small>Please use the direct download button</small>
                </div>
            `;
            qrContainer.style.display = 'flex';
        }
    }

    showError(message) {
        console.error('Showing error:', message);
        const errorElement = document.getElementById('error-message');
        const errorText = document.getElementById('error-text');
        const mainContent = document.getElementById('main-content');
        
        if (errorElement && errorText) {
            errorText.textContent = message;
            errorElement.style.display = 'block';
        }
        
        if (mainContent) {
            mainContent.style.display = 'none';
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing VideoView...');
    new VideoView();
});
