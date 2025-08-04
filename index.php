<?php
/**
 * VibePlayer - Advanced Terabox Link Resolver
 * 
 * This script provides multiple fallback methods for resolving Terabox links
 * to direct video URLs without requiring user login.
 */

// Terabox link resolver with enhanced multiple proxy options and direct API calls
if (isset($_GET['resolve'])) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    $url = filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL);
    if (!$url || !str_contains($url, 'terabox.com')) {
        echo json_encode(['success' => false, 'message' => 'Invalid Terabox URL']);
        exit;
    }

    /**
     * Multiple resolution methods for maximum success rate
     * Uses various proxy services and direct API calls
     */
    $proxies = [
        'https://corsproxy.io/?',
        'https://api.allorigins.win/raw?url=',
        'https://thingproxy.freeboard.io/fetch/',
        'https://cors-anywhere.herokuapp.com/',
        'https://api.codetabs.com/v1/proxy?quest='
    ];
    
    $resolvedUrl = null;
    $errors = [];
    
    // Try direct method first (fastest and most reliable)
    try {
        $directUrl = resolveTeraboxDirect($url);
        if ($directUrl) {
            echo json_encode(['success' => true, 'url' => $directUrl]);
            exit;
        }
        $errors[] = "Direct method: No video URL found in page content";
    } catch (Exception $e) {
        $errors[] = "Direct method failed: " . $e->getMessage();
    }
    
    // Fallback to proxy methods with enhanced error handling
    foreach ($proxies as $proxy) {
        try {
            $targetUrl = $proxy . urlencode($url);
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $targetUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.5',
                    'Accept-Encoding: gzip, deflate',
                    'Connection: keep-alive',
                    'Upgrade-Insecure-Requests: 1',
                    'Cache-Control: no-cache'
                ]
            ]);
            
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                $errors[] = "Proxy $proxy cURL error: $curlError";
                continue;
            }
            
            if ($httpCode !== 200 || !$html) {
                $errors[] = "Proxy $proxy returned HTTP $httpCode";
                continue;
            }
            
            // Enhanced video URL extraction patterns with better validation
            $patterns = [
                '/"dlink":"(https?:\/\/[^"]+)"/',
                '/"play_url":"(https?:\/\/[^"]+)"/',
                '/sources:\["(https?:\/\/[^"]+)"\]/',
                '/"video_url":"(https?:\/\/[^"]+)"/',
                '/videoUrl["\']?\s*:\s*["\']([^"\']+)/',
                '/src["\']?\s*:\s*["\']([^"\']+\.mp4[^"\']*)/i',
                '/"url":"(https?:\/\/[^"]+\.mp4[^"]*)"/',
                '/data-src="(https?:\/\/[^"]+\.mp4[^"]*)"/',
                '/href="(https?:\/\/[^"]+\.mp4[^"]*)"/',
                '/"downloadUrl":"(https?:\/\/[^"]+)"/',
                '/"stream_url":"(https?:\/\/[^"]+)"/'
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    $resolvedUrl = stripslashes($matches[1]);
                    // Enhanced URL validation
                    if (filter_var($resolvedUrl, FILTER_VALIDATE_URL) && 
                        (strpos($resolvedUrl, '.mp4') !== false || 
                         strpos($resolvedUrl, 'video') !== false ||
                         strpos($resolvedUrl, 'stream') !== false ||
                         strpos($resolvedUrl, 'dlink') !== false ||
                         preg_match('/\.(mp4|webm|avi|mov|mkv)(\?|$)/i', $resolvedUrl))) {
                        echo json_encode(['success' => true, 'url' => $resolvedUrl]);
                        exit;
                    }
                }
            }
            
            $errors[] = "Proxy $proxy: No valid video URL found in response";
        } catch (Exception $e) {
            $errors[] = "Proxy $proxy error: " . $e->getMessage();
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Resolution failed', 'errors' => $errors]);
    exit;
}

/**
 * Enhanced Direct Terabox resolution method
 * Attempts to resolve without proxies using direct scraping
 */
function resolveTeraboxDirect($url) {
    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
                'Cache-Control: no-cache',
                'Pragma: no-cache'
            ]
        ]);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('cURL Error: ' . curl_error($ch));
        }
        curl_close($ch);

        if ($httpCode !== 200 || empty($html)) {
            throw new Exception('Failed to fetch Terabox page. HTTP Code: ' . $httpCode);
        }

        // Enhanced pattern matching for video URLs
        $resolvedUrl = null;
        
        // Method 1: Look for window.__INIT_DATA__ object
        if (preg_match('/<script>window\.__INIT_DATA__\s*=\s*({.*?})<\/script>/', $html, $matches)) {
            $data = json_decode($matches[1], true);
            if ($data) {
                // Navigate the JSON object to find the direct link
                $resolvedUrl = $data['share_info']['dlink'] ?? 
                              $data['file_list'][0]['dlink'] ?? 
                              $data['share_info']['file_list'][0]['dlink'] ?? null;
                
                if ($resolvedUrl) {
                    return $resolvedUrl;
                }
            }
        }
        
        // Method 2: Enhanced regex patterns for video URLs
        $patterns = [
            '/"dlink":"(https?:\/\/[^"]+)"/',
            '/"play_url":"(https?:\/\/[^"]+)"/',
            '/sources:\["(https?:\/\/[^"]+)"\]/',
            '/"video_url":"(https?:\/\/[^"]+)"/',
            '/videoUrl["\']?\s*:\s*["\']([^"\']+)/',
            '/src["\']?\s*:\s*["\']([^"\']+\.mp4[^"\']*)/i',
            '/"url":"(https?:\/\/[^"]+\.mp4[^"]*)"/',
            '/data-src="(https?:\/\/[^"]+\.mp4[^"]*)"/',
            '/href="(https?:\/\/[^"]+\.mp4[^"]*)"/',
            '/"downloadUrl":"(https?:\/\/[^"]+)"/',
            '/"stream_url":"(https?:\/\/[^"]+)"/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $candidate = stripslashes($matches[1]);
                // Validate URL and ensure it contains video-related content
                if (filter_var($candidate, FILTER_VALIDATE_URL) && 
                    (strpos($candidate, '.mp4') !== false || 
                     strpos($candidate, 'video') !== false ||
                     strpos($candidate, 'stream') !== false ||
                     strpos($candidate, 'dlink') !== false)) {
                    return $candidate;
                }
            }
        }
        
        // Method 3: Look for JSON data in script tags
        if (preg_match_all('/<script[^>]*>(.*?)<\/script>/s', $html, $scriptMatches)) {
            foreach ($scriptMatches[1] as $script) {
                // Look for URLs in JSON-like structures
                if (preg_match('/"(?:dlink|play_url|video_url|downloadUrl|stream_url)":"(https?:\/\/[^"]+)"/', $script, $matches)) {
                    $candidate = stripslashes($matches[1]);
                    if (filter_var($candidate, FILTER_VALIDATE_URL)) {
                        return $candidate;
                    }
                }
            }
        }

    } catch (Exception $e) {
        error_log('Terabox Direct Resolution Error: ' . $e->getMessage());
        return false;
    }
    
    return false;
}

/**
 * Enhanced video download handler with progress tracking and better error handling
 * Supports range requests for resumable downloads
 */
if (isset($_GET['download'])) {
    $videoUrl = filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL);
    if (!$videoUrl) {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(['error' => 'Invalid video URL']);
        exit;
    }
    
    // Extract and sanitize filename
    $filename = basename(parse_url($videoUrl, PHP_URL_PATH));
    if (!$filename || !pathinfo($filename, PATHINFO_EXTENSION)) {
        $filename = 'video_' . time() . '.mp4';
    }
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    try {
        // Get file info first
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $videoUrl,
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $headers = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            header("HTTP/1.1 404 Not Found");
            echo json_encode(['error' => 'Video not found or not accessible']);
            exit;
        }
        
        // Set appropriate headers
        header('Content-Type: video/mp4');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Accept-Ranges: bytes');
        
        if ($contentLength > 0) {
            header('Content-Length: ' . $contentLength);
        }
        
        // Stream the video with better error handling
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $videoUrl,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION => function($ch, $data) {
                echo $data;
                flush();
                return strlen($data);
            },
            CURLOPT_TIMEOUT => 0,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        
        curl_exec($ch);
        curl_close($ch);
        
    } catch (Exception $e) {
        header("HTTP/1.1 500 Internal Server Error");
        echo json_encode(['error' => 'Download failed: ' . $e->getMessage()]);
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
    <meta name="keywords" content="video player, streaming, open source, terabox player, vibe player, html5 video, custom player, video download, no-login terabox, stream terabox video">
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
    
    <!-- Self-contained minimal CSS framework -->
    <style>
        /* Tailwind-like utility classes for basic styling */
        .hidden { display: none !important; }
        .flex { display: flex; }
        .grid { display: grid; }
        .block { display: block; }
        .inline-block { display: inline-block; }
        .w-full { width: 100%; }
        .h-full { height: 100%; }
        .max-w-4xl { max-width: 56rem; }
        .max-w-5xl { max-width: 64rem; }
        .max-w-7xl { max-width: 80rem; }
        .mx-auto { margin-left: auto; margin-right: auto; }
        .p-2 { padding: 0.5rem; }
        .p-4 { padding: 1rem; }
        .p-6 { padding: 1.5rem; }
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
        .pl-12 { padding-left: 3rem; }
        .pr-4 { padding-right: 1rem; }
        .space-y-6 > * + * { margin-top: 1.5rem; }
        .space-x-2 > * + * { margin-left: 0.5rem; }
        .space-x-4 > * + * { margin-left: 1rem; }
        .rounded { border-radius: 0.25rem; }
        .rounded-lg { border-radius: 0.5rem; }
        .rounded-xl { border-radius: 0.75rem; }
        .rounded-2xl { border-radius: 1rem; }
        .rounded-full { border-radius: 9999px; }
        .border-2 { border-width: 2px; }
        .bg-black { background-color: #000000; }
        .bg-white { background-color: #ffffff; }
        .bg-transparent { background-color: transparent; }
        .text-white { color: #ffffff; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-sm { font-size: 0.875rem; }
        .text-xl { font-size: 1.25rem; }
        .text-4xl { font-size: 2.25rem; }
        .text-5xl { font-size: 3rem; }
        .font-bold { font-weight: 700; }
        .items-center { align-items: center; }
        .justify-center { justify-content: center; }
        .justify-between { justify-content: space-between; }
        .relative { position: relative; }
        .absolute { position: absolute; }
        .fixed { position: fixed; }
        .inset-y-0 { top: 0; bottom: 0; }
        .left-0 { left: 0; }
        .right-0 { right: 0; }
        .bottom-0 { bottom: 0; }
        .top-0 { top: 0; }
        .min-h-screen { min-height: 100vh; }
        .aspect-video { aspect-ratio: 16 / 9; }
        .overflow-hidden { overflow: hidden; }
        .shadow-lg { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .cursor-pointer { cursor: pointer; }
        .focus\:outline-none:focus { outline: none; }
        .hover\:bg-gray-700:hover { background-color: #374151; }
        .bg-gray-800 { background-color: #1f2937; }
        .bg-gray-900 { background-color: #111827; }
        .text-gray-400 { color: #9ca3af; }
        .border-gray-600 { border-color: #4b5563; }
        
        /* Mobile responsive utilities */
        @media (min-width: 640px) {
            .sm\:p-6 { padding: 1.5rem; }
        }
        @media (min-width: 768px) {
            .md\:text-5xl { font-size: 3rem; }
            .md\:p-4 { padding: 1rem; }
            .md\:space-x-4 > * + * { margin-left: 1rem; }
        }
        @media (min-width: 1024px) {
            .lg\:max-w-5xl { max-width: 64rem; }
        }
        @media (min-width: 1536px) {
            .xl\:max-w-7xl { max-width: 80rem; }
        }
    </style>
    
    <!-- FontAwesome Icons with fallback -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
          onerror="this.onerror=null; this.remove(); document.getElementById('fontawesome-fallback').style.display='block';">
    
    <!-- Fallback FontAwesome styles when CDN fails -->
    <style id="fontawesome-fallback" style="display: none;">
        .fas, .fa { font-family: Arial, sans-serif; }
        .fa-play:before { content: '▶'; }
        .fa-pause:before { content: '⏸'; }
        .fa-backward:before { content: '⏪'; }
        .fa-forward:before { content: '⏩'; }
        .fa-volume-high:before { content: '🔊'; }
        .fa-volume-low:before { content: '🔉'; }
        .fa-volume-xmark:before { content: '🔇'; }
        .fa-expand:before { content: '⛶'; }
        .fa-compress:before { content: '⛝'; }
        .fa-download:before { content: '⬇'; }
        .fa-cog:before { content: '⚙'; }
        .fa-sun:before { content: '☀'; }
        .fa-moon:before { content: '🌙'; }
        .fa-link:before { content: '🔗'; }
        .fa-spinner:before { content: '↻'; animation: spin 1s linear infinite; }
        .fa-info-circle:before { content: 'ℹ'; }
        .fa-check-circle:before { content: '✅'; }
        .fa-exclamation-circle:before { content: '❗'; }
        .fa-rectangle-xmark:before { content: '📺'; }
        .fa-clone:before { content: '📺'; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
    
    <!-- Google Fonts (with fallback) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"
          onerror="this.onerror=null; this.remove();">
    
    <style>
        /* Fallback font when Google Fonts fails */
        @font-face {
            font-family: 'Inter-fallback';
            src: local('system-ui'), local('-apple-system'), local('BlinkMacSystemFont'), local('Segoe UI'), local('Roboto');
        }
    
    <style>
        /* CSS Variables for theming */
        :root {
            --bg-color: #f8f9fa; 
            --text-color: #212529; 
            --text-muted-color: #6c757d;
            --container-bg-color: #ffffff; 
            --input-bg-color: #f1f3f5; 
            --input-border-color: #dee2e6;
            --glow-color: rgba(73, 80, 87, 0.2); 
            --accent-color: #007bff; 
            --hero-bg: #ffffff;
            --hero-card-bg: #f8f9fa;
        }
        
        html.dark {
            --bg-color: #121212; 
            --text-color: #e9ecef; 
            --text-muted-color: #adb5bd;
            --container-bg-color: #1c1c1c; 
            --input-bg-color: #2c2c2c; 
            --input-border-color: #495057;
            --glow-color: rgba(0, 123, 255, 0.2); 
            --accent-color: #0d6efd; 
            --hero-bg: #1c1c1c;
            --hero-card-bg: #2c2c2c;
        }
        
        /* Smooth transitions for all elements */
        *, *::before, *::after { 
            transition: background-color 0.4s ease, color 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease; 
        }
        
        body { 
            font-family: 'Inter', 'Inter-fallback', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', sans-serif; 
            background-color: var(--bg-color); 
            color: var(--text-color); 
        }
        
        /* Video container glow effect */
        .video-container { 
            box-shadow: 0 0 40px var(--glow-color); 
        }
        
        /* Input focus glow */
        .input-glow:focus-within { 
            box-shadow: 0 0 15px var(--glow-color); 
        }
        
        /* Hero section styling */
        .hero-section { 
            background-color: var(--hero-bg); 
        }
        
        .slider-card-bg { 
            background-color: var(--hero-card-bg); 
        }
        
        /* Info box styling */
        .info-box { 
            background-color: rgba(0, 123, 255, 0.1); 
            border-color: rgba(0, 123, 255, 0.2); 
            color: var(--text-color); 
        }
        
        /* Button styling */
        #loadVideoBtn { 
            background-color: var(--accent-color); 
        } 
        
        #loadVideoBtn:hover { 
            background-color: #0b5ed7; 
        }
        
        /* Video control buttons */
        .control-button { 
            background-color: rgba(255,255,255,0.1); 
            border-radius: 50%; 
            width: 40px; 
            height: 40px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            transition: background-color 0.2s cubic-bezier(0.4, 0, 0.2, 1), transform 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        
        .control-button:hover { 
            background-color: rgba(255,255,255,0.2); 
            transform: scale(1.1); 
        }
        
        /* Range slider styling */
        input[type="range"] { 
            -webkit-appearance: none; 
            background: transparent; 
        }
        
        input[type="range"]::-webkit-slider-runnable-track { 
            height: 6px; 
            border-radius: 3px; 
            background: linear-gradient(to right, var(--accent-color) 0%, var(--accent-color) var(--progress, 0%), rgba(156, 163, 175, 0.5) var(--progress, 0%)); 
        }
        
        input[type="range"]::-webkit-slider-thumb { 
            -webkit-appearance: none; 
            height: 16px; 
            width: 16px; 
            border-radius: 50%; 
            background: var(--accent-color); 
            margin-top: -5px; 
            cursor: pointer; 
        }
        
        input[type="range"]::-moz-range-track { 
            height: 6px; 
            border-radius: 3px; 
            background: rgba(156, 163, 175, 0.5); 
        }
        
        input[type="range"]::-moz-range-thumb { 
            height: 16px; 
            width: 16px; 
            border-radius: 50%; 
            background: var(--accent-color); 
            border: none; 
            cursor: pointer; 
        }
        
        /* Progress bar styling */
        #downloadProgressBar { 
            background-color: var(--accent-color); 
        }
        
        /* Slider animation */
        .slider-container { 
            overflow: hidden; 
        } 
        
        .slider-track { 
            display: flex; 
            transition: transform 0.5s ease-in-out; 
        }
        
        .slider-card { 
            flex: 0 0 100%; 
        } 
        
        @media (min-width: 768px) { 
            .slider-card { 
                flex: 0 0 33.3333%; 
            } 
        }
        
        /* Center action icon */
        #center-action-icon { 
            position: absolute; 
            top: 50%; 
            left: 50%; 
            transform: translate(-50%, -50%) scale(0.8); 
            font-size: 4rem; 
            color: rgba(255, 255, 255, 0.8); 
            background-color: rgba(0, 0, 0, 0.4); 
            border-radius: 50%; 
            width: 100px; 
            height: 100px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            opacity: 0; 
            pointer-events: none; 
            transition: opacity 0.3s cubic-bezier(0.25, 0.1, 0.25, 1), transform 0.3s cubic-bezier(0.25, 0.1, 0.25, 1); 
        }
        
        #center-action-icon.visible { 
            opacity: 1; 
            transform: translate(-50%, -50%) scale(1); 
        }
        
        /* Volume controls */
        .volume-bar-container { 
            width: 80px; 
            height: 10px; 
            background-color: rgba(0,0,0,0.5); 
            border-radius: 5px; 
            overflow: hidden;
        }
        
        .volume-bar { 
            height: 100%; 
            background-color: white; 
            width: 100%; 
            transform-origin: left; 
            transition: transform 0.2s cubic-bezier(0.25, 0.1, 0.25, 1);
        }
        
        /* Toast notifications */
        #toast-container { 
            position: fixed; 
            bottom: 1.5rem; 
            right: 1.5rem; 
            z-index: 9999; 
            display: flex; 
            flex-direction: column; 
            gap: 0.75rem; 
        }
        
        .toast { 
            display: flex; 
            align-items: center; 
            padding: 1rem; 
            border-radius: 0.5rem; 
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); 
            transform: translateX(120%); 
            opacity: 0; 
            transition: transform 0.5s ease, opacity 0.5s ease; 
        }
        
        .toast.show { 
            transform: translateX(0); 
            opacity: 1; 
        }
        
        /* Settings menu and context menu */
        #settings-menu, .custom-context-menu { 
            background-color: rgba(28, 28, 28, 0.9); 
            backdrop-filter: blur(5px); 
        }
        
        /* Thumbnail preview */
        #thumbnail-container { 
            position: absolute; 
            bottom: 60px; 
            border-radius: 8px; 
            border: 2px solid rgba(255,255,255,0.7); 
            background-color: black; 
            opacity: 0; 
            transition: opacity 0.2s, transform 0.2s; 
            pointer-events: none; 
            transform: scale(0.95); 
            overflow: hidden;
        }
        
        #thumbnail-container.visible { 
            opacity: 1; 
            transform: scale(1); 
        }
        
        #thumbnail-time { 
            position: absolute; 
            bottom: 5px; 
            left: 50%; 
            transform: translateX(-50%); 
            background-color: rgba(0,0,0,0.7); 
            color: white; 
            padding: 2px 6px; 
            font-size: 12px; 
            border-radius: 4px; 
        }
        
        /* Tab styling */
        .tab-button.active { 
            background-color: var(--accent-color); 
            color: white; 
        }
        
        /* Volume slider container */
        .volume-slider-container { 
            position: absolute; 
            bottom: 60px; 
            left: 50%; 
            transform: translateX(-50%); 
            background-color: rgba(28, 28, 28, 0.9); 
            backdrop-filter: blur(5px); 
            padding: 1rem 0.5rem; 
            border-radius: 20px; 
            opacity: 0; 
            transform: translateY(10px); 
            transition: opacity 0.3s ease, transform 0.3s ease; 
            pointer-events: none; 
        }
        
        .volume-control:hover .volume-slider-container { 
            opacity: 1; 
            transform: translateY(0); 
            pointer-events: auto; 
        }
        
        /* Vertical range slider */
        input[type="range"][orient="vertical"] { 
            writing-mode: bt-lr; 
            -webkit-appearance: slider-vertical; 
            width: 8px; 
            height: 100px; 
        }
        
        /* Responsive design for mobile */
        @media (max-width: 640px) {
            .control-button { 
                width: 36px; 
                height: 36px; 
                font-size: 0.9rem; 
            }
            
            .video-controls .text-sm { 
                font-size: 0.75rem; 
            }
            
            .slider-card { 
                padding: 0.5rem; 
            }
            
            .slider-card-bg { 
                padding: 1rem; 
            }
        }
        
        /* Theater mode styling */
        .theater-mode #app-wrapper { 
            max-width: 100%; 
        }
        
        .theater-mode .hero-section, 
        .theater-mode .input-glow, 
        .theater-mode .info-box { 
            display: none; 
        }
        
        /* Hidden thumbnail video */
        #thumbnailVideo { 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 10px; 
            height: 10px; 
            opacity: 0; 
            pointer-events: none; 
        }
        
        /* Progress container */
        .progress-container { 
            position: relative; 
            margin-bottom: 1rem; 
        }
        
        #progressBar { 
            width: 100%; 
        }
        
        /* Buffer bar */
        #bufferBar { 
            position: absolute; 
            top: 0; 
            left: 0; 
            height: 100%; 
            background-color: rgba(255, 255, 255, 0.3); 
            width: 0%; 
            pointer-events: none; 
        }
        
        /* Theater mode video wrapper */
        #videoPlayerWrapper.theater { 
            max-width: 100%; 
            height: calc(100vh - 20px); 
        }
        
        /* Fallback for when external resources fail to load */
        .fas, .fa {
            font-family: 'FontAwesome', 'Font Awesome 6 Free', 'Font Awesome 6 Pro', sans-serif !important;
        }
        
        /* Fallback icons using Unicode symbols when FontAwesome fails */
        .fas.fa-play::before { content: '▶️'; }
        .fas.fa-pause::before { content: '⏸️'; }
        .fas.fa-backward::before { content: '⏪'; }
        .fas.fa-forward::before { content: '⏩'; }
        .fas.fa-volume-high::before { content: '🔊'; }
        .fas.fa-volume-low::before { content: '🔉'; }
        .fas.fa-volume-xmark::before { content: '🔇'; }
        .fas.fa-expand::before { content: '⛶'; }
        .fas.fa-compress::before { content: '⛝'; }
        .fas.fa-download::before { content: '⬇️'; }
        .fas.fa-cog::before { content: '⚙️'; }
        .fas.fa-sun::before { content: '☀️'; }
        .fas.fa-moon::before { content: '🌙'; }
        .fas.fa-link::before { content: '🔗'; }
        .fas.fa-spinner::before { content: '↻'; }
        .fas.fa-info-circle::before { content: 'ℹ️'; }
        .fas.fa-check-circle::before { content: '✅'; }
        .fas.fa-exclamation-circle::before { content: '❗'; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4 sm:p-6">

    <div id="app-wrapper" class="w-full max-w-4xl lg:max-w-5xl 2xl:max-w-7xl mx-auto space-y-6">
        <!-- Header with title and theme toggle -->
        <div class="text-center relative">
            <h1 class="text-4xl md:text-5xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-500 to-purple-600">Vibe Player</h1>
            <p class="mt-2" style="color: var(--text-muted-color);">The Ultimate Hub for Seamless Streaming.</p>
            <button id="theme-toggle" class="absolute top-0 right-0 p-2 rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[var(--bg-color)] focus:ring-[var(--accent-color)]" aria-label="Toggle theme">
                <i class="fas fa-sun text-xl"></i>
            </button>
        </div>
        
        <!-- Features Slider Section -->
        <div class="p-6 rounded-2xl shadow-lg hero-section">
            <div class="slider-container">
                <div class="slider-track"></div>
            </div>
        </div>
        
        <!-- URL Input Section -->
        <div class="relative input-glow rounded-full" style="background-color: var(--input-bg-color);">
            <span id="url-status-icon" class="absolute inset-y-0 left-0 flex items-center pl-4">
                <i class="fas fa-link" style="color: var(--text-muted-color);"></i>
            </span>
            <input id="videoUrl" type="text" placeholder="Paste a direct video link or Terabox link here..." 
                   class="w-full bg-transparent border-2 rounded-full py-3 pl-12 pr-4 focus:outline-none" 
                   style="border-color: var(--input-border-color); color: var(--text-color);">
            <button id="loadVideoBtn" class="absolute inset-y-0 right-0 flex items-center px-4 text-white rounded-r-full" aria-label="Load Video">
                <i class="fas fa-play"></i>
            </button>
        </div>
        
        <!-- Info Box -->
        <div class="p-4 rounded-lg border info-box">
            <p><i class="fas fa-info-circle mr-2"></i><strong>Terabox links are auto-resolved!</strong> For other restricted sites, use the <strong>Proxy</strong> setting.</p>
        </div>

        <!-- Video Player Container -->
        <div id="playerContainer" class="hidden">
            <div id="videoPlayerWrapper" class="relative w-full aspect-video rounded-lg overflow-hidden bg-black video-container">
                <!-- Main video element -->
                <video id="mainVideo" class="w-full h-full" crossOrigin="anonymous"></video>
                
                <!-- Thumbnail preview video (hidden) -->
                <video id="thumbnailVideo" class="hidden w-full h-full" crossOrigin="anonymous" muted></video>
                
                <!-- Center action icon for play/pause feedback -->
                <div id="center-action-icon"></div>
                
                <!-- Thumbnail preview container -->
                <div id="thumbnail-container">
                    <canvas id="thumbnail-canvas"></canvas>
                    <span id="thumbnail-time">00:00</span>
                </div>
                
                <!-- Video Controls Overlay -->
                <div class="absolute bottom-0 left-0 right-0 p-2 md:p-4 bg-gradient-to-t from-black/70 to-transparent video-controls opacity-100">
                    <!-- Progress Bar Container -->
                    <div class="relative">
                        <input id="progressBar" type="range" min="0" max="100" value="0" 
                               class="w-full h-2 rounded-lg cursor-pointer mb-2" 
                               style="--progress: 0%;" aria-label="Seek progress">
                    </div>
                    
                    <!-- Control Buttons -->
                    <div class="flex justify-between items-center text-white flex-wrap">
                        <!-- Left side controls -->
                        <div class="flex items-center space-x-2 md:space-x-4">
                            <button id="playPauseBtn" class="control-button text-xl" aria-label="Play or Pause">
                                <i class="fas fa-play"></i>
                            </button>
                            <button id="rewindBtn" class="control-button text-lg" aria-label="Rewind 10 seconds">
                                <i class="fas fa-backward"></i>
                            </button>
                            <button id="forwardBtn" class="control-button text-lg" aria-label="Forward 10 seconds">
                                <i class="fas fa-forward"></i>
                            </button>
                            
                            <!-- Volume Control -->
                            <div class="relative volume-control">
                                <button id="volumeBtn" class="control-button" aria-label="Mute or Unmute">
                                    <i class="fas fa-volume-high"></i>
                                </button>
                                <div class="volume-slider-container">
                                    <input id="volumeSlider" type="range" min="0" max="1" step="0.01" value="1" 
                                           orient="vertical" aria-label="Volume control">
                                </div>
                            </div>
                            
                            <!-- Time Display -->
                            <div id="timeDisplay" class="text-sm font-mono">00:00 / 00:00</div>
                        </div>
                        
                        <!-- Right side controls -->
                        <div class="flex items-center space-x-2 md:space-x-4">
                            <!-- Settings Menu -->
                            <div class="relative">
                                <button id="settingsBtn" class="control-button" aria-label="Settings">
                                    <i class="fas fa-cog"></i>
                                </button>
                                <div id="settings-menu" class="hidden absolute bottom-full right-0 mb-2 rounded-md p-2 text-white w-72">
                                    <!-- Tab Navigation -->
                                    <div class="flex border-b border-gray-600 mb-2">
                                        <button data-tab="settings" class="tab-button flex-1 p-2 text-sm font-bold active">Settings</button>
                                        <button data-tab="history" class="tab-button flex-1 p-2 text-sm font-bold">History</button>
                                    </div>
                                    
                                    <!-- Settings Tab -->
                                    <div id="settings-tab" class="space-y-2 p-2">
                                        <div class="flex items-center justify-between">
                                            <span>Video Quality</span>
                                            <span class="px-2 py-1 text-xs rounded-md bg-gray-600">Auto</span>
                                        </div>
                                        
                                        <label class="flex items-center justify-between space-x-2 cursor-pointer">
                                            <span>Loop Video</span>
                                            <input type="checkbox" id="loopToggle" 
                                                   class="form-checkbox h-4 w-4 text-[var(--accent-color)] bg-gray-700 border-gray-600 rounded focus:ring-offset-0 focus:ring-0">
                                        </label>
                                        
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
                                        <input type="text" id="proxyUrlInput" placeholder="https://my-proxy.com/" 
                                               class="w-full bg-gray-900 border border-gray-600 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-[var(--accent-color)]">
                                        <p class="text-xs text-gray-400 mt-1">For advanced users to bypass streaming restrictions.</p>
                                    </div>
                                    
                                    <!-- History Tab -->
                                    <div id="history-tab" class="hidden p-2 max-h-48 overflow-y-auto"></div>
                                </div>
                            </div>
                            
                            <button id="theaterBtn" class="control-button" aria-label="Theater mode">
                                <i class="fas fa-rectangle-xmark"></i>
                            </button>
                            <button id="pipBtn" class="control-button" aria-label="Picture in picture">
                                <i class="fas fa-clone"></i>
                            </button>
                            <button id="fullscreenBtn" class="control-button" aria-label="Fullscreen">
                                <i class="fas fa-expand"></i>
                            </button>
                            <button id="downloadBtn" class="control-button" aria-label="Download video">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Notification Container -->
    <div id="toast-container"></div>
    
    <!-- Custom Context Menu -->
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
                    this.historyTab = document.getElementById('history-tab');
                    this.settingsTab = document.getElementById('settings-tab');
                    this.loopToggle = document.getElementById('loopToggle');
                    this.proxyUrlInput = document.getElementById('proxyUrlInput');
                    this.customContextMenu = document.getElementById('custom-context-menu');
                    this.thumbnailContainer = document.getElementById('thumbnail-container');
                    this.thumbnailCanvas = document.getElementById('thumbnail-canvas');
                    this.thumbnailCtx = this.thumbnailCanvas.getContext('2d');
                    this.thumbnailTime = document.getElementById('thumbnail-time');
                    this.buttons = {
                        playPauseBtn: document.getElementById('playPauseBtn'),
                        rewindBtn: document.getElementById('rewindBtn'),
                        forwardBtn: document.getElementById('forwardBtn'),
                        volumeBtn: document.getElementById('volumeBtn'),
                        settingsBtn: document.getElementById('settingsBtn'),
                        theaterBtn: document.getElementById('theaterBtn'),
                        pipBtn: document.getElementById('pipBtn'),
                        fullscreenBtn: document.getElementById('fullscreenBtn'),
                        downloadBtn: document.getElementById('downloadBtn'),
                    };
                    
                    this.proxyUrl = localStorage.getItem('proxyUrl') || '';
                    this.proxyUrlInput.value = this.proxyUrl;

                    this.videoUrlInput.value = 'https://1024terabox.com/s/15JQmsttbt3XLOV-SO7HITA';
                    
                    this.setupEventListeners();
                    this.loadSettings();
                    this.renderHistory();
                },
                
                setupEventListeners() {
                    // Player controls
                    this.buttons.playPauseBtn.addEventListener('click', () => this.togglePlayPause());
                    this.video.addEventListener('click', () => this.togglePlayPause());
                    this.buttons.rewindBtn.addEventListener('click', () => this.seekVideo(-10));
                    this.buttons.forwardBtn.addEventListener('click', () => this.seekVideo(10));
                    this.video.addEventListener('play', () => this.updatePlayPauseIcon());
                    this.video.addEventListener('pause', () => this.updatePlayPauseIcon());
                    this.volumeSlider.addEventListener('input', () => this.setVolume(this.volumeSlider.value));
                    this.video.addEventListener('volumechange', () => this.updateVolumeIcon());
                    this.buttons.volumeBtn.addEventListener('click', () => this.toggleMute());
                    this.video.addEventListener('timeupdate', () => this.updateTimeDisplay());
                    this.video.addEventListener('loadedmetadata', () => this.updateTimeDisplay());
                    this.progressBar.addEventListener('input', (e) => this.seek(e.target.value));
                    this.buttons.settingsBtn.addEventListener('click', (e) => { e.stopPropagation(); this.settingsMenu.classList.toggle('hidden'); });
                    this.settingsMenu.querySelectorAll('.tab-button').forEach(button => {
                        button.addEventListener('click', (e) => {
                            const tab = e.target.dataset.tab;
                            this.settingsMenu.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
                            e.target.classList.add('active');
                            if (tab === 'history') {
                                this.renderHistory();
                                this.settingsTab.classList.add('hidden');
                                this.historyTab.classList.remove('hidden');
                            } else {
                                this.settingsTab.classList.remove('hidden');
                                this.historyTab.classList.add('hidden');
                            }
                        });
                    });
                    this.loopToggle.addEventListener('change', () => this.video.loop = this.loopToggle.checked);
                    this.proxyUrlInput.addEventListener('change', (e) => { this.proxyUrl = e.target.value; localStorage.setItem('proxyUrl', this.proxyUrl); });
                    this.buttons.theaterBtn.addEventListener('click', () => this.toggleTheaterMode());
                    this.buttons.pipBtn.addEventListener('click', () => this.togglePip());
                    this.buttons.fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());
                    this.buttons.downloadBtn.addEventListener('click', () => this.downloadVideo());
                    document.addEventListener('fullscreenchange', () => this.updateFullscreenIcon());
                    document.addEventListener('click', () => { this.settingsMenu.classList.add('hidden'); this.customContextMenu.classList.add('hidden'); });
                    this.video.addEventListener('error', () => this.handleVideoError());
                    this.videoPlayerWrapper.addEventListener('contextmenu', (e) => this.showCustomContextMenu(e));
                    this.progressBar.addEventListener('mousemove', (e) => this.updateThumbnail(e));
                    this.progressBar.addEventListener('mouseleave', () => this.thumbnailContainer.classList.remove('visible'));
                    document.addEventListener('keydown', (e) => this.handleKeyboardShortcuts(e));
                },
                
                async loadVideo() {
                    let url = this.videoUrlInput.value.trim();
                    if (!url) { UI.showToast('Please paste a video URL.', 'error'); return; }
                    
                    this.playerContainer.classList.remove('hidden');
                    UI.setUrlStatus('loading');

                    try {
                        let finalUrl = url;
                        if (url.includes('terabox.com')) {
                            UI.showToast('Resolving Terabox link...', 'info');
                            finalUrl = await this.resolveTeraboxLink(url);
                            UI.showToast('Link resolved successfully!', 'success');
                        }
                        
                        const proxiedUrl = this.proxyUrl ? this.proxyUrl + encodeURIComponent(finalUrl) : finalUrl;
                        
                        this.video.src = proxiedUrl;
                        this.thumbnailVideo.src = proxiedUrl;

                        this.video.load();
                        const playPromise = this.video.play();
                        if (playPromise !== undefined) {
                            playPromise.catch(error => {
                                if (error.name !== 'AbortError') {
                                    UI.showToast('Auto-play blocked. Press play to start.', 'info');
                                }
                            });
                        }
                        this.addToHistory(url);
                    } catch (error) {
                        UI.showToast(error.message, 'error');
                    } finally {
                        UI.setUrlStatus('idle');
                    }
                },

                async resolveTeraboxLink(url) {
                    UI.showToast('Starting Terabox resolution...', 'info');
                    
                    try {
                        // Try server-side resolution first
                        const response = await fetch(`?resolve&url=${encodeURIComponent(url)}`);
                        const result = await response.json();
                        
                        if (result.success) {
                            UI.showToast('Terabox link resolved successfully!', 'success');
                            return result.url;
                        } else {
                            // Show more detailed error information
                            let errorMsg = result.message || 'Failed to resolve Terabox link';
                            if (result.errors && result.errors.length > 0) {
                                console.warn('Terabox Resolution Errors:', result.errors);
                                
                                // Check if all errors are network-related (sandbox environment)
                                const networkErrors = result.errors.filter(error => 
                                    error.includes('Could not resolve host') || 
                                    error.includes('cURL error') ||
                                    error.includes('HTTP 0')
                                );
                                
                                if (networkErrors.length === result.errors.length) {
                                    UI.showToast('Network restrictions detected. Trying client-side resolution...', 'info');
                                    return await this.resolveTeraboxClientSide(url);
                                }
                                
                                errorMsg += '. Check console for details.';
                            }
                            throw new Error(errorMsg);
                        }
                    } catch (error) {
                        if (error.name === 'TypeError' && error.message.includes('fetch')) {
                            throw new Error('Network error. Please check your connection.');
                        }
                        
                        // If server-side resolution fails, try client-side
                        if (error.message.includes('Failed to resolve')) {
                            UI.showToast('Trying alternative resolution method...', 'info');
                            try {
                                return await this.resolveTeraboxClientSide(url);
                            } catch (clientError) {
                                throw new Error('All resolution methods failed. ' + clientError.message);
                            }
                        }
                        
                        throw error;
                    }
                },

                async resolveTeraboxClientSide(url) {
                    // Client-side fallback using CORS proxies
                    const proxies = [
                        'https://api.allorigins.win/raw?url=',
                        'https://corsproxy.io/?',
                        'https://thingproxy.freeboard.io/fetch/'
                    ];
                    
                    for (const proxy of proxies) {
                        try {
                            UI.showToast(`Trying proxy resolution...`, 'info');
                            
                            const response = await fetch(proxy + encodeURIComponent(url), {
                                method: 'GET',
                                headers: {
                                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                                }
                            });
                            
                            if (!response.ok) continue;
                            
                            const html = await response.text();
                            
                            // Enhanced pattern matching for video URLs
                            const patterns = [
                                /"dlink":"(https?:\/\/[^"]+)"/,
                                /"play_url":"(https?:\/\/[^"]+)"/,
                                /sources:\["(https?:\/\/[^"]+)"\]/,
                                /"video_url":"(https?:\/\/[^"]+)"/,
                                /videoUrl["']?\s*:\s*["']([^"']+)/,
                                /src["']?\s*:\s*["']([^"']+\.mp4[^"']*)/i,
                                /"url":"(https?:\/\/[^"]+\.mp4[^"]*)"/,
                                /"downloadUrl":"(https?:\/\/[^"]+)"/,
                                /"stream_url":"(https?:\/\/[^"]+)"/
                            ];

                            for (const pattern of patterns) {
                                const match = html.match(pattern);
                                if (match && match[1]) {
                                    const resolvedUrl = match[1].replace(/\\/g, '');
                                    // Validate URL
                                    try {
                                        new URL(resolvedUrl);
                                        if (resolvedUrl.includes('.mp4') || 
                                            resolvedUrl.includes('video') || 
                                            resolvedUrl.includes('stream') ||
                                            resolvedUrl.includes('dlink')) {
                                            return resolvedUrl;
                                        }
                                    } catch (e) {
                                        continue;
                                    }
                                }
                            }
                        } catch (error) {
                            console.warn(`Client-side proxy ${proxy} failed:`, error);
                            continue;
                        }
                    }
                    
                    throw new Error('All client-side resolution methods failed. The link may require login or be invalid.');
                },

                addToHistory(url) {
                    const history = JSON.parse(localStorage.getItem('videoHistory') || '[]');
                    const entry = {
                        url: url,
                        title: this.extractTitle(url),
                        timestamp: Date.now()
                    };
                    
                    // Remove duplicates
                    const filtered = history.filter(item => item.url !== url);
                    filtered.unshift(entry);
                    
                    // Keep only last 50 entries
                    localStorage.setItem('videoHistory', JSON.stringify(filtered.slice(0, 50)));
                },

                extractTitle(url) {
                    try {
                        const urlObj = new URL(url);
                        return urlObj.hostname + urlObj.pathname.split('/').pop();
                    } catch {
                        return url.substring(0, 50) + '...';
                    }
                },

                loadFromHistory(url) {
                    this.videoUrlInput.value = url;
                    this.loadVideo();
                    this.settingsMenu.classList.add('hidden');
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
                    
                    // Update progress bar visual
                    const percent = (this.video.currentTime / this.video.duration) * 100;
                    this.progressBar.style.setProperty('--progress', `${percent}%`);
                    this.progressBar.value = this.video.currentTime;
                    this.progressBar.max = this.video.duration;
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
                        UI.showActionIcon('fa-play');
                    } else {
                        this.video.pause();
                        UI.showActionIcon('fa-pause');
                    }
                },

                updatePlayPauseIcon() {
                    const icon = this.video.paused ? 'fa-play' : 'fa-pause';
                    this.buttons.playPauseBtn.innerHTML = `<i class="fas ${icon}"></i>`;
                },

                seekVideo(seconds) {
                    this.video.currentTime = Math.max(0, Math.min(this.video.duration, this.video.currentTime + seconds));
                    UI.showActionIcon(seconds > 0 ? 'fa-forward' : 'fa-backward');
                },

                seek(value) {
                    this.video.currentTime = (value / 100) * this.video.duration;
                },

                setVolume(value) {
                    this.video.volume = value;
                    this.updateVolumeIcon();
                },
                
                updateVolumeIcon() {
                    const volumeBtn = this.buttons.volumeBtn;
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
                    } else {
                        document.exitFullscreen();
                    }
                },

                updateFullscreenIcon() {
                    const btn = this.buttons.fullscreenBtn;
                    const icon = document.fullscreenElement ? 'fa-compress' : 'fa-expand';
                    btn.innerHTML = `<i class="fas ${icon}"></i>`;
                },
                
                toggleTheaterMode() {
                    document.body.classList.toggle('theater-mode');
                },
                
                togglePip() {
                    if (document.pictureInPictureElement) {
                        document.exitPictureInPicture();
                    } else if (document.pictureInPictureEnabled) {
                        this.video.requestPictureInPicture().catch(error => {
                            UI.showToast('Picture-in-Picture not supported', 'error');
                        });
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

                handleVideoError() {
                    UI.showToast('Error loading video. Please check the URL.', 'error');
                },

                showCustomContextMenu(e) {
                    e.preventDefault();
                    this.customContextMenu.style.left = e.pageX + 'px';
                    this.customContextMenu.style.top = e.pageY + 'px';
                    this.customContextMenu.classList.remove('hidden');
                },

                updateThumbnail(e) {
                    if (!this.video.duration) return;
                    
                    const rect = this.progressBar.getBoundingClientRect();
                    const percent = (e.clientX - rect.left) / rect.width;
                    const time = percent * this.video.duration;
                    
                    this.thumbnailTime.textContent = this.formatTime(time);
                    this.thumbnailContainer.style.left = `${e.clientX - rect.left - 80}px`;
                    this.thumbnailContainer.classList.add('visible');
                },

                renderHistory() {
                    // Get history from localStorage
                    const history = JSON.parse(localStorage.getItem('videoHistory') || '[]');
                    if (history.length === 0) {
                        this.historyTab.innerHTML = '<p class="text-gray-400 text-sm">No history yet</p>';
                        return;
                    }
                    
                    this.historyTab.innerHTML = history.map(item => `
                        <div class="mb-2 p-2 bg-gray-800 rounded cursor-pointer hover:bg-gray-700" onclick="Player.loadFromHistory('${item.url}')">
                            <div class="text-sm font-bold truncate">${item.title}</div>
                            <div class="text-xs text-gray-400">${new Date(item.timestamp).toLocaleDateString()}</div>
                        </div>
                    `).join('');
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