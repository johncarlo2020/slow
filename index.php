<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Professional Slow Motion Video Creator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('background/bg.webp') center center;
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .main-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
        }
        
        .upload-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
            position: relative;
        }
        
        .upload-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .upload-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('background/bg.webp') center center;
            background-size: cover;
            opacity: 0.1;
            z-index: 0;
        }
        
        .upload-header > * {
            position: relative;
            z-index: 1;
        }
        
        .brand-logo {
            font-size: 2.5rem;
            font-weight: 300;
            letter-spacing: 3px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .upload-body {
            padding: 40px;
        }
        
        .upload-area {
            border: 3px dashed #e0e0e0;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(145deg, #f8f9fa, #ffffff);
            position: relative;
            min-height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .upload-area:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.1);
        }
        
        .upload-area.dragover {
            border-color: #667eea;
            background: linear-gradient(145deg, #f0f4ff, #e8f2ff);
            transform: scale(1.02);
        }
        
        .upload-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .upload-area:hover .upload-icon {
            transform: scale(1.1);
        }
        
        .upload-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .upload-subtitle {
            color: #7f8c8d;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .upload-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50px;
            padding: 15px 40px;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .navigation-buttons {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 10;
        }
        
        .nav-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            padding: 10px 15px;
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            cursor: pointer;
            user-select: none;
        }
        
        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }
        
        .progress-container {
            display: none;
            margin-top: 30px;
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
        }
        
        .processing-status {
            display: none;
            text-align: center;
            padding: 40px;
            background: linear-gradient(145deg, #f8f9fa, #ffffff);
            border-radius: 20px;
            margin-top: 30px;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(102, 126, 234, 0.1);
            border-top: 4px solid #667eea;
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
            background: linear-gradient(145deg, #f8f9fa, #ffffff);
            border-radius: 20px;
            padding: 30px;
        }
        
        .video-preview {
            max-width: 100%;
            height: auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        /* iPad specific optimizations */
        @media (min-width: 768px) and (max-width: 1024px) {
            .main-container {
                padding: 40px;
            }
            
            .upload-card {
                max-width: 90vw;
            }
            
            .upload-area {
                min-height: 350px;
                padding: 80px 60px;
            }
            
            .upload-icon {
                font-size: 5rem;
            }
            
            .upload-title {
                font-size: 1.8rem;
            }
            
            .brand-logo {
                font-size: 3rem;
            }
        }
        
        /* Mobile optimizations */
        @media (max-width: 767px) {
            .main-container {
                padding: 15px;
            }
            
            .upload-body {
                padding: 25px;
            }
            
            .upload-area {
                padding: 40px 25px;
                min-height: 250px;
            }
            
            .upload-icon {
                font-size: 3rem;
            }
            
            .upload-title {
                font-size: 1.3rem;
            }
            
            .brand-logo {
                font-size: 2rem;
            }
            
            .navigation-buttons {
                position: relative;
                top: auto;
                right: auto;
                justify-content: center;
                margin-top: 20px;
                z-index: 10;
            }
            
            .nav-btn {
                display: inline-block;
                margin: 5px;
            }
        }
        
        /* Animation for smooth transitions */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .file-actions {
            display: flex;
            gap: 10px;
        }
        
        .status-message {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="upload-card fade-in">
            <div class="upload-header">
                <div class="navigation-buttons">
                    <a href="video-gallery.html" class="nav-btn">
                        <i class="fas fa-photo-video me-1"></i> Gallery
                    </a>
                    <a href="upload-assets.html" class="nav-btn">
                        <i class="fas fa-cog me-1"></i> Assets
                    </a>
                </div>
                <div class="brand-logo">SAMSUNG</div>
                <div style="font-size: 1.1rem; opacity: 0.9;">Professional Slow Motion Creator</div>
                <div style="font-size: 0.9rem; opacity: 0.7; margin-top: 10px;">
                    Automatic 4x slow motion • Cinema quality overlay • 4-7 seconds
                </div>
            </div>
            
            <div class="upload-body">
                <!-- Upload Section -->
                <div id="upload-section">
                    <div class="upload-area" id="upload-area">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-title">Upload your video here</div>
                        <div class="upload-subtitle">
                            Drag and drop your video file or click to browse<br>
                            <small>Supports MP4, AVI, MOV, WMV, WebM formats</small>
                        </div>
                        <input type="file" id="video-input" accept="video/*" style="display: none;">
                        <button class="btn upload-btn" onclick="document.getElementById('video-input').click()">
                            <i class="fas fa-plus me-2"></i>Choose Video File
                        </button>
                    </div>
                </div>
                            
                <!-- Progress Section -->
                <div class="progress-container" id="progress-container">
                    <div class="d-flex align-items-center mb-3">
                        <div class="spinner-border text-primary me-3" role="status" style="width: 2rem; height: 2rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div>
                            <h6 class="mb-1">Uploading your video...</h6>
                            <small class="text-muted">Please wait while we process your file</small>
                        </div>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 10px;">
                        <div class="progress-bar" id="upload-progress" role="progressbar" style="width: 0%; border-radius: 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                    </div>
                </div>
            </div>

            <!-- Video Preview and Controls -->
            <div id="video-controls" style="display: none;">
                <div class="row mt-4">
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100" style="border: none; border-radius: 20px; background: linear-gradient(145deg, #f8f9fa, #ffffff);">
                            <div class="card-body">
                                <h5 class="card-title" style="color: #2c3e50;">
                                    <i class="fas fa-play-circle me-2" style="color: #667eea;"></i>Video Preview
                                </h5>
                                <video id="video-preview" class="video-preview w-100" controls style="border-radius: 15px;"></video>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100" style="border: none; border-radius: 20px; background: linear-gradient(145deg, #f8f9fa, #ffffff);">
                            <div class="card-body">
                                <h5 class="card-title" style="color: #2c3e50;">
                                    <i class="fas fa-magic me-2" style="color: #667eea;"></i>Processing Settings
                                </h5>
                                <div class="alert" style="background: linear-gradient(135deg, #667eea15, #764ba215); border: 1px solid rgba(102, 126, 234, 0.2); border-radius: 15px;">
                                    <i class="fas fa-info-circle" style="color: #667eea;"></i>
                                    <strong>Auto Processing Mode</strong><br>
                                    <div class="mt-2" style="font-size: 0.9rem;">
                                        • Slow motion: seconds 4-7<br>
                                        • Ultra smooth quality with frame interpolation<br>
                                        • Professional overlay template included<br>
                                        • Cinema-quality results
                                    </div>
                                </div>
                                
                                <div class="mt-3 p-3" style="background: rgba(102, 126, 234, 0.05); border-radius: 15px;">
                                    <h6 style="color: #2c3e50; margin-bottom: 15px;">Processing Details</h6>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div style="color: #667eea; font-weight: 600;">4.0-7.0s</div>
                                            <small class="text-muted">Time Range</small>
                                        </div>
                                        <div class="col-4">
                                            <div style="color: #667eea; font-weight: 600;">4x Slower</div>
                                            <small class="text-muted">Speed Factor</small>
                                        </div>
                                        <div class="col-4">
                                            <div style="color: #667eea; font-weight: 600;">Ultra HD</div>
                                            <small class="text-muted">Quality</small>
                                        </div>
                                    </div>
                                </div>

                                <button class="btn w-100 mt-4" id="process-btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 50px; padding: 15px; color: white; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                                    <i class="fas fa-magic me-2"></i>Create Slow Motion Video
                                    <div style="font-size: 0.8rem; font-weight: 400; text-transform: none; letter-spacing: normal;">with Professional Overlay</div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Processing Status -->
            <div id="processing-status" class="processing-status">
                <div class="spinner"></div>
                <h5 style="color: #2c3e50; margin-bottom: 10px;">Creating Professional Slow Motion Video...</h5>
                <p style="color: #7f8c8d;">This may take a few minutes. Please wait...</p>
                <div class="mt-3" style="font-size: 0.9rem; color: #95a5a6;">
                    • Applying 4x slow motion to seconds 4-7<br>
                    • Adding professional overlay template<br>
                    • Ultra-smooth frame interpolation processing
                </div>
                <div class="progress mt-3" style="height: 8px; border-radius: 10px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" id="process-progress" style="width: 0%; border-radius: 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                </div>
            </div>

            <!-- Result Section -->
            <div id="result-section" class="result-section">
                <div class="alert" style="background: linear-gradient(135deg, #00c85115, #00c85115); border: 1px solid #00c851; border-radius: 20px; color: #00695c;">
                    <h5><i class="fas fa-check-circle me-2"></i>Processing Complete!</h5>
                    <p class="mb-0">Your professional slow motion video has been created successfully.</p>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100" style="border: none; border-radius: 20px; background: linear-gradient(145deg, #f8f9fa, #ffffff);">
                            <div class="card-body">
                                <h6 style="color: #2c3e50;"><i class="fas fa-file-video me-2" style="color: #667eea;"></i>Original Video</h6>
                                <video id="original-video" class="video-preview w-100" controls style="border-radius: 15px;"></video>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100" style="border: none; border-radius: 20px; background: linear-gradient(145deg, #f8f9fa, #ffffff);">
                            <div class="card-body">
                                <h6 style="color: #2c3e50;"><i class="fas fa-magic me-2" style="color: #667eea;"></i>Processed Video</h6>
                                <video id="processed-video" class="video-preview w-100" controls style="border-radius: 15px;"></video>
                                <div class="mt-3 d-grid gap-2">
                                    <a href="#" class="btn" id="download-btn" download style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 50px; padding: 12px; color: white; font-weight: 600;">
                                        <i class="fas fa-download me-2"></i>Download Video
                                    </a>
                                    <a href="video-view.html" class="btn btn-outline-secondary" id="view-btn" style="border-radius: 50px; border-color: #667eea; color: #667eea;">
                                        <i class="fas fa-eye me-2"></i>View & Share
                                    </a>
                                    <button class="btn btn-outline-info" onclick="location.reload()" style="border-radius: 50px; border-color: #17a2b8; color: #17a2b8;">
                                        <i class="fas fa-plus me-2"></i>Process Another Video
                                    </button>
                                </div>
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
