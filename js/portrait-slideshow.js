class PortraitSlideshow {
    constructor() {
        // Photos
        this.photos = [];
        this.currentPhotoIndex = 0;
        this.photoTimer = null;

        // Videos
        this.videos = [];
        this.currentVideoIndex = 0;
        this.currentVideoSet = 0;
        this.maxVideosPerSet = 5;

        // State
        this.isLoading = true;
        
        this.initializeElements();
        this.loadContent();
    }

    initializeElements() {
        this.loadingScreen = document.getElementById('loading-screen');
        this.portraitContainer = document.getElementById('portrait-container');
        this.photoSection = document.getElementById('photo-section');
        this.videoSection = document.getElementById('video-section');
        this.photoSlidesContainer = document.getElementById('photo-slides-container');
        this.videoSlidesContainer = document.getElementById('video-slides-container');
        this.statusInfo = document.getElementById('status-info');
        this.photoCounter = document.getElementById('photo-counter');
        this.videoCounter = document.getElementById('video-counter');
    }

    async loadContent() {
        try {
            console.log('Loading photos and videos...');
            
            // Load photos and videos in parallel
            const [photosResult, videosResult] = await Promise.all([
                this.loadPhotos(),
                this.loadVideos()
            ]);

            if (this.photos.length === 0 && this.videos.length === 0) {
                this.showNoContent();
            } else {
                this.startSlideshow();
            }

            this.hideLoading();
        } catch (error) {
            console.error('Error loading content:', error);
            this.showNoContent();
            this.hideLoading();
        }
    }

    async loadPhotos() {
        try {
            // Get processed photos from the processed/photos directory
            const response = await fetch('api/get-processed-photos.php');
            
            if (!response.ok) {
                console.warn('Could not load photos:', response.status);
                return;
            }
            
            const data = await response.json();
            console.log('Photos API response:', data);

            if (data.success && data.photos && data.photos.length > 0) {
                this.photos = data.photos;
                console.log(`Loaded ${this.photos.length} photos`);
                this.createPhotoSlides();
            } else {
                console.log('No photos available');
                this.showNoPhotos();
            }
        } catch (error) {
            console.error('Error loading photos:', error);
            this.showNoPhotos();
        }
    }

    async loadVideos() {
        try {
            const response = await fetch(`api/get-videos.php?limit=100&page=1`);
            
            if (!response.ok) {
                console.warn('Could not load videos:', response.status);
                return;
            }
            
            const data = await response.json();
            console.log('Videos API response:', data);

            if (data.success && data.videos.length > 0) {
                // Store only video metadata (paths, info) - NOT DOM elements
                // This saves memory by not loading all videos into DOM at once
                this.videos = data.videos;
                console.log(`Loaded ${this.videos.length} video references`);
                this.loadVideoSet(0);
            } else {
                console.log('No videos available');
                this.showNoVideos();
            }
        } catch (error) {
            console.error('Error loading videos:', error);
            this.showNoVideos();
        }
    }

    createPhotoSlides() {
        this.photoSlidesContainer.innerHTML = '';
        
        this.photos.forEach((photo, index) => {
            const slide = document.createElement('div');
            slide.className = `photo-slide ${index === 0 ? 'active' : ''}`;
            slide.innerHTML = `<img src="${photo.path}" alt="Photo ${index + 1}">`;
            this.photoSlidesContainer.appendChild(slide);
        });

        this.updatePhotoCounter();
    }

    loadVideoSet(setIndex) {
        const startIndex = setIndex * this.maxVideosPerSet;
        const endIndex = Math.min(startIndex + this.maxVideosPerSet, this.videos.length);
        const videoSet = this.videos.slice(startIndex, endIndex);

        if (videoSet.length === 0) {
            console.log('No videos in this set, looping back to first set');
            this.currentVideoSet = 0;
            this.loadVideoSet(0);
            return;
        }

        console.log(`Loading video set ${setIndex}: ${videoSet.length} videos (${startIndex}-${endIndex-1})`);
        this.currentVideoSet = setIndex;
        this.currentVideoIndex = 0;

        // Clear and rebuild only current set (saves memory)
        // Only 5 video elements in DOM at a time instead of all videos
        this.videoSlidesContainer.innerHTML = '';
        
        videoSet.forEach((video, index) => {
            const slide = document.createElement('div');
            slide.className = `video-slide ${index === 0 ? 'active' : ''}`;
            slide.innerHTML = `
                <video 
                    id="video-${index}"
                    muted
                    preload="metadata"
                >
                    <source src="${video.path}" type="video/mp4">
                </video>
            `;
            this.videoSlidesContainer.appendChild(slide);
        });

        this.updateVideoCounter();
    }

    startSlideshow() {
        console.log('Starting slideshow...');
        
        // Don't start automatic photo slideshow - photos will change when videos end
        
        // Start video playback
        if (this.videos.length > 0) {
            this.playCurrentVideo();
        }
    }

    nextPhoto() {
        if (this.photos.length === 0) return;

        const slides = this.photoSlidesContainer.querySelectorAll('.photo-slide');
        
        // Hide current
        slides[this.currentPhotoIndex].classList.remove('active');
        
        // Move to next
        this.currentPhotoIndex = (this.currentPhotoIndex + 1) % this.photos.length;
        
        // Show next
        slides[this.currentPhotoIndex].classList.add('active');
        
        this.updatePhotoCounter();
    }

    playCurrentVideo() {
        const videoSlides = this.videoSlidesContainer.querySelectorAll('.video-slide');
        if (videoSlides.length === 0) return;

        const currentSlide = videoSlides[this.currentVideoIndex];
        const video = currentSlide.querySelector('video');

        if (!video) return;

        // Set up event listener for when video ends
        video.onended = () => {
            this.onVideoEnded();
        };

        // Play the video
        video.play().catch(error => {
            console.error('Error playing video:', error);
            this.onVideoEnded();
        });

        console.log(`Playing video ${this.currentVideoIndex + 1} of set ${this.currentVideoSet + 1}`);
        this.updateVideoCounter();
    }

    onVideoEnded() {
        const videoSlides = this.videoSlidesContainer.querySelectorAll('.video-slide');
        
        // Change photo when video ends
        this.nextPhoto();
        
        // Check for new videos/photos from folder
        this.checkForNewContent();
        
        // Hide current video
        videoSlides[this.currentVideoIndex].classList.remove('active');
        
        // Move to next video in current set
        this.currentVideoIndex++;

        // Check if we've reached the end of the current set
        if (this.currentVideoIndex >= videoSlides.length) {
            console.log('End of video set reached, loading next set...');
            
            // Calculate next set
            const nextSet = this.currentVideoSet + 1;
            const maxSets = Math.ceil(this.videos.length / this.maxVideosPerSet);
            
            if (nextSet >= maxSets) {
                // Loop back to first set
                console.log('All sets played, looping back to first set');
                this.loadVideoSet(0);
            } else {
                // Load next set
                this.loadVideoSet(nextSet);
            }
            
            // Play first video of new set
            this.playCurrentVideo();
        } else {
            // Play next video in current set
            videoSlides[this.currentVideoIndex].classList.add('active');
            this.playCurrentVideo();
        }
    }

    async checkForNewContent() {
        try {
            // Check if there are new videos in the folder
            const response = await fetch(`api/get-videos.php?limit=1&page=1`);
            if (response.ok) {
                const data = await response.json();
                if (data.success && data.videos.length > 0) {
                    const latestVideo = data.videos[0];
                    
                    // Check if this is a new video we don't have yet
                    const exists = this.videos.some(v => v.path === latestVideo.path);
                    if (!exists) {
                        console.log('New video detected from folder:', latestVideo);
                        this.videos.unshift(latestVideo);
                        
                        // Check for new photo
                        const photoResponse = await fetch(`api/get-processed-photos.php?limit=1&page=1`);
                        if (photoResponse.ok) {
                            const photoData = await photoResponse.json();
                            if (photoData.success && photoData.photos.length > 0) {
                                const latestPhoto = photoData.photos[0];
                                const photoExists = this.photos.some(p => p.path === latestPhoto.path);
                                if (!photoExists) {
                                    console.log('New photo detected from folder:', latestPhoto);
                                    this.photos.unshift(latestPhoto);
                                    this.createPhotoSlides();
                                }
                            }
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error checking for new content:', error);
        }
    }

    updatePhotoCounter() {
        if (this.photos.length > 0) {
            this.photoCounter.textContent = `${this.currentPhotoIndex + 1}/${this.photos.length}`;
        } else {
            this.photoCounter.textContent = '0/0';
        }
    }

    updateVideoCounter() {
        const videoSlides = this.videoSlidesContainer.querySelectorAll('.video-slide');
        const totalVideos = this.videos.length;
        const currentVideoGlobal = (this.currentVideoSet * this.maxVideosPerSet) + this.currentVideoIndex + 1;
        
        if (totalVideos > 0) {
            this.videoCounter.textContent = `${currentVideoGlobal}/${totalVideos} (Set ${this.currentVideoSet + 1})`;
        } else {
            this.videoCounter.textContent = '0/0';
        }
    }

    showNoPhotos() {
        this.photoSlidesContainer.innerHTML = `
            <div class="no-content">
                <i class="fas fa-images"></i>
                <h3>No Photos</h3>
                <p>No processed photos available</p>
            </div>
        `;
    }

    showNoVideos() {
        this.videoSlidesContainer.innerHTML = `
            <div class="no-content">
                <i class="fas fa-video"></i>
                <h3>No Videos</h3>
                <p>No processed videos available</p>
            </div>
        `;
    }

    showNoContent() {
        this.photoSlidesContainer.innerHTML = `
            <div class="no-content">
                <i class="fas fa-images"></i>
                <h3>No Photos</h3>
                <p>No processed photos available</p>
            </div>
        `;
        
        this.videoSlidesContainer.innerHTML = `
            <div class="no-content">
                <i class="fas fa-video"></i>
                <h3>No Videos</h3>
                <p>No processed videos available</p>
            </div>
        `;
    }

    hideLoading() {
        this.loadingScreen.style.display = 'none';
        this.isLoading = false;
    }
}

// Initialize slideshow when page loads
let portraitSlideshow;
document.addEventListener('DOMContentLoaded', () => {
    portraitSlideshow = new PortraitSlideshow();
});
