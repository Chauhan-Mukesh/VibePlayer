<?php
// Terabox link resolver with multiple proxy options
if (isset($_GET['resolve'])) {
    header('Content-Type: application/json');
    
    $url = filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL);
    if (!$url || !str_contains($url, 'terabox.com')) {
        echo json_encode(['success' => false, 'message' => 'Invalid Terabox URL']);
        exit;
    }

    $proxies = [
        'https://corsproxy.io/?',
        'https://api.allorigins.win/raw?url=',
        'https://cors-anywhere.herokuapp.com/'
    ];
    
    $resolvedUrl = null;
    $errors = [];
    
    foreach ($proxies as $proxy) {
        try {
            $targetUrl = $proxy . urlencode($url);
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $targetUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode !== 200) {
                $errors[] = "Proxy $proxy returned HTTP $httpCode";
                continue;
            }
            
            // Extract video URL from HTML
            $patterns = [
                '/"play_url":"(.*?)"/',
                '/sources:\["(.*?)"\]/',
                '/"dlink":"(.*?)"/',
                '/video_url":"(.*?)"/'
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    $resolvedUrl = stripslashes($matches[1]);
                    break 2;
                }
            }
            
            $errors[] = "Proxy $proxy: No video URL found";
        } catch (Exception $e) {
            $errors[] = "Proxy $proxy error: " . $e->getMessage();
        } finally {
            curl_close($ch);
        }
    }
    
    if ($resolvedUrl) {
        echo json_encode(['success' => true, 'url' => $resolvedUrl]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Resolution failed', 'errors' => $errors]);
    }
    exit;
}

// Handle video download
if (isset($_GET['download'])) {
    $videoUrl = filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL);
    if (!$videoUrl) {
        header("HTTP/1.1 400 Bad Request");
        exit;
    }
    
    // Extract filename from URL
    $filename = basename(parse_url($videoUrl, PHP_URL_PATH));
    if (!$filename) $filename = 'video_' . time() . '.mp4';
    
    // Set headers for download
    header('Content-Type: video/mp4');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Stream the video
    try {
        $context = stream_context_create(['http' => ['timeout' => 300]]);
        readfile($videoUrl, false, $context);
    } catch (Exception $e) {
        header("HTTP/1.1 500 Internal Server Error");
        echo "Download failed: " . $e->getMessage();
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO & AI Optimization -->
    <title>Vibe Player | The Ultimate Open Source Streaming Hub</title>
    <meta name="description" content="Vibe Player is a feature-rich, open-source video player that streams direct video links with a beautiful UI, keyboard shortcuts, history, and advanced features like proxy support.">
    <meta name="keywords" content="video player, streaming, open source, terabox player, vibe player, html5 video, custom player, video download">
    <link rel="canonical" href="https://your-domain.com/"> <!-- Replace with your actual domain -->

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://your-domain.com/">
    <meta property="og:title" content="Vibe Player | The Ultimate Open Source Streaming Hub">
    <meta property="og:description" content="Stream any direct video link with a beautiful, powerful, and feature-rich player.">
    <meta property="og:image" content="https://placehold.co/1200x630/0d6efd/ffffff?text=Vibe+Player">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://your-domain.com/">
    <meta property="twitter:title" content="Vibe Player | The Ultimate Open Source Streaming Hub">
    <meta property="twitter:description" content="Stream any direct video link with a beautiful, powerful, and feature-rich player.">
    <meta property="twitter:image" content="https://placehold.co/1200x630/0d6efd/ffffff?text=Vibe+Player">

    <!-- Structured Data for SEO & AI -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "Vibe Player",
      "applicationCategory": "MultimediaApplication",
      "operatingSystem": "Web",
      "description": "An advanced, open-source HTML5 video player for streaming direct video links with a rich user interface, keyboard shortcuts, playback history, and proxy support to bypass CORS restrictions.",
      "featureList": [
        "Direct video link playback",
        "Terabox Link Resolution",
        "Light & Dark Themes",
        "Keyboard Shortcuts",
        "Playback Speed Control",
        "Picture-in-Picture Mode",
        "Fullscreen & Theater Modes",
        "Video Download with Progress",
        "Playback History",
        "Seekbar Thumbnail Previews",
        "Custom CORS Proxy Support"
      ],
      "offers": {
        "@type": "Offer",
        "price": "0"
      }
    }
    </script>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #f8f9fa; --text-color: #212529; --text-muted-color: #6c757d;
            --container-bg-color: #ffffff; --input-bg-color: #f1f3f5; --input-border-color: #dee2e6;
            --glow-color: rgba(73, 80, 87, 0.2); --accent-color: #007bff; --hero-bg: #ffffff;
            --hero-card-bg: #f8f9fa;
        }
        html.dark {
            --bg-color: #121212; --text-color: #e9ecef; --text-muted-color: #adb5bd;
            --container-bg-color: #1c1c1c; --input-bg-color: #2c2c2c; --input-border-color: #495057;
            --glow-color: rgba(0, 123, 255, 0.2); --accent-color: #0d6efd; --hero-bg: #1c1c1c;
            --hero-card-bg: #2c2c2c;
        }
        *, *::before, *::after { transition: background-color 0.4s ease, color 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-color); }
        .video-container { box-shadow: 0 0 40px var(--glow-color); }
        .input-glow:focus-within { box-shadow: 0 0 15px var(--glow-color); }
        .hero-section { background-color: var(--hero-bg); }
        .slider-card-bg { background-color: var(--hero-card-bg); }
        .info-box { background-color: rgba(0, 123, 255, 0.1); border-color: rgba(0, 123, 255, 0.2); color: var(--text-color); }
        #loadVideoBtn { background-color: var(--accent-color); } #loadVideoBtn:hover { background-color: #0b5ed7; }
        .control-button { background-color: rgba(255,255,255,0.1); border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; transition: background-color 0.2s cubic-bezier(0.4, 0, 0.2, 1), transform 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
        .control-button:hover { background-color: rgba(255,255,255,0.2); transform: scale(1.1); }
        input[type="range"] { -webkit-appearance: none; background: transparent; }
        input[type="range"]::-webkit-slider-runnable-track { height: 6px; border-radius: 3px; background: rgba(156, 163, 175, 0.5); }
        input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; height: 16px; width: 16px; border-radius: 50%; background: var(--accent-color); margin-top: -5px; cursor: pointer; }
        input[type="range"]::-moz-range-track { height: 6px; border-radius: 3px; background: rgba(156, 163, 175, 0.5); }
        input[type="range"]::-moz-range-thumb { height: 16px; width: 16px; border-radius: 50%; background: var(--accent-color); border: none; cursor: pointer; }
        #downloadProgressBar { background-color: var(--accent-color); }
        .slider-container { overflow: hidden; } .slider-track { display: flex; transition: transform 0.5s ease-in-out; }
        .slider-card { flex: 0 0 100%; } @media (min-width: 768px) { .slider-card { flex: 0 0 33.3333%; } }
        #center-action-icon { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.8); font-size: 4rem; color: rgba(255, 255, 255, 0.8); background-color: rgba(0, 0, 0, 0.4); border-radius: 50%; width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.3s cubic-bezier(0.25, 0.1, 0.25, 1), transform 0.3s cubic-bezier(0.25, 0.1, 0.25, 1); }
        #center-action-icon.visible { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        .volume-bar-container { width: 80px; height: 10px; background-color: rgba(0,0,0,0.5); border-radius: 5px; overflow: hidden;}
        .volume-bar { height: 100%; background-color: white; width: 100%; transform-origin: left; transition: transform 0.2s cubic-bezier(0.25, 0.1, 0.25, 1);}
        #toast-container { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999; display: flex; flex-direction: column; gap: 0.75rem; }
        .toast { display: flex; align-items: center; padding: 1rem; border-radius: 0.5rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); transform: translateX(120%); opacity: 0; transition: transform 0.5s ease, opacity 0.5s ease; }
        .toast.show { transform: translateX(0); opacity: 1; }
        #settings-menu, .custom-context-menu { background-color: rgba(28, 28, 28, 0.9); backdrop-filter: blur(5px); }
        #thumbnail-container { position: absolute; bottom: 60px; border-radius: 8px; border: 2px solid rgba(255,255,255,0.7); background-color: black; opacity: 0; transition: opacity 0.2s, transform 0.2s; pointer-events: none; transform: scale(0.95); overflow: hidden;}
        #thumbnail-container.visible { opacity: 1; transform: scale(1); }
        #thumbnail-time { position: absolute; bottom: 5px; left: 50%; transform: translateX(-50%); background-color: rgba(0,0,0,0.7); color: white; padding: 2px 6px; font-size: 12px; border-radius: 4px; }
        .tab-button.active { background-color: var(--accent-color); color: white; }
        .volume-slider-container { position: absolute; bottom: 60px; left: 50%; transform: translateX(-50%); background-color: rgba(28, 28, 28, 0.9); backdrop-filter: blur(5px); padding: 1rem 0.5rem; border-radius: 20px; opacity: 0; transform: translateY(10px); transition: opacity 0.3s ease, transform 0.3s ease; pointer-events: none; }
        .volume-control:hover .volume-slider-container { opacity: 1; transform: translateY(0); pointer-events: auto; }
        input[type="range"][orient="vertical"] { writing-mode: bt-lr; -webkit-appearance: slider-vertical; width: 8px; height: 100px; }
        @media (max-width: 640px) {
            .control-button { width: 36px; height: 36px; font-size: 0.9rem; }
            .video-controls .text-sm { font-size: 0.75rem; }
            .slider-card { padding: 0.5rem; }
            .slider-card-bg { padding: 1rem; }
        }
        .theater-mode #app-wrapper { max-width: 100%; }
        .theater-mode .hero-section, .theater-mode .input-glow, .theater-mode .info-box { display: none; }
        #thumbnailVideo { position: absolute; top: 0; left: 0; width: 10px; height: 10px; opacity: 0; pointer-events: none; }
        .progress-container { position: relative; margin-bottom: 1rem; }
        #progressBar { width: 100%; }
        #bufferBar { position: absolute; top: 0; left: 0; height: 100%; background-color: rgba(255, 255, 255, 0.3); width: 0%; pointer-events: none; }
        #videoPlayerWrapper.theater { max-width: 100%; height: calc(100vh - 20px); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4 sm:p-6">

    <div id="app-wrapper" class="w-full max-w-4xl lg:max-w-5xl 2xl:max-w-7xl mx-auto space-y-6">
        <!-- Header -->
        <div class="text-center relative">
            <h1 class="text-4xl md:text-5xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-500 to-purple-600">Vibe Player</h1>
            <p class="mt-2" style="color: var(--text-muted-color);">The Ultimate Hub for Seamless Streaming.</p>
            <button id="theme-toggle" class="absolute top-0 right-0 p-2 rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[var(--bg-color)] focus:ring-[var(--accent-color)]"><i class="fas fa-sun text-xl"></i></button>
        </div>
        
        <!-- Features Slider -->
        <div class="p-6 rounded-2xl shadow-lg hero-section">
            <div class="slider-container">
                <div class="slider-track"></div>
            </div>
        </div>
        
        <!-- URL Input -->
        <div class="relative input-glow rounded-full" style="background-color: var(--input-bg-color);">
            <span id="url-status-icon" class="absolute inset-y-0 left-0 flex items-center pl-4"><i class="fas fa-link" style="color: var(--text-muted-color);"></i></span>
            <input id="videoUrl" type="text" placeholder="Paste a direct video link or Terabox link here..." class="w-full bg-transparent border-2 rounded-full py-3 pl-12 pr-4 focus:outline-none" style="border-color: var(--input-border-color); color: var(--text-color);">
            <button id="loadVideoBtn" class="absolute inset-y-0 right-0 flex items-center px-4 text-white rounded-r-full"><i class="fas fa-play"></i></button>
        </div>
        
        <!-- Info Box -->
        <div class="p-4 rounded-lg border info-box"><p><i class="fas fa-info-circle mr-2"></i><strong>Terabox links are auto-resolved!</strong> For other restricted sites, use the <strong>Proxy</strong> setting.</p></div>

        <!-- Video Player -->
        <div id="playerContainer" class="hidden">
            <div id="videoPlayerWrapper" class="relative w-full aspect-video rounded-lg overflow-hidden bg-black video-container">
                <video id="mainVideo" class="w-full h-full" crossorigin="anonymous"></video>
                <video id="thumbnailVideo" class="w-full h-full" crossorigin="anonymous" muted preload="auto"></video>
                <div id="center-action-icon"></div>
                <div id="thumbnail-container"><canvas id="thumbnail-canvas"></canvas><span id="thumbnail-time">00:00</span></div>
                <div class="absolute bottom-0 left-0 right-0 p-2 md:p-4 bg-gradient-to-t from-black/70 to-transparent video-controls opacity-100">
                    <div class="progress-container">
                        <div id="bufferBar"></div>
                        <input id="progressBar" type="range" min="0" max="100" value="0" class="w-full h-2 rounded-lg cursor-pointer" style="--progress: 0%;">
                    </div>
                    <div class="flex justify-between items-center text-white flex-wrap gap-2">
                        <div class="flex items-center space-x-2 md:space-x-4 flex-wrap">
                            <button id="playPauseBtn" class="control-button text-xl"><i class="fas fa-play"></i></button>
                            <button id="rewindBtn" class="control-button text-lg"><i class="fas fa-backward"></i></button>
                            <button id="forwardBtn" class="control-button text-lg"><i class="fas fa-forward"></i></button>
                            <div class="relative volume-control">
                                <button id="volumeBtn" class="control-button"><i class="fas fa-volume-high"></i></button>
                                <div class="volume-slider-container">
                                    <input id="volumeSlider" type="range" min="0" max="1" step="0.01" value="1" orient="vertical">
                                </div>
                            </div>
                            <div id="timeDisplay" class="text-sm font-mono">00:00 / 00:00</div>
                        </div>
                        <div class="flex items-center space-x-2 md:space-x-4 flex-wrap">
                            <div class="relative">
                                <button id="settingsBtn" class="control-button"><i class="fas fa-cog"></i></button>
                                <div id="settings-menu" class="hidden absolute bottom-full right-0 mb-2 rounded-md p-2 text-white w-72">
                                    <div class="flex border-b border-gray-600 mb-2">
                                        <button data-tab="settings" class="tab-button flex-1 p-2 text-sm font-bold active">Settings</button>
                                        <button data-tab="history" class="tab-button flex-1 p-2 text-sm font-bold">History</button>
                                    </div>
                                    <div id="settings-tab" class="space-y-2 p-2">
                                        <div class="flex items-center justify-between"><span>Video Quality</span><span class="px-2 py-1 text-xs rounded-md bg-gray-600">Auto</span></div>
                                        <label class="flex items-center justify-between space-x-2 cursor-pointer"><span>Loop Video</span><input type="checkbox" id="loopToggle" class="form-checkbox h-4 w-4 text-[var(--accent-color)] bg-gray-700 border-gray-600 rounded focus:ring-offset-0 focus:ring-0"></label>
                                        <div class="flex items-center justify-between space-x-2 cursor-pointer">
                                            <span>Playback Speed</span>
                                            <select id="playbackSpeed" class="bg-gray-900 border border-gray-600 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-[var(--accent-color)]">
                                                <option value="0.5">0.5x</option>
                                                <option value="0.75">0.75x</option>
                                                <option value="1" selected>1x</option>
                                                <option value="1.25">1.25x</option>
                                                <option value="1.5">1.5x</option>
                                                <option value="2">2x</option>
                                            </select>
                                        </div>
                                        <hr class="my-2 border-gray-600">
                                        <label class="block text-sm font-bold mb-1">CORS Proxy URL</label>
                                        <input type="text" id="proxyUrlInput" placeholder="https://my-proxy.com/" class="w-full bg-gray-900 border border-gray-600 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-[var(--accent-color)]">
                                        <p class="text-xs text-gray-400 mt-1">For advanced users to bypass streaming restrictions.</p>
                                    </div>
                                    <div id="history-tab" class="hidden p-2 max-h-48 overflow-y-auto"></div>
                                </div>
                            </div>
                            <button id="theaterBtn" class="control-button"><i class="fas fa-rectangle-xmark"></i></button>
                            <button id="pipBtn" class="control-button"><i class="fas fa-clone"></i></button>
                            <button id="fullscreenBtn" class="control-button"><i class="fas fa-expand"></i></button>
                            <button id="downloadBtn" class="control-button"><i class="fas fa-download"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Download Progress -->
        <div id="downloadProgressContainer" class="hidden w-full bg-gray-200 dark:bg-gray-700 rounded-full">
            <div id="downloadProgressBar" class="text-xs font-medium text-blue-100 text-center p-0.5 leading-none rounded-full" style="width: 0%">0%</div>
        </div>
    </div>
    
    <!-- Toast Notifications -->
    <div id="toast-container"></div>
    
    <!-- Context Menu -->
    <div id="custom-context-menu" class="custom-context-menu hidden fixed p-2 rounded-md shadow-lg text-white z-50"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Main application module
            const App = {
                init() {
                    this.setupGlobalErrorHandling();
                    Theme.init();
                    HeroSlider.init();
                    Player.init();
                    this.setupEventListeners();
                },
                
                setupGlobalErrorHandling() {
                    const handleError = (message) => UI.showToast(message, 'error');
                    window.addEventListener('error', (e) => handleError(`Script error: ${e.message}`));
                    window.addEventListener('unhandledrejection', (e) => {
                        if (e.reason && e.reason.name === 'AbortError') return;
                        handleError(`Unhandled error: ${e.reason}`);
                    });
                },
                
                setupEventListeners() {
                    document.getElementById('loadVideoBtn').addEventListener('click', () => Player.loadVideo());
                    document.getElementById('videoUrl').addEventListener('keypress', (e) => e.key === 'Enter' && Player.loadVideo());
                }
            };

            // Theme management module
            const Theme = {
                init() {
                    this.themeToggle = document.getElementById('theme-toggle');
                    this.themeToggle.addEventListener('click', () => this.toggle());
                    this.apply(localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
                },
                
                apply(theme) {
                    const isDark = theme === 'dark';
                    document.documentElement.classList.toggle('dark', isDark);
                    this.themeToggle.querySelector('i').className = `fas ${isDark ? 'fa-sun' : 'fa-moon'} text-xl`;
                },
                
                toggle() {
                    const newTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
                    localStorage.setItem('theme', newTheme);
                    this.apply(newTheme);
                }
            };

            // Hero slider module
            const HeroSlider = {
                init() {
                    this.sliderTrack = document.querySelector('.slider-track');
                    this.features = [
                        { icon: 'fa-server', title: 'Resolver Built-in', desc: 'Automatically resolves Terabox links.' },
                        { icon: 'fa-keyboard', title: 'Shortcuts', desc: 'Control playback with your keyboard.' },
                        { icon: 'fa-palette', title: 'Themes', desc: 'Switch between light and dark modes instantly.' },
                        { icon: 'fa-download', title: 'Download', desc: 'Save videos with progress tracking.' },
                        { icon: 'fa-expand-arrows-alt', title: 'Modes', desc: 'Enjoy theater, PiP, and fullscreen views.' },
                        { icon: 'fa-cog', title: 'Settings', desc: 'Loop your favorite videos.' }
                    ];
                    
                    this.sliderTrack.innerHTML = [...this.features, ...this.features]
                        .map(f => `
                            <div class="slider-card p-4">
                                <div class="p-6 rounded-xl h-full flex items-center space-x-4 slider-card-bg">
                                    <i class="fas ${f.icon} text-2xl text-[var(--accent-color)]"></i>
                                    <div>
                                        <h3 class="font-bold text-lg">${f.title}</h3>
                                        <p class="text-sm" style="color: var(--text-muted-color);">${f.desc}</p>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    
                    this.currentIndex = 0;
                    setInterval(() => this.slide(), 3000);
                },
                
                slide() {
                    this.currentIndex++;
                    this.sliderTrack.style.transform = `translateX(-${this.currentIndex * (100/3)}%)`;
                    
                    if (this.currentIndex >= this.features.length) {
                        setTimeout(() => {
                            this.sliderTrack.style.transition = 'none';
                            this.currentIndex = 0;
                            this.sliderTrack.style.transform = 'translateX(0)';
                            setTimeout(() => this.sliderTrack.style.transition = 'transform 0.5s ease-in-out', 50);
                        }, 500);
                    }
                }
            };
            
            // UI utilities module
            const UI = {
                /**
                 * Displays a toast notification
                 * @param {string} message - The message to display
                 * @param {string} type - The type of toast (info, success, error)
                 */
                showToast(message, type = 'info') {
                    const toastContainer = document.getElementById('toast-container');
                    const toast = document.createElement('div');
                    const icons = { 
                        info: 'fa-info-circle', 
                        success: 'fa-check-circle', 
                        error: 'fa-exclamation-circle' 
                    };
                    const colors = { 
                        info: 'bg-blue-600', 
                        success: 'bg-green-600', 
                        error: 'bg-red-600' 
                    };
                    
                    toast.className = `toast text-white ${colors[type]}`;
                    toast.innerHTML = `<i class="fas ${icons[type]} mr-3 text-xl"></i><span>${message}</span>`;
                    toastContainer.appendChild(toast);
                    
                    setTimeout(() => toast.classList.add('show'), 10);
                    
                    setTimeout(() => {
                        toast.classList.remove('show');
                        toast.addEventListener('transitionend', () => toast.remove());
                    }, 5000);
                },
                
                /**
                 * Shows an action icon in the center of the video
                 * @param {string} iconClass - The FontAwesome icon class
                 * @param {string} content - Optional custom content
                 */
                showActionIcon(iconClass, content = '') {
                    const centerActionIcon = document.getElementById('center-action-icon');
                    centerActionIcon.innerHTML = content || `<i class="fas ${iconClass}"></i>`;
                    centerActionIcon.classList.add('visible');
                    
                    clearTimeout(this.actionIconTimeout);
                    this.actionIconTimeout = setTimeout(() => {
                        centerActionIcon.classList.remove('visible');
                    }, 800);
                },
                
                /**
                 * Updates the URL input status icon
                 * @param {string} status - The status (loading, idle)
                 */
                setUrlStatus(status) {
                    const iconEl = document.getElementById('url-status-icon');
                    iconEl.innerHTML = status === 'loading' 
                        ? `<i class="fas fa-spinner fa-spin" style="color: var(--accent-color);"></i>`
                        : `<i class="fas fa-link" style="color: var(--text-muted-color);"></i>`;
                }
            };

            // Video player module
            const Player = {
                /**
                 * Initializes the video player and sets up event listeners
                 */
                init() {
                    // Cache DOM elements
                    this.video = document.getElementById('mainVideo');
                    this.thumbnailVideo = document.getElementById('thumbnailVideo');
                    this.videoUrlInput = document.getElementById('videoUrl');
                    this.playerContainer = document.getElementById('playerContainer');
                    this.videoPlayerWrapper = document.getElementById('videoPlayerWrapper');
                    this.progressBar = document.getElementById('progressBar');
                    this.bufferBar = document.getElementById('bufferBar');
                    this.volumeSlider = document.getElementById('volumeSlider');
                    this.timeDisplay = document.getElementById('timeDisplay');
                    this.settingsMenu = document.getElementById('settings-menu');
                    this.playPauseBtn = document.getElementById('playPauseBtn');
                    this.thumbnailContainer = document.getElementById('thumbnail-container');
                    this.thumbnailCanvas = document.getElementById('thumbnail-canvas');
                    this.thumbnailTime = document.getElementById('thumbnail-time');
                    
                    this.setupEventListeners();
                    this.loadSettings();
                    this.loadHistory();
                },
                
                setupEventListeners() {
                    // Video events
                    this.video.addEventListener('loadedmetadata', () => this.onVideoLoaded());
                    this.video.addEventListener('timeupdate', () => this.updateProgress());
                    this.video.addEventListener('progress', () => this.updateBuffer());
                    this.video.addEventListener('ended', () => this.onVideoEnded());
                    this.video.addEventListener('error', (e) => this.onVideoError(e));
                    
                    // Player controls
                    this.playPauseBtn.addEventListener('click', () => this.togglePlayPause());
                    document.getElementById('rewindBtn').addEventListener('click', () => this.seek(-10));
                    document.getElementById('forwardBtn').addEventListener('click', () => this.seek(10));
                    document.getElementById('fullscreenBtn').addEventListener('click', () => this.toggleFullscreen());
                    document.getElementById('theaterBtn').addEventListener('click', () => this.toggleTheater());
                    document.getElementById('pipBtn').addEventListener('click', () => this.togglePiP());
                    document.getElementById('downloadBtn').addEventListener('click', () => this.downloadVideo());
                    
                    // Progress bar
                    this.progressBar.addEventListener('input', () => this.onProgressInput());
                    this.progressBar.addEventListener('mousemove', (e) => this.showThumbnail(e));
                    this.progressBar.addEventListener('mouseleave', () => this.hideThumbnail());
                    
                    // Volume controls
                    this.volumeSlider.addEventListener('input', () => this.updateVolume());
                    document.getElementById('volumeBtn').addEventListener('click', () => this.toggleMute());
                    
                    // Settings
                    document.getElementById('settingsBtn').addEventListener('click', () => this.toggleSettings());
                    document.getElementById('playbackSpeed').addEventListener('change', (e) => this.setPlaybackSpeed(e.target.value));
                    document.getElementById('loopToggle').addEventListener('change', (e) => this.setLoop(e.target.checked));
                    
                    // Tab switching
                    document.querySelectorAll('.tab-button').forEach(btn => {
                        btn.addEventListener('click', (e) => this.switchTab(e.target.dataset.tab));
                    });
                    
                    // Keyboard shortcuts
                    document.addEventListener('keydown', (e) => this.handleKeyboard(e));
                    
                    // Click to play/pause
                    this.video.addEventListener('click', () => this.togglePlayPause());
                    
                    // Hide settings when clicking outside
                    document.addEventListener('click', (e) => {
                        if (!e.target.closest('#settingsBtn') && !e.target.closest('#settings-menu')) {
                            this.settingsMenu.classList.add('hidden');
                        }
                    });
                },
                
                async loadVideo() {
                    const url = this.videoUrlInput.value.trim();
                    if (!url) {
                        UI.showToast('Please enter a video URL', 'error');
                        return;
                    }
                    
                    UI.setUrlStatus('loading');
                    
                    try {
                        let videoUrl = url;
                        
                        // Check if it's a Terabox link
                        if (url.includes('terabox.com')) {
                            const response = await fetch(`?resolve&url=${encodeURIComponent(url)}`);
                            const result = await response.json();
                            
                            if (result.success) {
                                videoUrl = result.url;
                                UI.showToast('Terabox link resolved successfully!', 'success');
                            } else {
                                throw new Error(result.message || 'Failed to resolve Terabox link');
                            }
                        }
                        
                        // Apply proxy if configured
                        const proxyUrl = document.getElementById('proxyUrlInput').value.trim();
                        if (proxyUrl && !url.includes('terabox.com')) {
                            videoUrl = proxyUrl + encodeURIComponent(videoUrl);
                        }
                        
                        this.video.src = videoUrl;
                        this.thumbnailVideo.src = videoUrl;
                        this.playerContainer.classList.remove('hidden');
                        
                        // Save to history
                        this.saveToHistory(url, videoUrl);
                        
                        UI.showToast('Video loaded successfully!', 'success');
                    } catch (error) {
                        UI.showToast(error.message, 'error');
                    } finally {
                        UI.setUrlStatus('idle');
                    }
                },
                
                onVideoLoaded() {
                    this.progressBar.max = this.video.duration;
                    this.updateTimeDisplay();
                },
                
                updateProgress() {
                    if (!this.video.duration) return;
                    
                    this.progressBar.value = this.video.currentTime;
                    this.updateTimeDisplay();
                    
                    // Update progress bar visual
                    const percent = (this.video.currentTime / this.video.duration) * 100;
                    this.progressBar.style.setProperty('--progress', `${percent}%`);
                },
                
                updateBuffer() {
                    if (!this.video.buffered.length || !this.video.duration) return;
                    
                    const bufferedEnd = this.video.buffered.end(this.video.buffered.length - 1);
                    const percent = (bufferedEnd / this.video.duration) * 100;
                    this.bufferBar.style.width = `${percent}%`;
                },
                
                updateTimeDisplay() {
                    if (!this.video.duration) return;
                    
                    const current = this.formatTime(this.video.currentTime);
                    const total = this.formatTime(this.video.duration);
                    this.timeDisplay.textContent = `${current} / ${total}`;
                },
                
                formatTime(seconds) {
                    const hours = Math.floor(seconds / 3600);
                    const mins = Math.floor((seconds % 3600) / 60);
                    const secs = Math.floor(seconds % 60);
                    
                    if (hours > 0) {
                        return `${hours}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                    }
                    return `${mins}:${secs.toString().padStart(2, '0')}`;
                },
                
                togglePlayPause() {
                    if (this.video.paused) {
                        this.video.play();
                        this.playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
                        UI.showActionIcon('fa-play');
                    } else {
                        this.video.pause();
                        this.playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                        UI.showActionIcon('fa-pause');
                    }
                },
                
                seek(seconds) {
                    this.video.currentTime = Math.max(0, Math.min(this.video.duration, this.video.currentTime + seconds));
                    UI.showActionIcon(seconds > 0 ? 'fa-forward' : 'fa-backward');
                },
                
                onProgressInput() {
                    this.video.currentTime = this.progressBar.value;
                },
                
                updateVolume() {
                    this.video.volume = this.volumeSlider.value;
                    this.updateVolumeIcon();
                },
                
                updateVolumeIcon() {
                    const volumeBtn = document.getElementById('volumeBtn');
                    const volume = this.video.volume;
                    let icon = 'fa-volume-high';
                    
                    if (volume === 0) icon = 'fa-volume-xmark';
                    else if (volume < 0.5) icon = 'fa-volume-low';
                    
                    volumeBtn.innerHTML = `<i class="fas ${icon}"></i>`;
                },
                
                toggleMute() {
                    if (this.video.volume > 0) {
                        this.lastVolume = this.video.volume;
                        this.video.volume = 0;
                        this.volumeSlider.value = 0;
                    } else {
                        this.video.volume = this.lastVolume || 1;
                        this.volumeSlider.value = this.video.volume;
                    }
                    this.updateVolumeIcon();
                },
                
                toggleFullscreen() {
                    if (!document.fullscreenElement) {
                        this.videoPlayerWrapper.requestFullscreen();
                        document.getElementById('fullscreenBtn').innerHTML = '<i class="fas fa-compress"></i>';
                    } else {
                        document.exitFullscreen();
                        document.getElementById('fullscreenBtn').innerHTML = '<i class="fas fa-expand"></i>';
                    }
                },
                
                toggleTheater() {
                    document.body.classList.toggle('theater-mode');
                    const btn = document.getElementById('theaterBtn');
                    const isTheater = document.body.classList.contains('theater-mode');
                    btn.innerHTML = isTheater ? '<i class="fas fa-rectangle-xmark"></i>' : '<i class="fas fa-rectangle-xmark"></i>';
                },
                
                async togglePiP() {
                    try {
                        if (document.pictureInPictureElement) {
                            await document.exitPictureInPicture();
                        } else {
                            await this.video.requestPictureInPicture();
                        }
                    } catch (error) {
                        UI.showToast('Picture-in-Picture not supported', 'error');
                    }
                },
                
                downloadVideo() {
                    const videoUrl = this.video.src;
                    if (!videoUrl) {
                        UI.showToast('No video loaded', 'error');
                        return;
                    }
                    
                    const downloadUrl = `?download&url=${encodeURIComponent(videoUrl)}`;
                    const a = document.createElement('a');
                    a.href = downloadUrl;
                    a.download = 'video.mp4';
                    a.click();
                    
                    UI.showToast('Download started', 'success');
                },
                
                showThumbnail(e) {
                    if (!this.video.duration) return;
                    
                    const rect = this.progressBar.getBoundingClientRect();
                    const percent = (e.clientX - rect.left) / rect.width;
                    const time = percent * this.video.duration;
                    
                    this.thumbnailTime.textContent = this.formatTime(time);
                    this.thumbnailContainer.style.left = `${e.clientX - rect.left - 80}px`;
                    this.thumbnailContainer.classList.add('visible');
                    
                    // Generate thumbnail
                    this.generateThumbnail(time);
                },
                
                hideThumbnail() {
                    this.thumbnailContainer.classList.remove('visible');
                },
                
                generateThumbnail(time) {
                    const canvas = this.thumbnailCanvas;
                    const ctx = canvas.getContext('2d');
                    
                    canvas.width = 160;
                    canvas.height = 90;
                    
                    // Create a temporary video element for thumbnail
                    const tempVideo = document.createElement('video');
                    tempVideo.src = this.video.src;
                    tempVideo.currentTime = time;
                    tempVideo.muted = true;
                    
                    tempVideo.addEventListener('seeked', () => {
                        ctx.drawImage(tempVideo, 0, 0, canvas.width, canvas.height);
                    }, { once: true });
                },
                
                toggleSettings() {
                    this.settingsMenu.classList.toggle('hidden');
                },
                
                switchTab(tab) {
                    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
                    document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
                    
                    document.getElementById('settings-tab').classList.toggle('hidden', tab !== 'settings');
                    document.getElementById('history-tab').classList.toggle('hidden', tab !== 'history');
                },
                
                setPlaybackSpeed(speed) {
                    this.video.playbackRate = parseFloat(speed);
                    UI.showToast(`Playback speed: ${speed}x`, 'info');
                },
                
                setLoop(loop) {
                    this.video.loop = loop;
                    localStorage.setItem('videoLoop', loop);
                },
                
                onVideoEnded() {
                    this.playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                },
                
                onVideoError(e) {
                    UI.showToast('Error loading video', 'error');
                    console.error('Video error:', e);
                },
                
                handleKeyboard(e) {
                    if (document.activeElement.tagName === 'INPUT') return;
                    
                    switch (e.code) {
                        case 'Space':
                            e.preventDefault();
                            this.togglePlayPause();
                            break;
                        case 'ArrowLeft':
                            e.preventDefault();
                            this.seek(-5);
                            break;
                        case 'ArrowRight':
                            e.preventDefault();
                            this.seek(5);
                            break;
                        case 'ArrowUp':
                            e.preventDefault();
                            this.video.volume = Math.min(1, this.video.volume + 0.1);
                            this.volumeSlider.value = this.video.volume;
                            this.updateVolumeIcon();
                            break;
                        case 'ArrowDown':
                            e.preventDefault();
                            this.video.volume = Math.max(0, this.video.volume - 0.1);
                            this.volumeSlider.value = this.video.volume;
                            this.updateVolumeIcon();
                            break;
                        case 'KeyF':
                            e.preventDefault();
                            this.toggleFullscreen();
                            break;
                        case 'KeyM':
                            e.preventDefault();
                            this.toggleMute();
                            break;
                    }
                },
                
                saveToHistory(originalUrl, resolvedUrl) {
                    const history = JSON.parse(localStorage.getItem('videoHistory') || '[]');
                    const entry = {
                        originalUrl,
                        resolvedUrl,
                        timestamp: Date.now(),
                        title: this.extractTitle(originalUrl)
                    };
                    
                    // Remove duplicate entries
                    const filtered = history.filter(item => item.originalUrl !== originalUrl);
                    filtered.unshift(entry);
                    
                    // Keep only last 50 entries
                    const limited = filtered.slice(0, 50);
                    localStorage.setItem('videoHistory', JSON.stringify(limited));
                    
                    this.loadHistory();
                },
                
                loadHistory() {
                    const history = JSON.parse(localStorage.getItem('videoHistory') || '[]');
                    const historyTab = document.getElementById('history-tab');
                    
                    if (history.length === 0) {
                        historyTab.innerHTML = '<p class="text-gray-400 text-sm">No history yet</p>';
                        return;
                    }
                    
                    historyTab.innerHTML = history.map(item => `
                        <div class="mb-2 p-2 bg-gray-800 rounded cursor-pointer hover:bg-gray-700" onclick="Player.loadFromHistory('${item.originalUrl}')">
                            <div class="text-sm font-bold truncate">${item.title}</div>
                            <div class="text-xs text-gray-400">${new Date(item.timestamp).toLocaleDateString()}</div>
                        </div>
                    `).join('');
                },
                
                loadFromHistory(url) {
                    this.videoUrlInput.value = url;
                    this.loadVideo();
                    this.settingsMenu.classList.add('hidden');
                },
                
                extractTitle(url) {
                    try {
                        const urlObj = new URL(url);
                        return urlObj.hostname + urlObj.pathname.split('/').pop();
                    } catch {
                        return url.substring(0, 50) + '...';
                    }
                },
                
                loadSettings() {
                    // Load saved settings
                    const loop = localStorage.getItem('videoLoop') === 'true';
                    document.getElementById('loopToggle').checked = loop;
                    
                    const proxyUrl = localStorage.getItem('proxyUrl') || '';
                    document.getElementById('proxyUrlInput').value = proxyUrl;
                    
                    // Save proxy URL when changed
                    document.getElementById('proxyUrlInput').addEventListener('input', (e) => {
                        localStorage.setItem('proxyUrl', e.target.value);
                    });
                }
            };

            // Initialize the application
            App.init();
        });
    </script>
</body>
</html>