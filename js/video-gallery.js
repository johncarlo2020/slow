class VideoGallery {
    constructor() {
        this.currentPage = 1;
        this.videosPerPage = 9;
        this.totalVideos = 0;
        this.videos = [];
        this.totalPages = 0;
        
        this.initializePusher();
        this.initializeEventListeners();
        this.loadVideos();
    }

    initializePusher() {
        // Initialize Pusher with your actual credentials
        this.pusher = new Pusher('60de59064bcf7cfb6d63', {
            cluster: 'ap1'
        });

        // Subscribe to the video processing channel
        this.channel = this.pusher.subscribe('video-processing');
        
        // Listen for new video processed events
        this.channel.bind('video-processed', (data) => {
            console.log('New video processed:', data);
            this.handleNewVideoProcessed(data);
        });
        
        console.log('Pusher initialized and listening for new videos');
    }

    handleNewVideoProcessed(data) {
        // Show notification
        this.showNotification('New video processed!', 'A new slow motion video is ready to view.');
        
        // Reload videos to show the new one
        this.loadVideos();
    }

    showNotification(title, message) {
        // Create a toast notification
        const toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        
        const toast = document.createElement('div');
        toast.className = 'toast show';
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="toast-header">
                <i class="fas fa-video text-primary me-2"></i>
                <strong class="me-auto">${title}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;
        
        toastContainer.appendChild(toast);
        document.body.appendChild(toastContainer);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toastContainer.parentNode) {
                toastContainer.parentNode.removeChild(toastContainer);
            }
        }, 5000);
    }

    initializeEventListeners() {
        // Get current page from URL if available
        const urlParams = new URLSearchParams(window.location.search);
        const page = urlParams.get('page');
        if (page && !isNaN(page)) {
            this.currentPage = parseInt(page);
        }

        // Modal delete button
        document.getElementById('modal-delete-btn').addEventListener('click', () => {
            this.deleteCurrentVideo();
        });
    }

    async loadVideos() {
        try {
            this.showLoading();
            
            console.log('Fetching videos from API...');
            const response = await fetch(`api/get-videos.php?page=${this.currentPage}&limit=${this.videosPerPage}`);
            
            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);
            
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('API response data:', data);

            if (data.success) {
                console.log('Videos loaded successfully:', data.videos.length, 'videos');
                this.videos = data.videos;
                this.totalVideos = data.total;
                this.totalPages = data.totalPages;
                
                try {
                    this.displayVideos();
                    console.log('displayVideos completed');
                } catch (e) {
                    console.error('Error in displayVideos:', e);
                    throw e;
                }
                
                try {
                    this.displayStats(data.stats);
                    console.log('displayStats completed');
                } catch (e) {
                    console.error('Error in displayStats:', e);
                    throw e;
                }
                
                try {
                    this.displayPagination();
                    console.log('displayPagination completed');
                } catch (e) {
                    console.error('Error in displayPagination:', e);
                    throw e;
                }
                
                if (this.videos.length === 0 && this.currentPage === 1) {
                    this.showEmptyState();
                } else {
                    this.hideLoading();
                }
            } else {
                console.error('API returned success=false:', data.message);
                throw new Error(data.message || 'Failed to load videos');
            }
        } catch (error) {
            console.error('Error loading videos:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                name: error.name
            });
            this.hideLoading();
            
            // Only show alert for actual failures, not if videos loaded successfully
            if (!this.videos || this.videos.length === 0) {
                this.showError('Failed to load videos. Please try again.');
            }
        }
    }

    displayVideos() {
        const videoGrid = document.getElementById('video-grid');
        videoGrid.innerHTML = '';

        if (this.videos.length === 0) {
            this.showEmptyState();
            return;
        }

        this.videos.forEach(video => {
            const videoCard = this.createVideoCard(video);
            videoGrid.appendChild(videoCard);
        });

        videoGrid.style.display = 'grid';
        videoGrid.className = 'videos-grid';
        this.hideLoading();
    }

    createVideoCard(video) {
        const card = document.createElement('div');
        card.className = 'video-card';
        card.style.cursor = 'pointer';

        const formattedDate = new Date(video.created * 1000).toLocaleDateString();

        card.innerHTML = `
            <div class="video-thumbnail">
                <video preload="metadata" muted>
                    <source src="${video.path}" type="video/mp4">
                </video>
                <div class="video-overlay">
                    <div class="play-button">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
            </div>
            <div class="video-info">
                <div class="video-title">${video.displayName}</div>
                <div class="video-meta">
                    <span><i class="fas fa-calendar"></i> ${formattedDate}</span>
                </div>
            </div>
        `;

        // Make entire card clickable
        card.addEventListener('click', () => {
            window.location.href = `video-view.html?video=${encodeURIComponent(video.filename)}`;
        });

        return card;
    }

    displayStats(stats) {
        if (!stats) return;
        
        // Update document title with video count
        document.title = `Video Gallery (${stats.totalVideos}) - Samsung Slow Motion Creator`;
        
        // Stats are not displayed in the new Samsung design
        // The stats bar elements don't exist in our new layout
    }

    displayPagination() {
        if (this.totalPages <= 1) {
            document.getElementById('pagination-wrapper').style.display = 'none';
            return;
        }

        const pagination = document.getElementById('pagination');
        pagination.innerHTML = '';

        // Previous button
        const prevBtn = this.createPaginationItem(
            '<i class="fas fa-chevron-left"></i>',
            this.currentPage - 1,
            this.currentPage === 1
        );
        pagination.appendChild(prevBtn);

        // Page numbers
        const startPage = Math.max(1, this.currentPage - 2);
        const endPage = Math.min(this.totalPages, this.currentPage + 2);

        // First page if not in range
        if (startPage > 1) {
            pagination.appendChild(this.createPaginationItem('1', 1));
            if (startPage > 2) {
                pagination.appendChild(this.createPaginationItem('...', null, true));
            }
        }

        // Page range
        for (let i = startPage; i <= endPage; i++) {
            pagination.appendChild(this.createPaginationItem(i.toString(), i, false, i === this.currentPage));
        }

        // Last page if not in range
        if (endPage < this.totalPages) {
            if (endPage < this.totalPages - 1) {
                pagination.appendChild(this.createPaginationItem('...', null, true));
            }
            pagination.appendChild(this.createPaginationItem(this.totalPages.toString(), this.totalPages));
        }

        // Next button
        const nextBtn = this.createPaginationItem(
            '<i class="fas fa-chevron-right"></i>',
            this.currentPage + 1,
            this.currentPage === this.totalPages
        );
        pagination.appendChild(nextBtn);

        document.getElementById('pagination-wrapper').style.display = 'block';
    }

    createPaginationItem(text, page, disabled = false, active = false) {
        const li = document.createElement('li');
        li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;

        const a = document.createElement('a');
        a.className = 'page-link';
        a.innerHTML = text;
        a.href = '#';

        if (!disabled && page !== null) {
            a.addEventListener('click', (e) => {
                e.preventDefault();
                this.goToPage(page);
            });
        }

        li.appendChild(a);
        return li;
    }

    goToPage(page) {
        if (page < 1 || page > this.totalPages || page === this.currentPage) return;
        
        this.currentPage = page;
        
        // Update URL
        const url = new URL(window.location);
        url.searchParams.set('page', page);
        window.history.pushState({}, '', url);
        
        this.loadVideos();
    }

    openVideoModal(filename) {
        const video = this.videos.find(v => v.filename === filename);
        if (!video) return;

        const modal = new bootstrap.Modal(document.getElementById('videoModal'));
        const modalVideo = document.getElementById('modal-video');
        
        document.getElementById('modal-video-title').textContent = video.displayName;
        modalVideo.src = video.path;
        document.getElementById('modal-file-size').textContent = this.formatFileSize(video.size);
        document.getElementById('modal-created-date').textContent = new Date(video.created * 1000).toLocaleString();
        document.getElementById('modal-download-btn').href = video.path;
        document.getElementById('modal-download-btn').download = video.filename;
        
        // Store current video for deletion
        this.currentModalVideo = filename;
        
        modal.show();
    }

    confirmDelete(filename) {
        const video = this.videos.find(v => v.filename === filename);
        if (!video) return;

        if (confirm(`Are you sure you want to delete "${video.displayName}"? This action cannot be undone.`)) {
            this.deleteVideo(filename);
        }
    }

    deleteCurrentVideo() {
        if (this.currentModalVideo) {
            this.confirmDelete(this.currentModalVideo);
            bootstrap.Modal.getInstance(document.getElementById('videoModal')).hide();
        }
    }

    async deleteVideo(filename) {
        try {
            const response = await fetch('api/delete-video.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ filename: filename })
            });

            const data = await response.json();

            if (data.success) {
                // Remove video from current list
                this.videos = this.videos.filter(v => v.filename !== filename);
                this.totalVideos--;
                
                // Reload if current page becomes empty
                if (this.videos.length === 0 && this.currentPage > 1) {
                    this.currentPage--;
                    this.loadVideos();
                } else {
                    this.displayVideos();
                    this.displayPagination();
                }
                
                this.showSuccessMessage('Video deleted successfully');
            } else {
                throw new Error(data.message || 'Failed to delete video');
            }
        } catch (error) {
            console.error('Error deleting video:', error);
            this.showError('Failed to delete video. Please try again.');
        }
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    showLoading() {
        document.getElementById('loading-state').style.display = 'block';
        document.getElementById('video-grid').style.display = 'none';
        document.getElementById('empty-state').style.display = 'none';
        document.getElementById('pagination-wrapper').style.display = 'none';
    }

    hideLoading() {
        document.getElementById('loading-state').style.display = 'none';
    }

    showEmptyState() {
        document.getElementById('loading-state').style.display = 'none';
        document.getElementById('video-grid').style.display = 'none';
        document.getElementById('empty-state').style.display = 'block';
        document.getElementById('pagination-wrapper').style.display = 'none';
    }

    showError(message) {
        this.hideLoading();
        alert(message);
    }

    showSuccessMessage(message) {
        // You could implement a proper success message display here
        console.log(message);
    }
}

// Initialize when page loads
let videoGallery;
document.addEventListener('DOMContentLoaded', () => {
    videoGallery = new VideoGallery();
});
