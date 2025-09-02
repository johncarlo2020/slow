class VideoSlideshow {
    constructor() {
        this.videos = [];
        this.currentIndex = 0;
        this.isPlaying = true;
        this.maxVideos = 5;
        this.videoDuration = 0;
        this.currentTime = 0;
        this.progressInterval = null;
        
        this.initializePusher();
        this.initializeElements();
        this.initializeEventListeners();
        this.loadVideos();
    }

    initializePusher() {
        // Initialize Pusher with your credentials
        this.pusher = new Pusher('60de59064bcf7cfb6d63', {
            cluster: 'ap1'
        });

        // Subscribe to the video processing channel
        this.channel = this.pusher.subscribe('video-processing');
        
        // Listen for new video processed events
        this.channel.bind('video-processed', (data) => {
            console.log('New video processed for slideshow:', data);
            this.handleNewVideoProcessed(data);
        });
        
        console.log('Pusher initialized for slideshow');
    }

    initializeElements() {
        this.loadingScreen = document.getElementById('loading-screen');
        this.slideshowContainer = document.getElementById('slideshow-container');
        this.noVideosScreen = document.getElementById('no-videos-screen');
        this.slidesContainer = document.getElementById('slides-container');
    }

    initializeEventListeners() {
        // No UI controls to listen to
    }

    async loadVideos() {
        try {
            console.log('Loading videos for slideshow...');
            const response = await fetch(`api/get-videos.php?limit=${this.maxVideos}&page=1`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Slideshow API response:', data);

            if (data.success && data.videos.length > 0) {
                this.videos = data.videos.slice(0, this.maxVideos); // Ensure max 5 videos
                console.log(`Loaded ${this.videos.length} videos for slideshow`);
                this.createSlides();
                this.startSlideshow();
            } else {
                console.log('No videos available for slideshow');
                this.showNoVideos();
            }
        } catch (error) {
            console.error('Error loading videos for slideshow:', error);
            this.showNoVideos();
        }
    }

    createSlides() {
        this.slidesContainer.innerHTML = '';
        
        this.videos.forEach((video, index) => {
            const slide = document.createElement('div');
            slide.className = `video-slide ${index === 0 ? 'active' : ''}`;
            slide.innerHTML = `
                <video 
                    id="video-${index}"
                    muted 
                    preload="metadata"
                    onloadedmetadata="slideshow.onVideoLoaded(${index})"
                    onended="slideshow.onVideoEnded()"
                    ontimeupdate="slideshow.updateProgress()"
                >
                    <source src="${video.path}" type="video/mp4">
                </video>
            `;
            this.slidesContainer.appendChild(slide);
        });

        this.updateCounters();
    }

    startSlideshow() {
        this.hideLoading();
        this.showSlideshow();
        
        // Start with the first video
        this.currentIndex = 0;
        this.playCurrentVideo();
    }

    playCurrentVideo() {
        const video = document.getElementById(`video-${this.currentIndex}`);
        if (video) {
            // Reset and play current video
            video.currentTime = 0;
            video.play().catch(e => console.log('Video play failed:', e));
            this.updateCounters();
        }
    }

    onVideoLoaded(index) {
        if (index === this.currentIndex) {
            const video = document.getElementById(`video-${index}`);
            this.videoDuration = video.duration;
        }
    }

    onVideoEnded() {
        console.log('Video ended, moving to next');
        this.nextVideo();
    }

    updateProgress() {
        // Progress UI removed - no progress bar to update
    }

    nextVideo() {
        this.currentIndex = (this.currentIndex + 1) % this.videos.length;
        this.switchToVideo(this.currentIndex);
    }

    previousVideo() {
        this.currentIndex = this.currentIndex === 0 ? this.videos.length - 1 : this.currentIndex - 1;
        this.switchToVideo(this.currentIndex);
    }

    switchToVideo(index) {
        // Hide all slides
        const slides = document.querySelectorAll('.video-slide');
        slides.forEach(slide => slide.classList.remove('active'));
        
        // Pause all videos
        const videos = document.querySelectorAll('video');
        videos.forEach(video => {
            video.pause();
            video.currentTime = 0;
        });
        
        // Show and play current slide
        const currentSlide = slides[index];
        if (currentSlide) {
            currentSlide.classList.add('active');
            if (this.isPlaying) {
                setTimeout(() => this.playCurrentVideo(), 500); // Wait for transition
            }
        }
        
        this.updateCounters();
    }

    togglePlayPause() {
        this.isPlaying = !this.isPlaying;
        const video = document.getElementById(`video-${this.currentIndex}`);
        const icon = this.playPauseBtn.querySelector('i');
        
        if (this.isPlaying) {
            video?.play();
            icon.className = 'fas fa-pause';
        } else {
            video?.pause();
            icon.className = 'fas fa-play';
        }
    }

    updateCounters() {
        // Counter UI removed - no counters to update
    }

    handleNewVideoProcessed(data) {
        console.log('Handling new video for slideshow:', data);
        
        // Reload videos to get the latest 5
        this.loadVideos();
    }

    showNotification(message) {
        // Notifications removed for clean video-only experience
    }

    hideLoading() {
        this.loadingScreen.style.display = 'none';
    }

    showSlideshow() {
        this.slideshowContainer.style.display = 'block';
    }

    showNoVideos() {
        this.hideLoading();
        this.noVideosScreen.style.display = 'flex';
    }
}

// Global instance for video callbacks
let slideshow;

// Initialize slideshow when page loads
document.addEventListener('DOMContentLoaded', () => {
    slideshow = new VideoSlideshow();
});
