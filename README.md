# Video Upload and Slow Motion Processing System

A web-based application that allows users to upload videos and apply slow motion effects to specific time ranges.

## Features

- **Video Upload**: Drag & drop or click to upload video files
- **Video Preview**: Preview uploaded videos with HTML5 video player
- **Slow Motion Processing**: Apply slow motion to specific time ranges
- **Multiple Slow Factors**: Choose from 2x to 10x slower
- **Progress Tracking**: Real-time upload and processing progress
- **Download Results**: Download processed videos
- **Responsive Design**: Works on desktop and mobile devices

## Supported Video Formats

- MP4
- AVI
- MOV
- WMV
- WebM

## Requirements

### Server Requirements
- PHP 7.0 or higher
- Web server (Apache/Nginx)
- FFmpeg installed on the server

### FFmpeg Installation

#### Windows
1. Download FFmpeg from https://ffmpeg.org/download.html
2. Extract to `C:\ffmpeg\`
3. Add `C:\ffmpeg\bin` to your system PATH

#### Linux (Ubuntu/Debian)
```bash
sudo apt update
sudo apt install ffmpeg
```

#### macOS
```bash
brew install ffmpeg
```

## Installation

1. **Clone or download** this project to your web server directory
2. **Install FFmpeg** on your server (see requirements above)
3. **Set permissions** for upload and processed directories:
   ```bash
   chmod 755 uploads/
   chmod 755 processed/
   ```
4. **Configure PHP** (optional - increase upload limits):
   ```ini
   upload_max_filesize = 100M
   post_max_size = 100M
   max_execution_time = 300
   ```

## Usage

1. **Access the application** through your web browser
2. **Upload a video** by dragging and dropping or clicking to select
3. **Set the time range** for slow motion effect:
   - Start time (in seconds)
   - End time (in seconds)
4. **Choose slow motion factor**:
   - 2x slower (0.5)
   - 4x slower (0.25)
   - 8x slower (0.125)
   - 10x slower (0.1)
5. **Click "Process Video"** to start processing
6. **Download the result** when processing is complete

## How It Works

### Video Processing Pipeline
1. **Upload**: Video is uploaded to the `uploads/` directory
2. **Validation**: File type and size are validated
3. **Processing**: FFmpeg creates slow motion effect:
   - Extracts the segment before slow motion (normal speed)
   - Applies slow motion to the specified time range
   - Extracts the segment after slow motion (normal speed)
   - Concatenates all segments into final video
4. **Output**: Processed video is saved to `processed/` directory

### Slow Motion Technical Details
The slow motion effect is achieved using FFmpeg's `setpts` filter:
- `setpts=2.0*PTS` = 2x slower
- `setpts=4.0*PTS` = 4x slower
- etc.

## File Structure

```
slow/
├── index.php              # Main application page
├── js/
│   └── video-processor.js  # Frontend JavaScript
├── api/
│   ├── upload.php         # Video upload handler
│   └── process.php        # Video processing handler
├── uploads/               # Uploaded videos directory
├── processed/             # Processed videos directory
├── .gitignore            # Git ignore file
└── README.md             # This file
```

## API Endpoints

### POST /api/upload.php
Upload a video file
- **Input**: FormData with 'video' file
- **Output**: JSON with success status and video URL

### POST /api/process.php
Process video with slow motion
- **Input**: FormData with:
  - `videoUrl`: Path to uploaded video
  - `startTime`: Start time in seconds
  - `endTime`: End time in seconds
  - `slowFactor`: Slow motion factor (0.1 to 1.0)
- **Output**: JSON with processed video URL

## Configuration

### Upload Limits
Modify in `api/upload.php`:
```php
private $maxFileSize = 100 * 1024 * 1024; // 100MB
```

### Allowed Video Types
Modify in `api/upload.php`:
```php
private $allowedTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/webm'];
```

### FFmpeg Path
The system tries to auto-detect FFmpeg. If needed, modify in `api/process.php`:
```php
$this->ffmpegPath = '/path/to/ffmpeg';
```

## Troubleshooting

### Common Issues

1. **"FFmpeg not found"**
   - Install FFmpeg on your server
   - Ensure FFmpeg is in your system PATH
   - Update the FFmpeg path in `api/process.php`

2. **"Upload failed"**
   - Check file size limits in PHP configuration
   - Ensure upload directory has write permissions
   - Verify file type is supported

3. **"Processing failed"**
   - Check FFmpeg installation
   - Verify sufficient disk space
   - Check server error logs

### Error Logs
Check your web server error logs for detailed error messages.

## Security Considerations

- File type validation is performed
- File size limits are enforced
- Uploaded files are stored outside web root when possible
- Input parameters are validated and sanitized

## Performance Notes

- Processing time depends on video length and size
- Larger slow motion factors take longer to process
- Consider implementing background job processing for large files

## License

This project is open source and available under the MIT License.
