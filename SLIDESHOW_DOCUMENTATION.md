# 🎬 Video Slideshow Feature

## Overview
The Video Slideshow is a fullscreen presentation mode that automatically plays the latest 5 processed videos in a continuous loop. It provides a cinema-like experience with real-time updates when new videos are processed.

## ✨ Features

### 🔄 **Auto-Playing Slideshow**
- Displays the **first 5 videos** from your gallery
- **Fullscreen mode** for immersive viewing
- **Auto-advance** to next video when current video ends
- **Continuous loop** - returns to first video after the last one

### 📱 **Responsive Design**
- **Mobile-friendly** - works on all screen sizes
- **Portrait/Landscape** video support
- **Optimized video scaling** to fit screen

### ⚡ **Real-time Updates**
- **Live sync** with Pusher WebSocket
- **Automatic refresh** when new videos are processed
- **Smart queue management** - newest video replaces oldest
- **Instant notifications** for new videos

### 🎮 **Interactive Controls**
- **Play/Pause** button
- **Previous/Next** navigation
- **Progress bar** showing video timeline
- **Video counter** (1/5, 2/5, etc.)
- **Exit** button to return to gallery

### ⌨️ **Keyboard Shortcuts**
- **Spacebar** - Play/Pause
- **Arrow Left** - Previous video
- **Arrow Right** - Next video
- **Escape** - Exit slideshow

## 🚀 How to Use

### 1. **Access Slideshow**
Navigate to the slideshow from multiple locations:
- **Gallery page**: Click "Slideshow" button in header
- **Upload page**: Click "Slideshow" button in header
- **Direct URL**: `/slideshow.html`

### 2. **Slideshow Behavior**
- Automatically starts playing the first video
- Videos play in fullscreen with overlay info
- Advances to next video when current ends
- Shows notification when new videos are added

### 3. **Real-time Updates**
- When someone processes a new video from upload page
- Slideshow automatically adds the newest video
- Removes the oldest video to maintain 5-video limit
- Shows green notification toast

## 🛠️ Technical Implementation

### **Frontend (slideshow.js)**
```javascript
class VideoSlideshow {
    - Manages 5-video queue
    - Handles Pusher real-time events
    - Controls video playback and transitions
    - Provides keyboard and button controls
}
```

### **Backend Integration**
- Uses existing `api/get-videos.php` with `limit=5`
- Connects to Pusher channel `video-processing`
- Listens for `video-processed` events
- Auto-refreshes video list on updates

### **Pusher Events**
```javascript
channel.bind('video-processed', (data) => {
    // Reload slideshow with latest 5 videos
    // Show notification toast
    // Maintain current playback state
});
```

## 📋 File Structure

```
/slideshow.html          # Main slideshow page
/js/slideshow.js         # Slideshow functionality
/api/get-videos.php      # Video data API (existing)
/config/pusher.php       # Pusher configuration
/classes/PusherHelper.php # Pusher event helper
```

## 🎯 Use Cases

### **Exhibition Mode**
Perfect for displaying your best slow-motion videos in:
- **Trade shows** and exhibitions
- **Digital signage** displays
- **Reception areas** and lobbies
- **Portfolio presentations**

### **Live Demo**
Great for demonstrating the video processing:
- **Real-time updates** show new videos instantly
- **Professional presentation** mode
- **Automatic operation** requires no interaction

### **Content Review**
Useful for reviewing processed videos:
- **Quick preview** of latest videos
- **Easy navigation** between videos
- **Fullscreen viewing** for detail inspection

## 🔧 Customization Options

### **Video Limit**
```javascript
// Change max videos in slideshow.js
this.maxVideos = 5; // Modify this number
```

### **Auto-advance Timing**
Videos advance when they naturally end. To add delays:
```javascript
// Add delay before next video
setTimeout(() => this.nextVideo(), 2000); // 2 second delay
```

### **Notification Styling**
Customize notifications in CSS:
```css
.notification-toast {
    background: rgba(0, 128, 0, 0.9); /* Green notification */
    /* Modify colors, position, duration */
}
```

## 🐛 Troubleshooting

### **No Videos Showing**
- Check if videos exist in `/processed/` folder
- Verify API endpoint `/api/get-videos.php` works
- Check browser console for JavaScript errors

### **Real-time Updates Not Working**
- Verify Pusher credentials in `config/pusher.php`
- Check browser console for Pusher connection errors
- Ensure `composer install` was run for Pusher PHP library

### **Videos Not Playing**
- Check video format compatibility (MP4 recommended)
- Verify video file permissions
- Check browser autoplay policies

### **Performance Issues**
- Limit video file sizes (under 50MB recommended)
- Use optimized video formats (H.264/MP4)
- Close other browser tabs during slideshow

## 🔄 Integration with Upload Process

When a video is processed in `api/process.php`:

1. **Video is saved** to `/processed/` folder
2. **Pusher event** is triggered with video data
3. **Slideshow receives** real-time update
4. **Video queue** is refreshed with latest 5 videos
5. **User sees notification** and updated slideshow

This creates a seamless experience where the slideshow stays current with the latest processed videos automatically.

## 🎨 UI/UX Features

- **Samsung-style design** matching the main app
- **Smooth transitions** between videos
- **Professional overlay** with video information
- **Intuitive controls** with hover effects
- **Loading states** for better user experience
- **Error handling** with fallback screens
