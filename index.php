<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Samsung Video Processor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'SamsungOne';
            src: url('font/SamsungOne-400.ttf') format('truetype');
            font-weight: 400;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'SamsungOne';
            src: url('font/SamsungOne-700.ttf') format('truetype');
            font-weight: 700;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'SamsungSharpSans';
            src: url('font/SamsungSharpSans-Bold.ttf') format('truetype');
            font-weight: bold;
            font-display: swap;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'SamsungOne', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: url('background/bg.webp') center center;
            background-size: cover;
            background-attachment: fixed;
            background-repeat: no-repeat;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.85) 0%, rgba(22, 33, 62, 0.9) 50%, rgba(15, 52, 96, 0.8) 100%);
            z-index: 0;
            pointer-events: none;
        }
        
        /* Animated background particles */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
            pointer-events: none;
        }
        
        .particle {
            position: absolute;
            background: radial-gradient(circle, rgba(138, 43, 226, 0.8) 0%, rgba(30, 144, 255, 0.4) 100%);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .particle:nth-child(1) { width: 4px; height: 4px; left: 10%; top: 20%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 6px; height: 6px; left: 20%; top: 40%; animation-delay: 1s; }
        .particle:nth-child(3) { width: 3px; height: 3px; left: 30%; top: 60%; animation-delay: 2s; }
        .particle:nth-child(4) { width: 5px; height: 5px; left: 40%; top: 30%; animation-delay: 1.5s; }
        .particle:nth-child(5) { width: 4px; height: 4px; left: 50%; top: 70%; animation-delay: 0.5s; }
        .particle:nth-child(6) { width: 6px; height: 6px; left: 60%; top: 20%; animation-delay: 2.5s; }
        .particle:nth-child(7) { width: 3px; height: 3px; left: 70%; top: 50%; animation-delay: 1s; }
        .particle:nth-child(8) { width: 5px; height: 5px; left: 80%; top: 80%; animation-delay: 3s; }
        .particle:nth-child(9) { width: 4px; height: 4px; left: 90%; top: 40%; animation-delay: 0.8s; }
        .particle:nth-child(10) { width: 6px; height: 6px; left: 15%; top: 80%; animation-delay: 2.2s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.7; }
            50% { transform: translateY(-20px) rotate(180deg); opacity: 1; }
        }
        
        /* Lightning effect */
        .lightning {
            position: absolute;
            width: 2px;
            height: 200px;
            background: linear-gradient(0deg, transparent 0%, #8a2be2 50%, #1e90ff 100%);
            left: 50%;
            top: 20%;
            transform: translateX(-50%) rotate(25deg);
            opacity: 0.6;
            z-index: 2;
            animation: lightning 4s ease-in-out infinite;
        }
        
        @keyframes lightning {
            0%, 100% { opacity: 0.3; transform: translateX(-50%) rotate(25deg) scaleY(0.8); }
            50% { opacity: 0.8; transform: translateX(-50%) rotate(25deg) scaleY(1.2); }
        }
        
        .main-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 2;
        }
        
        .container-fluid {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .samsung-card {
            background: rgba(20, 20, 30, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
            position: relative;
            z-index: 3;
        }
        
        .samsung-header {
            padding: 30px 30px 20px;
            text-align: center;
            position: relative;
        }
        
        .card-header {
            padding: 40px 30px 30px;
            text-align: center;
            position: relative;
        }
        
        .settings-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 24px;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .settings-icon:hover {
            color: #8a2be2;
        }
        
        .samsung-logo {
            font-family: 'SamsungSharpSans', sans-serif;
            font-size: 42px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 4px;
            margin: 0 auto 15px;
            text-shadow: 0 0 20px rgba(138, 43, 226, 0.5);
            text-align: center;
            display: block;
        }
        
        .nav-buttons {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 10;
        }
        
        .nav-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .nav-btn:hover {
            background: rgba(138, 43, 226, 0.3);
            color: white;
            transform: translateY(-1px);
            text-decoration: none;
        }
        
        .header-subtitle {
            font-family: 'SamsungOne', sans-serif;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            margin: 0;
            text-align: center;
        }
        
        .upload-section {
            padding: 0 30px 30px;
        }
        
        .upload-header-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 30px;
            border-radius: 20px 20px 0 0;
            margin: 0 30px;
        }
        
        .upload-section-title {
            font-family: 'SamsungOne', sans-serif;
            font-size: 24px;
            font-weight: 600;
            color: #333;
            text-align: center;
            margin: 0;
        }
        
        #upload-section {
            background: rgba(20, 20, 30, 0.9);
            border-radius: 0 0 20px 20px;
            margin: 0 30px;
            padding: 30px;
        }
        
        .upload-title {
            font-family: 'SamsungOne', sans-serif;
            font-size: 20px;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.9);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .upload-area {
            background: transparent;
            border: 2px dashed rgba(255, 255, 255, 0.4);
            border-radius: 20px;
            padding: 60px 20px;
            text-align: center;
            position: relative;
            transition: all 0.3s ease;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .upload-area:hover {
            border-color: rgba(138, 43, 226, 0.8);
            background: rgba(138, 43, 226, 0.05);
        }
        
        .upload-area.dragover {
            border-color: rgba(30, 144, 255, 0.8);
            background: rgba(30, 144, 255, 0.05);
            transform: scale(1.02);
        }
        
        .upload-icon {
            width: 60px;
            height: 60px;
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            transition: all 0.3s ease;
        }
        
        .upload-icon i {
            font-size: 24px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .upload-area:hover .upload-icon {
            border-color: rgba(138, 43, 226, 0.8);
        }
        
        .upload-area:hover .upload-icon i {
            color: rgba(138, 43, 226, 0.9);
        }
        
        .upload-text {
            font-family: 'SamsungOne', sans-serif;
            font-size: 16px;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 25px;
        }
        
        .file-input {
            display: none;
        }
        
        .upload-button {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 12px 24px;
            font-family: 'SamsungOne', sans-serif;
            font-size: 14px;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.9);
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .upload-button:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-1px);
        }
        
        .upload-button:active {
            transform: translateY(0);
        }
        
        /* Progress and status styles */
        .progress-container {
            margin-top: 30px;
            display: none;
        }
        
        .progress {
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-bar {
            background: linear-gradient(90deg, #8a2be2 0%, #1e90ff 100%);
            transition: width 0.3s ease;
        }
        
        .status-text {
            font-family: 'SamsungOne', sans-serif;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            text-align: center;
            margin-top: 15px;
        }
        
        /* Section visibility control */
        .processing-section {
            display: none;
            margin-top: 30px;
            padding: 30px;
            text-align: center;
            background: rgba(20, 20, 30, 0.8);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .result-section {
            display: none;
            margin-top: 30px;
            padding: 30px;
            background: rgba(20, 20, 30, 0.8);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .processing-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(138, 43, 226, 0.2);
            border-top: 4px solid #8a2be2;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .processing-title {
            font-family: 'SamsungOne', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: white;
            margin-bottom: 10px;
        }
        
        .processing-subtitle {
            font-family: 'SamsungOne', sans-serif;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 20px;
        }
        
        .processing-steps {
            font-family: 'SamsungOne', sans-serif;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
            text-align: left;
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 12px;
            margin: 20px 0;
        }
        
        .video-card {
            background: rgba(20, 20, 30, 0.8);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        
        .card-title {
            font-family: 'SamsungOne', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
        }
        
        .video-preview {
            width: 100%;
            max-width: 100%;
            height: auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            object-fit: contain;
            background: #000;
        }
        
        .settings-info {
            background: rgba(138, 43, 226, 0.1);
            border: 1px solid rgba(138, 43, 226, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            font-family: 'SamsungOne', sans-serif;
            color: white;
        }
        
        .processing-details {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .processing-details h6 {
            font-family: 'SamsungOne', sans-serif;
            font-weight: 700;
            color: white;
            margin-bottom: 15px;
        }
        
        .details-grid {
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        
        .detail-item {
            flex: 1;
        }
        
        .detail-value {
            font-family: 'SamsungOne', sans-serif;
            font-weight: 700;
            color: #8a2be2;
            font-size: 16px;
        }
        
        .detail-item small {
            color: rgba(255, 255, 255, 0.6);
            font-size: 12px;
        }
        
        .process-btn {
            width: 100%;
            background: linear-gradient(135deg, #8a2be2 0%, #1e90ff 100%);
            border: none;
            border-radius: 16px;
            padding: 20px;
            font-family: 'SamsungOne', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(138, 43, 226, 0.3);
            margin-top: 25px;
        }
        
        .process-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(138, 43, 226, 0.4);
        }
        
        .btn-subtitle {
            font-size: 12px;
            font-weight: 400;
            margin-top: 5px;
            opacity: 0.8;
        }
        
        /* Video progress section */
        .video-progress-section {
            margin: 20px 0;
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .progress-text {
            font-family: 'SamsungOne', sans-serif;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
        }
        
        .progress-percent {
            font-family: 'SamsungOne', sans-serif;
            font-size: 14px;
            color: #8a2be2;
            font-weight: 700;
        }
        
        .success-alert {
            background: linear-gradient(135deg, rgba(0, 200, 81, 0.2), rgba(0, 200, 81, 0.1));
            border: 1px solid rgba(0, 200, 81, 0.4);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            color: #00c851;
            font-family: 'SamsungOne', sans-serif;
        }
        
        .success-alert h5 {
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn-download {
            background: linear-gradient(135deg, #8a2be2 0%, #1e90ff 100%);
            border: none;
            border-radius: 16px;
            padding: 15px 25px;
            font-family: 'SamsungOne', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: white;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(138, 43, 226, 0.3);
            display: inline-block;
        }
        
        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(138, 43, 226, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            padding: 15px 25px;
            font-family: 'SamsungOne', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }
        
        /* iPad specific optimizations */
        @media (min-width: 768px) and (max-width: 1024px) {
            .samsung-card {
                max-width: 700px;
                margin: 30px auto;
            }
            
            .samsung-logo {
                font-size: 48px;
                letter-spacing: 5px;
            }
            
            .header-subtitle {
                font-size: 16px;
            }
            
            .upload-section-title {
                font-size: 28px;
            }
            
            .upload-area {
                min-height: 350px;
                padding: 50px 40px;
            }
            
            .upload-icon {
                width: 100px;
                height: 100px;
            }
            
            .upload-icon i {
                font-size: 48px;
            }
            
            .upload-title {
                font-size: 28px;
                margin-bottom: 15px;
            }
            
            .upload-subtitle {
                font-size: 16px;
                margin-bottom: 35px;
            }
            
            .upload-btn {
                padding: 18px 35px;
                font-size: 16px;
            }
            
            .video-card {
                padding: 30px;
            }
            
            .video-preview {
                min-height: 200px;
                max-height: 300px;
                border-radius: 20px;
            }
            
            .card-title {
                font-size: 20px;
            }
            
            .process-btn {
                padding: 22px;
                font-size: 18px;
            }
            
            .container-fluid {
                padding: 40px;
            }
        }
        
        /* Mobile responsiveness */
        @media (max-width: 767px) {
            .samsung-card {
                margin: 15px;
                border-radius: 24px;
                max-width: calc(100vw - 30px);
            }
            
            .samsung-logo {
                font-size: 32px;
                letter-spacing: 3px;
            }
            
            .header-subtitle {
                font-size: 12px;
            }
            
            .upload-section-title {
                font-size: 20px;
            }
            
            .upload-area {
                min-height: 280px;
                padding: 40px 20px;
            }
            
            .upload-icon {
                width: 70px;
                height: 70px;
            }
            
            .upload-icon i {
                font-size: 28px;
            }
            
            .upload-title {
                font-size: 22px;
            }
            
            .upload-subtitle {
                font-size: 14px;
            }
            
            .upload-btn {
                padding: 14px 28px;
                font-size: 14px;
            }
            
            .container-fluid {
                padding: 15px;
            }
            
            .video-card {
                padding: 20px;
            }
            
            .video-preview {
                min-height: 150px;
                max-height: 220px;
                border-radius: 15px;
            }
            
            .details-grid {
                flex-direction: column;
                gap: 15px;
            }
            
            .process-btn {
                padding: 18px;
                font-size: 15px;
            }
        }
        
        /* Toast Notification Styles */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            padding: 20px 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            z-index: 9999;
            transform: translateX(400px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            max-width: 350px;
        }
        
        .toast-notification.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .toast-content {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .toast-success .toast-content i {
            color: #27ae60;
            font-size: 18px;
        }
        
        .toast-error .toast-content i {
            color: #e74c3c;
            font-size: 18px;
        }
        
        .toast-success {
            border-left: 4px solid #27ae60;
        }
        
        .toast-error {
            border-left: 4px solid #e74c3c;
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    
    <div class="container-fluid">
        <div class="samsung-card">
            <!-- Header Section -->
            <div class="card-header">
                <div class="settings-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <h1 class="samsung-logo">SAMSUNG</h1>
                <div class="nav-buttons">
                    <a href="video-gallery.html" class="nav-btn">
                        <i class="fas fa-images me-1"></i>Gallery
                    </a>
                </div>
                <div class="lightning-effect"></div>
            </div>
            
            <!-- Main Content -->
            <div class="card-body">
                <!-- Upload Header -->
                <div class="upload-header-section">
                    <h3 class="upload-section-title">Upload your video here</h3>
                </div>
                
                <!-- Upload Section -->
                <div id="upload-section">
                    <div class="upload-area" id="upload-area">
                        <div class="upload-icon">
                            <i class="fas fa-upload"></i>
                        </div>
                        <p class="upload-text">Choose Video File</p>
                        <input type="file" id="video-input" accept="video/*" style="display: none;">
                        <button class="upload-button" onclick="document.getElementById('video-input').click()">
                            Choose File
                        </button>
                    </div>
                </div>
                
                <!-- Video Controls -->
                <div id="video-controls" style="display: none;">
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="video-card">
                                <h5 class="card-title">
                                    <i class="fas fa-play-circle me-2"></i>Video Preview
                                </h5>
                                <video id="video-preview" class="video-preview" controls></video>
                                
                                <!-- Progress Section -->
                                <div id="video-progress" class="video-progress-section" style="display: none;">
                                    <div class="progress-info">
                                        <span class="progress-text">Processing video...</span>
                                        <span class="progress-percent" id="progress-percent">0%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar" id="video-progress-bar"></div>
                                    </div>
                                </div>
                                
                                <button class="process-btn" id="process-btn">
                                    <i class="fas fa-magic me-2"></i>Test Template & Audio
                                    <div class="btn-subtitle">Checking dimension fit</div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Processing Status -->
                <div id="processing-status" class="processing-section">
                    <div class="processing-spinner"></div>
                    <h5 class="processing-title">Creating Slow Motion Video...</h5>
                    <div class="progress">
                        <div class="progress-bar" id="process-progress"></div>
                    </div>
                    <div class="status-text" id="status-text">Processing...</div>
                </div>
                
                <!-- Result Section -->
                <div id="result-section" class="result-section">
                    <div class="success-alert">
                        <h5><i class="fas fa-check-circle me-2"></i>Complete!</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="video-card">
                                <h6><i class="fas fa-file-video me-2"></i>Original</h6>
                                <video id="original-video" class="video-preview" controls></video>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="video-card">
                                <h6><i class="fas fa-magic me-2"></i>Processed</h6>
                                <video id="processed-video" class="video-preview" controls></video>
                                <div class="action-buttons">
                                    <a href="#" class="btn-download" id="download-btn" download>
                                        <i class="fas fa-download me-2"></i>Download
                                    </a>
                                    <button class="btn-secondary" onclick="location.reload()">
                                        <i class="fas fa-plus me-2"></i>New Video
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Particle animation
        function createParticles() {
            const particles = document.getElementById('particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.style.position = 'absolute';
                particle.style.width = Math.random() * 4 + 1 + 'px';
                particle.style.height = particle.style.width;
                particle.style.background = `rgba(${Math.random() * 100 + 155}, ${Math.random() * 100 + 155}, 255, ${Math.random() * 0.5 + 0.3})`;
                particle.style.borderRadius = '50%';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDuration = Math.random() * 3 + 2 + 's';
                particle.style.animationDelay = Math.random() * 2 + 's';
                particle.style.animation = 'float ' + particle.style.animationDuration + ' ease-in-out infinite ' + particle.style.animationDelay;
                particles.appendChild(particle);
            }
        }
        
        // Lightning effect
        function createLightning() {
            const lightning = document.querySelector('.lightning-effect');
            const intensity = Math.random() * 0.3 + 0.1;
            lightning.style.background = `radial-gradient(ellipse at center, rgba(138, 43, 226, ${intensity}) 0%, transparent 70%)`;
            
            setTimeout(() => {
                lightning.style.background = 'transparent';
            }, 150);
        }
        
        // Initialize effects
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            
            // Lightning effect every 3-7 seconds
            setInterval(createLightning, Math.random() * 4000 + 3000);
            
            // Add global error handling
            window.addEventListener('error', (event) => {
                console.error('Page error:', event.error);
            });
            
            window.addEventListener('unhandledrejection', (event) => {
                console.error('Unhandled promise rejection on page:', event.reason);
            });
        });
    </script>
    <script src="js/video-processor.js"></script>
</body>
</html>
