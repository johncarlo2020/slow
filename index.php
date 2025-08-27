<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Slow Motion Video Creator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .upload-area {
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .upload-area:hover {
            border-color: #007bff;
            background: #e3f2fd;
        }
        .upload-area.dragover {
            border-color: #007bff;
            background: #e3f2fd;
        }
        .progress-container {
            display: none;
            margin-top: 20px;
        }
        .video-preview {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .time-input {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
        }
        .processing-status {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .result-section {
            display: none;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><i class="fas fa-video"></i> Professional Slow Motion Creator</h3>
                            <small>Automatic 4x slow motion with cinema-quality overlay (4-7 seconds)</small>
                        </div>
                        <a href="upload-assets.html" class="btn btn-light btn-sm">
                            <i class="fas fa-cog"></i> Manage Templates & Audio
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- Upload Section -->
                        <div id="upload-section">
                            <div class="upload-area" id="upload-area">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h5>Upload your video for professional slow motion</h5>
                                <p class="text-muted">Automatic processing: 4x slower motion from 4-7 seconds<br>
                                <small>Includes professional overlay template • Ultra-smooth frame interpolation</small></p>
                                <input type="file" id="video-input" accept="video/*" style="display: none;">
                                <button class="btn btn-outline-primary" onclick="document.getElementById('video-input').click()">
                                    Choose Video File
                                </button>
                            </div>
                            
                            <div class="progress-container" id="progress-container">
                                <div class="progress">
                                    <div class="progress-bar" id="upload-progress" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted">Uploading...</small>
                            </div>
                        </div>

                        <!-- Video Preview and Controls -->
                        <div id="video-controls" style="display: none;">
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <h5>Video Preview</h5>
                                    <video id="video-preview" class="video-preview" controls></video>
                                </div>
                                <div class="col-md-6">
                                    <h5>Slow Motion Processing</h5>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Auto Processing:</strong><br>
                                        • Slow motion applied to seconds 4-7<br>
                                        • Ultra smooth quality with frame interpolation<br>
                                        • Professional overlay template included<br>
                                        • Cinema-quality results
                                    </div>
                                    
                                    <div class="time-input">
                                        <label class="form-label">Processing Settings</label>
                                        <div class="form-control" style="background: #f8f9fa;">
                                            <strong>Time Range:</strong> 4.0 - 7.0 seconds<br>
                                            <strong>Slow Factor:</strong> 4x Slower (0.25)<br>
                                            <strong>Quality:</strong> Ultra Smooth + Overlay<br>
                                            <strong>Template:</strong> Professional branding
                                        </div>
                                    </div>

                                    <button class="btn btn-success btn-lg w-100 mt-3" id="process-btn">
                                        <i class="fas fa-magic"></i> Create Slow Motion Video
                                        <small class="d-block">with Professional Overlay</small>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Processing Status -->
                        <div id="processing-status" class="processing-status">
                            <div class="spinner"></div>
                            <h5>Creating Professional Slow Motion Video...</h5>
                            <p class="text-muted">
                                • Applying 4x slow motion to seconds 4-7<br>
                                • Adding professional overlay template<br>
                                • Ultra-smooth frame interpolation processing<br>
                                <small>This may take a few minutes for best quality results</small>
                            </p>
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" id="process-progress" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- Result Section -->
                        <div id="result-section" class="result-section">
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check-circle"></i> Processing Complete!</h5>
                                <p class="mb-0">Your slow motion video has been created successfully.</p>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Original Video</h6>
                                    <video id="original-video" class="video-preview" controls></video>
                                </div>
                                <div class="col-md-6">
                                    <h6>Processed Video</h6>
                                    <video id="processed-video" class="video-preview" controls></video>
                                    <div class="mt-3">
                                        <a href="#" class="btn btn-primary" id="download-btn" download>
                                            <i class="fas fa-download"></i> Download Processed Video
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-outline-secondary mt-3" onclick="location.reload()">
                                Process Another Video
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add global error handling before loading the main script
        window.addEventListener('error', (event) => {
            console.error('Page error:', event.error);
        });
        
        window.addEventListener('unhandledrejection', (event) => {
            console.error('Unhandled promise rejection on page:', event.reason);
        });
    </script>
    <script src="js/video-processor.js"></script>
</body>
</html>
