<?php
/**
 * VibePlayer - Advanced Terabox Link Resolver
 * 
 * This script provides multiple fallback methods for resolving Terabox links
 * to direct video URLs without requiring user login.
 */

// Terabox link resolver with enhanced multiple proxy options and direct API calls
if (isset($_GET['resolve']) || ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false)) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    // Handle POST requests with JSON body
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $url = $input['link'] ?? $input['url'] ?? null;
    } else {
        $url = filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL);
    }
    
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
        $result = resolveTeraboxDirect($url);
        if ($result['success']) {
            echo json_encode($result);
            exit;
        }
        $errors[] = "Direct method: " . ($result['error'] ?? 'No video URL found in page content');
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
 * Enhanced Direct Terabox resolution method following the exact approach from problem statement
 * Uses server-side direct API calls to TeraBox's share APIs for maximum reliability
 */
function resolveTeraboxDirect($url) {
    try {
        // Step 1: Extract share ID and domain from URL
        $parsedUrl = parse_url($url);
        $domain = $parsedUrl['host'] ?? 'terabox.com';
        
        // Extract surl/share ID from URL - multiple patterns supported
        $surl = '';
        if (preg_match('/\/s\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $surl = $matches[1];
        } elseif (preg_match('/surl=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $surl = $matches[1];
        } elseif (preg_match('/\/sharing\/link\?surl=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $surl = $matches[1];
        }
        
        if (!$surl) {
            throw new Exception("Could not extract share ID from TeraBox URL");
        }

        // Step 2: Try direct API call first (as mentioned in problem statement)
        $apiUrl = "https://www.terabox.com/share/list";
        $params = [
            'app_id' => '250528', // Known app_id from TeraBox's open-platform integration
            'shorturl' => $surl,
            'root' => '1'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl . '?' . http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json, text/plain, */*',
                'Accept-Language: en-US,en;q=0.9',
                'Referer: https://terabox.com/sharing/link?surl=' . $surl,
                'DNT: 1',
                'Connection: keep-alive',
                'Cache-Control: no-cache'
            ]
        ]);
        
        $apiResponse = curl_exec($ch);
        $apiHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // If direct API call succeeds, parse response
        if ($apiHttpCode === 200 && $apiResponse && !$curlError) {
            $apiData = json_decode($apiResponse, true);
            if ($apiData && isset($apiData['list']) && !empty($apiData['list'])) {
                $file = $apiData['list'][0];
                if (isset($file['dlink']) && !empty($file['dlink'])) {
                    return [
                        'success' => true,
                        'url' => $file['dlink'],
                        'file_name' => $file['server_filename'] ?? 'video.mp4',
                        'thumbnail' => $file['thumbs']['url3'] ?? '',
                        'size' => $file['size'] ?? 0,
                        'method' => 'direct_api'
                    ];
                }
            }
        }

        // Step 3: Fallback to page scraping with enhanced token extraction
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
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
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        
        if (curl_errno($ch)) {
            throw new Exception('cURL Error: ' . curl_error($ch));
        }
        curl_close($ch);

        if ($httpCode !== 200 || empty($html)) {
            throw new Exception('Failed to fetch Terabox page. HTTP Code: ' . $httpCode);
        }

        // Step 4: Enhanced token extraction with multiple patterns
        $jsToken = null;
        $logid = null;
        $bdstoken = null;
        
        // Try multiple extraction patterns for jsToken
        $tokenPatterns = [
            '/"jsToken":"([^"]+)"/',
            "/'jsToken':'([^']+)'/",
            '/jsToken["\']?\s*:\s*["\']([^"\']+)/',
            '/window\.jsToken\s*=\s*["\']([^"\']+)/'
        ];
        
        foreach ($tokenPatterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $jsToken = $matches[1];
                break;
            }
        }
        
        // Try multiple extraction patterns for logid
        $logidPatterns = [
            '/"logid":"([^"]+)"/',
            "/'logid':'([^']+)'/",
            '/logid["\']?\s*:\s*["\']([^"\']+)/',
            '/window\.logid\s*=\s*["\']([^"\']+)/'
        ];
        
        foreach ($logidPatterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $logid = $matches[1];
                break;
            }
        }
        
        // Try multiple extraction patterns for bdstoken
        $bdstokenPatterns = [
            '/"bdstoken":"([^"]+)"/',
            "/'bdstoken':'([^']+)'/",
            '/bdstoken["\']?\s*:\s*["\']([^"\']+)/',
            '/window\.bdstoken\s*=\s*["\']([^"\']+)/'
        ];
        
        foreach ($bdstokenPatterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $bdstoken = $matches[1];
                break;
            }
        }

        // Step 5: Try window.__INIT_DATA__ extraction (most reliable method)
        $initDataPatterns = [
            '/<script>window\.__INIT_DATA__\s*=\s*({.*?})<\/script>/',
            '/window\.__INIT_DATA__\s*=\s*({.*?});/',
            '/__INIT_DATA__\s*=\s*({.*?})/'
        ];
        
        foreach ($initDataPatterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $data = json_decode($matches[1], true);
                if ($data && isset($data['share_info']['file_list']) && !empty($data['share_info']['file_list'])) {
                    $fileList = $data['share_info']['file_list'];
                    $dlink = $fileList[0]['dlink'] ?? null;
                    
                    if ($dlink) {
                        return [
                            'success' => true,
                            'url' => $dlink,
                            'file_name' => $fileList[0]['server_filename'] ?? 'video.mp4',
                            'thumbnail' => $fileList[0]['thumbs']['url3'] ?? '',
                            'size' => $fileList[0]['size'] ?? 0,
                            'method' => 'init_data'
                        ];
                    }
                }
                break;
            }
        }

        // Step 6: If tokens found, make enhanced API call
        if ($jsToken && $logid && $bdstoken) {
            // Try multiple API endpoints
            $apiEndpoints = [
                "https://www.terabox.com/share/list",
                "https://dm.terabox.app/share/list",
                "https://terabox.com/share/list"
            ];
            
            foreach ($apiEndpoints as $apiUrl) {
                $params = [
                    'app_id' => '250528',
                    'jsToken' => $jsToken,
                    'logid' => $logid,
                    'bdstoken' => $bdstoken,
                    'shorturl' => $surl,
                    'root' => '1'
                ];
                
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $apiUrl . '?' . http_build_query($params),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json, text/plain, */*',
                        'Accept-Language: en-US,en;q=0.9',
                        'Referer: https://www.terabox.com/sharing/link?surl=' . $surl,
                        'DNT: 1',
                        'Connection: keep-alive'
                    ]
                ]);
                
                $apiResponse = curl_exec($ch);
                $apiHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($apiHttpCode === 200 && $apiResponse) {
                    $apiData = json_decode($apiResponse, true);
                    if ($apiData && isset($apiData['list']) && !empty($apiData['list'])) {
                        $file = $apiData['list'][0];
                        if (isset($file['dlink']) && !empty($file['dlink'])) {
                            return [
                                'success' => true,
                                'url' => $file['dlink'],
                                'file_name' => $file['server_filename'] ?? 'video.mp4',
                                'thumbnail' => $file['thumbs']['url3'] ?? '',
                                'size' => $file['size'] ?? 0,
                                'method' => 'api_with_tokens'
                            ];
                        }
                    }
                }
            }
        }

        // Step 7: Enhanced fallback patterns with more comprehensive extraction
        $enhancedPatterns = [
            // Direct link patterns
            '/"dlink":"(https?:\/\/[^"]+)"/',
            '/"play_url":"(https?:\/\/[^"]+)"/',
            '/"download_url":"(https?:\/\/[^"]+)"/',
            '/"stream_url":"(https?:\/\/[^"]+)"/',
            
            // Video source patterns
            '/sources:\s*\[\s*"(https?:\/\/[^"]+)"\s*\]/',
            '/"video_url":"(https?:\/\/[^"]+)"/',
            '/videoUrl["\']?\s*:\s*["\']([^"\']+)/',
            '/src["\']?\s*:\s*["\']([^"\']+\.(?:mp4|webm|avi|mov|mkv)[^"\']*)/i',
            '/"url":"(https?:\/\/[^"]+\.(?:mp4|webm|avi|mov|mkv)[^"]*)"/',
            
            // Terabox specific patterns
            '/data-src="(https?:\/\/[^"]+\.(?:mp4|webm|avi|mov|mkv)[^"]*)"/',
            '/href="(https?:\/\/[^"]+\.(?:mp4|webm|avi|mov|mkv)[^"]*)"/',
            '/<source[^>]+src="([^"]+\.(?:mp4|webm|avi|mov|mkv)[^"]*)"[^>]*>/i',
            
            // JSON embedded patterns
            '/file_url["\']?\s*:\s*["\']([^"\']+)/',
            '/media_url["\']?\s*:\s*["\']([^"\']+)/',
            '/direct_link["\']?\s*:\s*["\']([^"\']+)/'
        ];
        
        foreach ($enhancedPatterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $candidate = stripslashes($matches[1]);
                
                // Enhanced URL validation
                if (filter_var($candidate, FILTER_VALIDATE_URL)) {
                    // Check if it's a valid video URL
                    $isVideoUrl = (
                        strpos($candidate, '.mp4') !== false ||
                        strpos($candidate, '.webm') !== false ||
                        strpos($candidate, '.avi') !== false ||
                        strpos($candidate, '.mov') !== false ||
                        strpos($candidate, '.mkv') !== false ||
                        strpos($candidate, 'video') !== false ||
                        strpos($candidate, 'stream') !== false ||
                        strpos($candidate, 'dlink') !== false ||
                        strpos($candidate, 'download') !== false ||
                        preg_match('/\.(mp4|webm|avi|mov|mkv)(\?|$)/i', $candidate)
                    );
                    
                    if ($isVideoUrl) {
                        return [
                            'success' => true, 
                            'url' => $candidate,
                            'method' => 'regex_extraction'
                        ];
                    }
                }
            }
        }

        // Step 8: Look for JSON data in all script tags with enhanced parsing
        if (preg_match_all('/<script[^>]*>(.*?)<\/script>/s', $html, $scriptMatches)) {
            foreach ($scriptMatches[1] as $script) {
                // Look for various JSON structures that might contain video URLs
                $jsonPatterns = [
                    '/"(?:dlink|play_url|video_url|downloadUrl|stream_url|file_url|media_url)":"(https?:\/\/[^"]+)"/',
                    '/(?:dlink|play_url|video_url|downloadUrl|stream_url|file_url|media_url):\s*"(https?:\/\/[^"]+)"/',
                    '/"url":"(https?:\/\/[^"]+\.(?:mp4|webm|avi|mov|mkv)[^"]*)"/',
                    '/src:\s*"(https?:\/\/[^"]+\.(?:mp4|webm|avi|mov|mkv)[^"]*)"/'
                ];
                
                foreach ($jsonPatterns as $pattern) {
                    if (preg_match($pattern, $script, $matches)) {
                        $candidate = stripslashes($matches[1]);
                        if (filter_var($candidate, FILTER_VALIDATE_URL)) {
                            return [
                                'success' => true, 
                                'url' => $candidate,
                                'method' => 'script_extraction'
                            ];
                        }
                    }
                }
            }
        }

        return ['success' => false, 'error' => 'No video URL found using any extraction method'];

    } catch (Exception $e) {
        error_log('Enhanced Terabox Resolution Error: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Helper function to find text between two strings
 * @param string $string The string to search in
 * @param string $start The starting delimiter
 * @param string $end The ending delimiter
 * @return string|null The text between delimiters or null if not found
 */
function findBetween($string, $start, $end) {
    $startPos = strpos($string, $start);
    if ($startPos === false) {
        return null;
    }
    
    $startPos += strlen($start);
    $endPos = strpos($string, $end, $startPos);
    
    if ($endPos === false) {
        return null;
    }
    
    return substr($string, $startPos, $endPos - $startPos);
}

/**
 * Enhanced video download handler with progress tracking and better error handling
 * Supports range requests for resumable downloads and improved CORS handling
 */
if (isset($_GET['download'])) {
    // Add CORS headers for download endpoint
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Range');
    
    $videoUrl = filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL);
    if (!$videoUrl) {
        header("HTTP/1.1 400 Bad Request");
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid video URL']);
        exit;
    }
    
    // Extract and sanitize filename
    $filename = basename(parse_url($videoUrl, PHP_URL_PATH));
    if (!$filename || !pathinfo($filename, PATHINFO_EXTENSION)) {
        $filename = 'video_' . time() . '.mp4';
    }
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    // Enhanced headers for better download support
    $DL_HEADERS = [
        'Accept: */*',
        'Accept-Language: en-US,en;q=0.9',
        'Accept-Encoding: identity;q=1, *;q=0',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
        'Connection: keep-alive',
        'Referer: https://www.terabox.com/',
        'DNT: 1'
    ];
    
    try {
        // Get file info first with enhanced headers
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $videoUrl,
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $DL_HEADERS
        ]);
        
        $headers = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            header("HTTP/1.1 404 Not Found");
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Video not found or not accessible']);
            exit;
        }
        
        // Handle range requests for resumable downloads
        $range = $_SERVER['HTTP_RANGE'] ?? null;
        $start = 0;
        $end = $contentLength - 1;
        
        if ($range) {
            if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
                $start = intval($matches[1]);
                if (!empty($matches[2])) {
                    $end = intval($matches[2]);
                }
            }
        }
        
        // Set appropriate headers with CORS support
        if ($range) {
            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes $start-$end/$contentLength");
        } else {
            header('HTTP/1.1 200 OK');
        }
        
        header('Content-Type: video/mp4');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . ($end - $start + 1));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        // Stream the video with better error handling and range support
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
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => array_merge($DL_HEADERS, $range ? ["Range: bytes=$start-$end"] : [])
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
    
    <!-- Self-hosted icon styles - always available -->
    <style>
        /* Icon font fallback using Unicode symbols */
        .fas, .fa { 
            font-family: Arial, sans-serif !important;
            font-style: normal;
            font-weight: normal;
            display: inline-block;
            text-decoration: none;
        }
        
        /* Media player icons */
        .fa-play:before { content: '▶'; }
        .fa-pause:before { content: '⏸'; }
        .fa-backward:before { content: '⏪'; }
        .fa-forward:before { content: '⏩'; }
        .fa-volume-high:before { content: '🔊'; }
        .fa-volume-low:before { content: '🔉'; }
        .fa-volume-xmark:before { content: '🔇'; }
        .fa-expand:before { content: '⛶'; }
        .fa-compress:before { content: '⛝'; }
        
        /* UI icons */
        .fa-download:before { content: '⬇'; }
        .fa-cog:before { content: '⚙'; }
        .fa-sun:before { content: '☀'; }
        .fa-moon:before { content: '🌙'; }
        .fa-link:before { content: '🔗'; }
        .fa-info-circle:before { content: 'ℹ'; }
        .fa-check-circle:before { content: '✅'; }
        .fa-exclamation-circle:before { content: '⚠'; }
        .fa-times-circle:before { content: '❌'; }
        .fa-rectangle-xmark:before { content: '📺'; }
        .fa-clone:before { content: '🖼'; }
        
        /* Feature icons */
        .fa-server:before { content: '🖥'; }
        .fa-keyboard:before { content: '⌨'; }
        .fa-palette:before { content: '🎨'; }
        .fa-expand-arrows-alt:before { content: '↔'; }
        
        /* Spinner animation */
        .fa-spinner:before { content: '↻'; }
        .fa-spinner { animation: spin 1s linear infinite; }
        @keyframes spin { 
            0% { transform: rotate(0deg); } 
            100% { transform: rotate(360deg); } 
        }
        
        /* Enhanced icon styling */
        .fas, .fa {
            line-height: 1;
            vertical-align: baseline;
        }
    </style>
    
    <!-- System fonts with fallbacks -->
    <style>
        /* Font family with comprehensive fallbacks */
        body, input, button, select, textarea { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Open Sans', 'Helvetica Neue', sans-serif;
        }
    </style>
    
    <style>
        /* CSS Variables for theming - Enhanced Gen-Alpha palette */
        :root {
            --bg-color: #f8f9fa; 
            --text-color: #212529; 
            --text-muted-color: #6c757d;
            --container-bg-color: rgba(255, 255, 255, 0.95); 
            --input-bg-color: rgba(241, 243, 245, 0.8); 
            --input-border-color: #dee2e6;
            --glow-color: rgba(73, 80, 87, 0.2); 
            --accent-color: #007bff; 
            --hero-bg: linear-gradient(135deg, #FF9A8B 0%, #FFD66B 50%, #FF99AC 100%);
            --hero-card-bg: rgba(255, 255, 255, 0.25);
            --primary-gradient: linear-gradient(135deg, #FF3CAC 0%, #784BA0 50%, #2B86C5 100%);
            --secondary-gradient: linear-gradient(135deg, #FF9A8B 0%, #FFD66B 50%, #FF99AC 100%);
            --glassmorphism-bg: rgba(255, 255, 255, 0.25);
            --glassmorphism-border: rgba(255, 255, 255, 0.18);
            
            /* Z-index layers */
            --z-video-controls: 100;
            --z-center-action: 700;
            --z-volume-slider: 800;
            --z-thumbnail: 900;
            --z-settings-menu: 1000;
            --z-context-menu: 1001;
            --z-toast: 10000;
        }
        
        html.dark {
            --bg-color: #0f0f23; 
            --text-color: #e9ecef; 
            --text-muted-color: #adb5bd;
            --container-bg-color: rgba(28, 28, 28, 0.95); 
            --input-bg-color: rgba(44, 44, 44, 0.8); 
            --input-border-color: #495057;
            --glow-color: rgba(255, 60, 172, 0.3); 
            --accent-color: #FF3CAC; 
            --hero-bg: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            --hero-card-bg: rgba(255, 255, 255, 0.1);
            --primary-gradient: linear-gradient(135deg, #FF3CAC 0%, #784BA0 50%, #2B86C5 100%);
            --secondary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            --glassmorphism-bg: rgba(255, 255, 255, 0.1);
            --glassmorphism-border: rgba(255, 255, 255, 0.2);
        }
        
        /* Smooth transitions for all elements */
        *, *::before, *::after { 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Open Sans', 'Helvetica Neue', sans-serif;
            background: var(--bg-color); 
            color: var(--text-color); 
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        /* Enhanced glassmorphism effects */
        .glassmorphism {
            background: var(--glassmorphism-bg);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid var(--glassmorphism-border);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }
        
        /* Video container with enhanced glow effect */
        .video-container { 
            box-shadow: 0 0 60px var(--glow-color), 0 0 120px rgba(255, 60, 172, 0.2); 
            border-radius: 20px;
            overflow: hidden;
        }
        
        /* Input with enhanced glassmorphism and glow */
        .input-glow { 
            background: var(--glassmorphism-bg);
            backdrop-filter: blur(15px) saturate(150%);
            -webkit-backdrop-filter: blur(15px) saturate(150%);
            border: 2px solid var(--glassmorphism-border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .input-glow:focus-within { 
            box-shadow: 0 0 30px var(--glow-color), 0 0 60px rgba(255, 60, 172, 0.3);
            border-color: var(--accent-color);
            transform: translateY(-2px);
        }
        
        /* Hero section with dynamic gradient */
        .hero-section { 
            background: var(--hero-bg);
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--secondary-gradient);
            opacity: 0.1;
            z-index: 0;
        }
        
        .hero-section > * {
            position: relative;
            z-index: 1;
        }
        
        /* Enhanced slider card with glassmorphism */
        .slider-card-bg { 
            background: var(--glassmorphism-bg);
            backdrop-filter: blur(15px) saturate(150%);
            -webkit-backdrop-filter: blur(15px) saturate(150%);
            border: 1px solid var(--glassmorphism-border);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            min-height: 120px;
            position: relative;
            overflow: hidden;
        }
        
        .slider-card-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--primary-gradient);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 0;
        }
        
        .slider-card-bg:hover::before {
            opacity: 0.1;
        }
        
        .slider-card-bg:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(255, 60, 172, 0.2), 0 0 30px var(--glow-color);
            border-color: var(--accent-color);
        }
        
        .slider-card-bg > * {
            position: relative;
            z-index: 1;
        }
        
        /* Info box with glassmorphism */
        .info-box { 
            background: var(--glassmorphism-bg);
            backdrop-filter: blur(15px) saturate(150%);
            -webkit-backdrop-filter: blur(15px) saturate(150%);
            border: 1px solid rgba(0, 123, 255, 0.3);
            color: var(--text-color);
            position: relative;
            overflow: hidden;
        }
        
        .info-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(0, 123, 255, 0.1), rgba(255, 60, 172, 0.1));
            z-index: 0;
        }
        
        .info-box > * {
            position: relative;
            z-index: 1;
        }
        
        /* Enhanced gradient button styling */
        #loadVideoBtn { 
            background: var(--primary-gradient);
            border: none;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        } 
        
        #loadVideoBtn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }
        
        #loadVideoBtn:hover::before {
            left: 100%;
        }
        
        #loadVideoBtn:hover { 
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 10px 25px rgba(255, 60, 172, 0.4);
        }
        
        #loadVideoBtn:active {
            transform: translateY(0) scale(1);
        }
        
        /* Enhanced video control buttons with YouTube-style design */
        .control-button { 
            background-color: rgba(255,255,255,0.1); 
            border-radius: 50%; 
            width: 40px; 
            height: 40px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
            border: none;
            color: white;
            cursor: pointer;
        }
        
        .control-button:hover { 
            background-color: rgba(255,255,255,0.2); 
            transform: scale(1.1); 
        }
        
        .control-button-enhanced {
            background-color: rgba(255,255,255,0.15); 
            border-radius: 50%; 
            width: 48px; 
            height: 48px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
            border: none;
            color: white;
            cursor: pointer;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .control-button-enhanced:hover { 
            background-color: rgba(255,255,255,0.25); 
            transform: scale(1.15); 
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        /* Enhanced progress bar with YouTube-style design */
        .progress-bar-enhanced {
            -webkit-appearance: none; 
            background: transparent; 
            cursor: pointer;
            height: 4px !important;
        }
        
        .progress-bar-enhanced::-webkit-slider-runnable-track { 
            height: 4px; 
            border-radius: 2px; 
            background: linear-gradient(to right, var(--accent-color) 0%, var(--accent-color) var(--progress, 0%), rgba(255, 255, 255, 0.3) var(--progress, 0%)); 
            transition: height 0.2s ease;
        }
        
        .progress-bar-enhanced:hover::-webkit-slider-runnable-track { 
            height: 6px; 
        }
        
        .progress-bar-enhanced::-webkit-slider-thumb { 
            -webkit-appearance: none; 
            height: 14px; 
            width: 14px; 
            border-radius: 50%; 
            background: var(--accent-color); 
            margin-top: -5px; 
            cursor: pointer; 
            opacity: 0;
            transition: opacity 0.2s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        
        .progress-bar-enhanced:hover::-webkit-slider-thumb { 
            opacity: 1; 
        }
        
        /* Enhanced volume controls with YouTube-style hover */
        .volume-control-enhanced { 
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .volume-slider-container-enhanced { 
            width: 0; 
            overflow: hidden;
            transition: width 0.3s ease; 
            margin-left: 8px;
        }
        
        .volume-control-enhanced:hover .volume-slider-container-enhanced { 
            width: 60px; 
        }
        
        .volume-slider-enhanced {
            -webkit-appearance: none; 
            background: transparent; 
            height: 4px;
            cursor: pointer;
        }
        
        .volume-slider-enhanced::-webkit-slider-runnable-track { 
            height: 4px; 
            border-radius: 2px; 
            background: rgba(255, 255, 255, 0.3); 
        }
        
        .volume-slider-enhanced::-webkit-slider-thumb { 
            -webkit-appearance: none; 
            height: 12px; 
            width: 12px; 
            border-radius: 50%; 
            background: white; 
            margin-top: -4px; 
            cursor: pointer; 
        }
        
        /* Mini-player functionality */
        .mini-player {
            position: fixed !important;
            bottom: 20px !important;
            right: 20px !important;
            width: 320px !important;
            height: 180px !important;
            z-index: 9999 !important;
            border-radius: 12px !important;
            overflow: hidden !important;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        
        .mini-player:hover {
            transform: scale(1.05) !important;
        }
        
        .mini-player .video-controls {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .mini-player:hover .video-controls {
            opacity: 1;
        }
        
        .mini-player-close {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(0,0,0,0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10001;
            font-size: 12px;
        }
        
        /* Quality selector styling */
        .quality-selector:hover #quality-menu {
            display: block;
        }
        
        #quality-menu {
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .quality-option.active {
            background: var(--accent-color) !important;
            color: white;
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
        
        /* Slider animation - improved */
        .slider-container { 
            overflow: hidden;
            border-radius: 1rem;
        } 
        
        .slider-track { 
            display: flex; 
            transition: transform 0.5s ease-in-out;
            width: 200%; /* Double width to accommodate duplicate cards */
        }
        
        .slider-card { 
            flex: 0 0 100%; 
            padding: 0.5rem;
        } 
        
        @media (min-width: 768px) { 
            .slider-card { 
                flex: 0 0 33.3333%; 
            }
            .slider-track {
                width: 200%; /* Still double for smooth infinite scroll */
            }
        }
        
        /* Card styling improvements */
        .slider-card-bg { 
            background-color: var(--hero-card-bg);
            border: 1px solid var(--input-border-color);
            transition: all 0.3s ease;
            height: 100%;
            min-height: 120px;
        }
        
        .slider-card-bg:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--glow-color);
            border-color: var(--accent-color);
        }
        
        .feature-icon {
            font-size: 2rem;
            color: var(--accent-color);
            margin-right: 1rem;
            min-width: 3rem;
            text-align: center;
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
            z-index: var(--z-center-action);
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
        
        /* Toast notifications - enhanced for cross-browser compatibility */
        #toast-container { 
            position: fixed; 
            bottom: 1.5rem; 
            right: 1.5rem; 
            z-index: var(--z-toast); 
            display: flex; 
            flex-direction: column; 
            gap: 0.75rem; 
            pointer-events: none;
        }
        
        .toast { 
            display: flex; 
            align-items: center; 
            padding: 1rem 1.25rem; 
            border-radius: 0.5rem; 
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); 
            transform: translateX(120%); 
            opacity: 0; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            min-width: 280px;
            max-width: 400px;
            word-wrap: break-word;
            pointer-events: auto;
            position: relative;
        }
        
        .toast.show { 
            transform: translateX(0); 
            opacity: 1; 
        }
        
        .toast-close {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 1.2rem;
            line-height: 1;
            opacity: 0.7;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .toast-close:hover {
            opacity: 1;
        }
        
        /* Toast types */
        .toast.toast-info { background-color: #3b82f6; color: white; }
        .toast.toast-success { background-color: #10b981; color: white; }
        .toast.toast-error { background-color: #ef4444; color: white; }
        .toast.toast-warning { background-color: #f59e0b; color: white; }
        
        /* Settings menu and context menu */
        #settings-menu, .custom-context-menu { 
            background-color: rgba(28, 28, 28, 0.9); 
            backdrop-filter: blur(5px);
            z-index: var(--z-settings-menu);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        /* Custom context menu specific styling */
        .custom-context-menu {
            z-index: var(--z-context-menu);
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
            z-index: var(--z-thumbnail);
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
            z-index: var(--z-volume-slider);
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
        
        /* Responsive design for mobile - enhanced */
        @media (max-width: 640px) {
            #app-wrapper {
                padding: 0.5rem;
            }
            
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
                min-height: 100px;
            }
            
            .feature-icon {
                font-size: 1.5rem;
                min-width: 2.5rem;
            }
            
            /* Improved mobile search bar */
            #videoUrl {
                font-size: 16px; /* Prevents zoom on iOS */
                padding-left: 2.5rem;
                padding-right: 2.5rem;
            }
            
            #loadVideoBtn {
                padding: 0.5rem;
            }
            
            /* Better mobile video controls */
            .video-controls {
                padding: 0.5rem;
            }
            
            .video-controls .flex {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            /* Mobile-friendly settings menu */
            #settings-menu {
                width: calc(100vw - 2rem);
                max-width: 20rem;
                right: auto;
                left: 50%;
                transform: translateX(-50%);
                bottom: 4rem;
            }
            
            /* Better mobile toast positioning */
            #toast-container {
                left: 1rem;
                right: 1rem;
                bottom: 1rem;
            }
            
            .toast {
                min-width: auto;
                max-width: none;
            }
        }
        
        @media (max-width: 480px) {
            /* Extra small screens */
            h1 {
                font-size: 2rem !important;
            }
            
            .video-controls .flex > div:first-child {
                flex-wrap: wrap;
            }
            
            #timeDisplay {
                font-size: 0.7rem;
                order: 10;
                width: 100%;
                text-align: center;
                margin-top: 0.5rem;
            }
        }
            }
            
            /* Stack controls vertically on very small screens */
            .video-controls .flex {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            /* Adjust toast for mobile */
            #toast-container {
                bottom: 1rem;
                right: 1rem;
                left: 1rem;
            }
            
            .toast {
                min-width: auto;
                max-width: none;
            }
            
            /* Improve input on mobile */
            #videoUrl {
                font-size: 16px; /* Prevent zoom on iOS */
            }
        }
        
        /* Improve contrast and accessibility */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
            
            .slider-track {
                transition: none;
            }
        }
        
        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .slider-card-bg {
                border-width: 2px;
            }
            
            .control-button {
                border: 1px solid currentColor;
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

    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4 sm:p-6">

    <div id="app-wrapper" class="w-full max-w-4xl lg:max-w-5xl 2xl:max-w-7xl mx-auto space-y-6">
        <!-- Header with enhanced gradient title and theme toggle -->
        <div class="text-center relative">
            <h1 class="text-4xl md:text-5xl font-bold bg-clip-text text-transparent" style="background-image: var(--primary-gradient);">Vibe Player</h1>
            <p class="mt-2" style="color: var(--text-muted-color);">The Ultimate Hub for Seamless Streaming.</p>
            <button id="theme-toggle" class="absolute top-0 right-0 p-2 rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[var(--bg-color)] focus:ring-[var(--accent-color)] glassmorphism" aria-label="Toggle theme">
                <i class="fas fa-sun text-xl"></i>
            </button>
        </div>
        
        <!-- Enhanced Features Slider Section with glassmorphism -->
        <div class="p-6 rounded-2xl shadow-lg hero-section glassmorphism">
            <div class="slider-container">
                <div class="slider-track"></div>
            </div>
        </div>
        
        <!-- Enhanced URL Input Section with glassmorphism -->
        <div class="relative input-glow rounded-full glassmorphism">
            <span id="url-status-icon" class="absolute inset-y-0 left-0 flex items-center pl-4">
                <i class="fas fa-link" style="color: var(--text-muted-color);"></i>
            </span>
            <input id="videoUrl" type="text" placeholder="Paste a direct video link or Terabox link here..." 
                   class="w-full bg-transparent border-0 rounded-full py-3 pl-12 pr-4 focus:outline-none" 
                   style="color: var(--text-color);">
            <button id="loadVideoBtn" class="absolute inset-y-0 right-0 flex items-center px-6 text-white rounded-r-full" aria-label="Load Video">
                <i class="fas fa-play"></i>
            </button>
        </div>
        
        <!-- Enhanced Info Box with glassmorphism -->
        <div class="p-4 rounded-lg border info-box glassmorphism">
            <p><i class="fas fa-info-circle mr-2"></i><strong>Terabox links are auto-resolved!</strong> For other restricted sites, use the <strong>Proxy</strong> setting.</p>
            <p class="mt-2 text-sm"><i class="fas fa-keyboard mr-2"></i><strong>Keyboard Shortcuts:</strong> Space (play/pause), ←/→ (seek), ↑/↓ (volume), F (fullscreen), M (mute), T (theater mode)</p>
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
                
                <!-- Enhanced Video Controls Overlay with YouTube-style design -->
                <div class="absolute bottom-0 left-0 right-0 p-2 md:p-4 bg-gradient-to-t from-black/80 via-black/40 to-transparent video-controls opacity-100" style="z-index: var(--z-video-controls);">
                    <!-- Enhanced Progress Bar Container with hover preview -->
                    <div class="relative mb-3">
                        <input id="progressBar" type="range" min="0" max="100" value="0" 
                               class="w-full h-1 rounded-lg cursor-pointer mb-2 progress-bar-enhanced" 
                               style="--progress: 0%;" aria-label="Seek progress">
                        <div id="progress-hover-preview" class="absolute bottom-full mb-2 hidden">
                            <div class="bg-black/80 text-white text-xs px-2 py-1 rounded">00:00</div>
                        </div>
                    </div>
                    
                    <!-- Enhanced Control Buttons with YouTube-style layout -->
                    <div class="flex justify-between items-center text-white flex-wrap">
                        <!-- Left side controls -->
                        <div class="flex items-center space-x-2 md:space-x-3">
                            <button id="playPauseBtn" class="control-button-enhanced text-xl hover:scale-110" aria-label="Play or Pause">
                                <i class="fas fa-play"></i>
                            </button>
                            <button id="rewindBtn" class="control-button text-lg hover:scale-110" aria-label="Rewind 10 seconds">
                                <i class="fas fa-backward"></i>
                            </button>
                            <button id="forwardBtn" class="control-button text-lg hover:scale-110" aria-label="Forward 10 seconds">
                                <i class="fas fa-forward"></i>
                            </button>
                            
                            <!-- Enhanced Volume Control with YouTube-style hover -->
                            <div class="relative volume-control-enhanced">
                                <button id="volumeBtn" class="control-button hover:scale-110" aria-label="Mute or Unmute">
                                    <i class="fas fa-volume-high"></i>
                                </button>
                                <div class="volume-slider-container-enhanced">
                                    <input id="volumeSlider" type="range" min="0" max="1" step="0.01" value="1" 
                                           class="volume-slider-enhanced" aria-label="Volume control">
                                </div>
                            </div>
                            
                            <!-- Enhanced Time Display -->
                            <div id="timeDisplay" class="text-sm font-mono bg-black/30 px-2 py-1 rounded">00:00 / 00:00</div>
                        </div>
                        
                        <!-- Right side controls -->
                        <div class="flex items-center space-x-2 md:space-x-3">
                            <!-- Quality Selection (YouTube-style) -->
                            <div class="relative quality-selector">
                                <button id="qualityBtn" class="control-button hover:scale-110" aria-label="Quality settings">
                                    <span class="text-xs font-bold">HD</span>
                                </button>
                                <div id="quality-menu" class="hidden absolute bottom-full right-0 mb-2 bg-black/90 rounded-md p-2 text-white min-w-24">
                                    <div class="text-xs space-y-1">
                                        <div class="quality-option hover:bg-white/20 p-1 rounded cursor-pointer" data-quality="auto">Auto</div>
                                        <div class="quality-option hover:bg-white/20 p-1 rounded cursor-pointer" data-quality="1080p">1080p</div>
                                        <div class="quality-option hover:bg-white/20 p-1 rounded cursor-pointer" data-quality="720p">720p</div>
                                        <div class="quality-option hover:bg-white/20 p-1 rounded cursor-pointer" data-quality="480p">480p</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Enhanced Settings Menu -->
                            <div class="relative">
                                <button id="settingsBtn" class="control-button hover:scale-110" aria-label="Settings">
                                    <i class="fas fa-cog"></i>
                                </button>
                                <div id="settings-menu" class="hidden absolute bottom-full right-0 mb-2 rounded-md p-2 text-white w-72 glassmorphism bg-black/90">
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
                            
                            <!-- Mini-player button (YouTube-style) -->
                            <button id="miniPlayerBtn" class="control-button hover:scale-110" aria-label="Mini player">
                                <i class="fas fa-clone"></i>
                            </button>
                            <button id="theaterBtn" class="control-button hover:scale-110" aria-label="Theater mode">
                                <i class="fas fa-rectangle-xmark"></i>
                            </button>
                            <button id="pipBtn" class="control-button hover:scale-110" aria-label="Picture in picture">
                                <i class="fas fa-expand-arrows-alt"></i>
                            </button>
                            <button id="fullscreenBtn" class="control-button hover:scale-110" aria-label="Fullscreen">
                                <i class="fas fa-expand"></i>
                            </button>
                            <button id="downloadBtn" class="control-button hover:scale-110" aria-label="Download video">
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
                    if (!this.sliderTrack) {
                        console.warn('Slider track not found');
                        return;
                    }
                    
                    this.features = [
                        { icon: 'fa-server', title: 'Resolver Built-in', desc: 'Automatically resolves Terabox links.' },
                        { icon: 'fa-keyboard', title: 'Shortcuts', desc: 'Control playback with your keyboard.' },
                        { icon: 'fa-palette', title: 'Themes', desc: 'Switch between light and dark modes instantly.' },
                        { icon: 'fa-download', title: 'Download', desc: 'Save videos with progress tracking.' },
                        { icon: 'fa-expand-arrows-alt', title: 'Modes', desc: 'Enjoy theater, PiP, and fullscreen views.' },
                        { icon: 'fa-cog', title: 'Settings', desc: 'Loop your favorite videos.' }
                    ];
                    
                    this.renderSlider();
                    this.currentIndex = 0;
                    this.startAutoSlide();
                },
                
                renderSlider() {
                    // Create cards with improved structure
                    const createCard = (feature) => `
                        <div class="slider-card p-4">
                            <div class="p-6 rounded-xl h-full flex items-center space-x-4 slider-card-bg">
                                <div class="feature-icon">
                                    <i class="fas ${feature.icon}"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-bold text-lg mb-1">${feature.title}</h3>
                                    <p class="text-sm" style="color: var(--text-muted-color);">${feature.desc}</p>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Duplicate features for seamless infinite scroll
                    this.sliderTrack.innerHTML = [...this.features, ...this.features]
                        .map(createCard).join('');
                },
                
                startAutoSlide() {
                    this.slideInterval = setInterval(() => this.slide(), 3000);
                    
                    // Pause on hover
                    this.sliderTrack.addEventListener('mouseenter', () => {
                        clearInterval(this.slideInterval);
                    });
                    
                    this.sliderTrack.addEventListener('mouseleave', () => {
                        this.startAutoSlide();
                    });
                },
                
                slide() {
                    this.currentIndex++;
                    const slideWidth = window.innerWidth >= 768 ? 100/3 : 100; // 33.33% on desktop, 100% on mobile
                    this.sliderTrack.style.transform = `translateX(-${this.currentIndex * slideWidth}%)`;
                    
                    // Reset to beginning when reaching the end of first set
                    if (this.currentIndex >= this.features.length) {
                        setTimeout(() => {
                            this.sliderTrack.style.transition = 'none';
                            this.currentIndex = 0;
                            this.sliderTrack.style.transform = 'translateX(0)';
                            setTimeout(() => {
                                this.sliderTrack.style.transition = 'transform 0.5s ease-in-out';
                            }, 50);
                        }, 500);
                    }
                }
            };
            
            // UI utilities module
            const UI = {
                toastCounter: 0,
                
                /**
                 * Displays a toast notification with enhanced features
                 * @param {string} message - The message to display
                 * @param {string} type - The type of toast (info, success, error, warning)
                 * @param {number} duration - Duration in milliseconds (default: 5000)
                 */
                showToast(message, type = 'info', duration = 5000) {
                    const toastContainer = document.getElementById('toast-container');
                    if (!toastContainer) {
                        console.warn('Toast container not found');
                        return;
                    }
                    
                    const toast = document.createElement('div');
                    const toastId = ++this.toastCounter;
                    const icons = { 
                        info: 'fa-info-circle', 
                        success: 'fa-check-circle', 
                        error: 'fa-exclamation-circle',
                        warning: 'fa-exclamation-circle'
                    };
                    
                    toast.className = `toast toast-${type}`;
                    toast.setAttribute('data-toast-id', toastId);
                    toast.innerHTML = `
                        <i class="fas ${icons[type]} mr-3 text-xl"></i>
                        <span class="flex-1">${this.escapeHtml(message)}</span>
                        <button class="toast-close" onclick="UI.closeToast(${toastId})" aria-label="Close">×</button>
                    `;
                    
                    toastContainer.appendChild(toast);
                    
                    // Trigger show animation
                    requestAnimationFrame(() => {
                        toast.classList.add('show');
                    });
                    
                    // Auto-hide after duration
                    if (duration > 0) {
                        setTimeout(() => this.closeToast(toastId), duration);
                    }
                },
                
                /**
                 * Closes a specific toast
                 * @param {number} toastId - The ID of the toast to close
                 */
                closeToast(toastId) {
                    const toast = document.querySelector(`[data-toast-id="${toastId}"]`);
                    if (toast) {
                        toast.classList.remove('show');
                        setTimeout(() => {
                            if (toast.parentNode) {
                                toast.parentNode.removeChild(toast);
                            }
                        }, 300);
                    }
                },
                
                /**
                 * Escapes HTML to prevent XSS
                 * @param {string} text - Text to escape
                 */
                escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                },
                
                /**
                 * Shows an action icon in the center of the video
                 * @param {string} iconClass - The FontAwesome icon class
                 * @param {string} content - Optional custom content
                 */
                showActionIcon(iconClass, content = '') {
                    const centerActionIcon = document.getElementById('center-action-icon');
                    if (!centerActionIcon) return;
                    
                    centerActionIcon.innerHTML = content || `<i class="fas ${iconClass}"></i>`;
                    centerActionIcon.classList.add('visible');
                    
                    clearTimeout(this.actionIconTimeout);
                    this.actionIconTimeout = setTimeout(() => {
                        centerActionIcon.classList.remove('visible');
                    }, 800);
                },
                
                /**
                 * Updates the URL input status icon
                 * @param {string} status - The status (loading, idle, error)
                 */
                setUrlStatus(status) {
                    const iconEl = document.getElementById('url-status-icon');
                    if (!iconEl) return;
                    
                    switch (status) {
                        case 'loading':
                            iconEl.innerHTML = `<i class="fas fa-spinner fa-spin" style="color: var(--accent-color);"></i>`;
                            break;
                        case 'error':
                            iconEl.innerHTML = `<i class="fas fa-exclamation-circle" style="color: #ef4444;"></i>`;
                            break;
                        default:
                            iconEl.innerHTML = `<i class="fas fa-link" style="color: var(--text-muted-color);"></i>`;
                    }
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
                        miniPlayerBtn: document.getElementById('miniPlayerBtn'),
                        qualityBtn: document.getElementById('qualityBtn'),
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
                    this.buttons.miniPlayerBtn.addEventListener('click', () => this.toggleMiniPlayer());
                    this.buttons.qualityBtn.addEventListener('click', (e) => { e.stopPropagation(); this.toggleQualityMenu(); });
                    document.getElementById('quality-menu').addEventListener('click', (e) => e.stopPropagation());
                    document.querySelectorAll('.quality-option').forEach(option => {
                        option.addEventListener('click', (e) => this.setQuality(e.target.dataset.quality));
                    });
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
                    if (!url) { 
                        UI.showToast('Please paste a video URL.', 'error'); 
                        return; 
                    }
                    
                    // Validate URL format
                    try {
                        new URL(url);
                    } catch (e) {
                        UI.showToast('Please enter a valid URL.', 'error');
                        return;
                    }
                    
                    this.playerContainer.classList.remove('hidden');
                    UI.setUrlStatus('loading');

                    try {
                        let finalUrl = url;
                        
                        // Check if it's a Terabox or similar service link
                        if (url.includes('terabox.com') || url.includes('1024terabox.com') || url.includes('4funbox.com')) {
                            UI.showToast('Resolving Terabox link...', 'info');
                            finalUrl = await this.resolveTeraboxLink(url);
                        }
                        
                        // Apply proxy if configured
                        const proxiedUrl = this.proxyUrl ? this.proxyUrl + encodeURIComponent(finalUrl) : finalUrl;
                        
                        // Set video sources
                        this.video.src = proxiedUrl;
                        this.thumbnailVideo.src = proxiedUrl;

                        // Load and attempt autoplay
                        this.video.load();
                        
                        // Wait for metadata to load
                        await new Promise((resolve, reject) => {
                            const timeout = setTimeout(() => reject(new Error('Video loading timeout')), 10000);
                            
                            this.video.addEventListener('loadedmetadata', () => {
                                clearTimeout(timeout);
                                resolve();
                            }, { once: true });
                            
                            this.video.addEventListener('error', () => {
                                clearTimeout(timeout);
                                reject(new Error('Video failed to load'));
                            }, { once: true });
                        });
                        
                        // Attempt autoplay
                        try {
                            await this.video.play();
                            UI.showToast('Video loaded and playing!', 'success');
                        } catch (playError) {
                            if (playError.name !== 'AbortError') {
                                UI.showToast('Video loaded. Click play to start.', 'info');
                            }
                        }
                        
                        // Add to history
                        this.addToHistory(url);
                        
                    } catch (error) {
                        console.error('Video loading error:', error);
                        UI.setUrlStatus('error');
                        
                        // Provide specific error messages
                        let errorMessage = 'Failed to load video. ';
                        if (error.message.includes('CORS')) {
                            errorMessage += 'Try enabling a CORS proxy in settings.';
                        } else if (error.message.includes('network')) {
                            errorMessage += 'Check your internet connection.';
                        } else if (error.message.includes('timeout')) {
                            errorMessage += 'The request timed out. Try again.';
                        } else {
                            errorMessage += error.message;
                        }
                        
                        UI.showToast(errorMessage, 'error');
                    } finally {
                        if (document.getElementById('url-status-icon').innerHTML.includes('fa-spinner')) {
                            UI.setUrlStatus('idle');
                        }
                    }
                },

                async resolveTeraboxLink(url) {
                    UI.showToast('Starting Terabox resolution...', 'info');
                    
                    try {
                        // Try server-side resolution first
                        const controller = new AbortController();
                        const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 second timeout
                        
                        const response = await fetch(`?resolve&url=${encodeURIComponent(url)}`, {
                            signal: controller.signal
                        });
                        clearTimeout(timeoutId);
                        
                        if (!response.ok) {
                            throw new Error(`Server response: ${response.status}`);
                        }
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            UI.showToast('Terabox link resolved successfully!', 'success');
                            return result.url;
                        } else {
                            // Log detailed error information
                            console.warn('Terabox Resolution Failed:', result);
                            
                            if (result.errors && result.errors.length > 0) {
                                // Check if all errors are network-related (sandbox environment)
                                const networkErrors = result.errors.filter(error => 
                                    error.includes('Could not resolve host') || 
                                    error.includes('cURL error') ||
                                    error.includes('HTTP 0') ||
                                    error.includes('Connection timed out')
                                );
                                
                                if (networkErrors.length === result.errors.length) {
                                    UI.showToast('Network restrictions detected. Trying alternative methods...', 'info');
                                    return await this.resolveTeraboxClientSide(url);
                                }
                            }
                            
                            throw new Error(result.message || 'Failed to resolve Terabox link');
                        }
                    } catch (error) {
                        if (error.name === 'AbortError') {
                            UI.showToast('Request timed out, trying alternative method...', 'warning');
                        } else if (error.name === 'TypeError' && error.message.includes('fetch')) {
                            UI.showToast('Network error detected, trying fallback...', 'warning');
                        }
                        
                        // Always try client-side resolution as fallback
                        try {
                            return await this.resolveTeraboxClientSide(url);
                        } catch (clientError) {
                            // If all else fails, try direct URL as last resort
                            UI.showToast('All resolution methods failed, trying direct URL...', 'warning');
                            return await this.tryDirectUrl(url);
                        }
                    }
                },

                async resolveTeraboxClientSide(url) {
                    // Enhanced client-side fallback using multiple CORS proxies and alternative services
                    const proxies = [
                        'https://api.allorigins.win/raw?url=',
                        'https://corsproxy.io/?',
                        'https://thingproxy.freeboard.io/fetch/',
                        'https://cors-anywhere.herokuapp.com/',
                        'https://api.codetabs.com/v1/proxy?quest='
                    ];
                    
                    let lastError;
                    
                    for (const proxy of proxies) {
                        try {
                            UI.showToast(`Trying resolution method ${proxies.indexOf(proxy) + 1}/${proxies.length}...`, 'info');
                            
                            const controller = new AbortController();
                            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout per proxy
                            
                            const response = await fetch(proxy + encodeURIComponent(url), {
                                method: 'GET',
                                signal: controller.signal,
                                headers: {
                                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                                }
                            });
                            
                            clearTimeout(timeoutId);
                            
                            if (!response.ok) {
                                lastError = `Proxy returned ${response.status}`;
                                continue;
                            }
                            
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
                                /"stream_url":"(https?:\/\/[^"]+)"/,
                                /href=["']([^"']*\.mp4[^"']*)/i,
                                /<source[^>]+src=["']([^"']+\.mp4[^"']*)/i
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
                                            resolvedUrl.includes('dlink') ||
                                            resolvedUrl.includes('download')) {
                                            UI.showToast('Alternative resolution successful!', 'success');
                                            return resolvedUrl;
                                        }
                                    } catch (e) {
                                        continue;
                                    }
                                }
                            }
                            
                            lastError = 'No valid video URL found in response';
                        } catch (error) {
                            lastError = error.name === 'AbortError' ? 'Request timeout' : error.message;
                            console.warn(`Client-side proxy ${proxy} failed:`, error);
                            continue;
                        }
                    }
                    
                    throw new Error(`All proxies failed. Last error: ${lastError}`);
                },
                
                async tryDirectUrl(url) {
                    // Last resort: try using the URL directly with CORS headers
                    try {
                        UI.showToast('Attempting direct URL access...', 'warning');
                        
                        // Sometimes Terabox URLs work directly in certain contexts
                        const testVideo = document.createElement('video');
                        testVideo.crossOrigin = 'anonymous';
                        
                        return new Promise((resolve, reject) => {
                            const timeout = setTimeout(() => {
                                reject(new Error('Direct URL test timed out'));
                            }, 5000);
                            
                            testVideo.addEventListener('loadstart', () => {
                                clearTimeout(timeout);
                                UI.showToast('Direct URL access successful!', 'success');
                                resolve(url);
                            });
                            
                            testVideo.addEventListener('error', () => {
                                clearTimeout(timeout);
                                reject(new Error('Direct URL access failed - CORS blocked or invalid URL'));
                            });
                            
                            testVideo.src = url;
                        });
                    } catch (error) {
                        throw new Error('Direct URL access failed: ' + error.message);
                    }
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
                },
                
                handleKeyboardShortcuts(e) {
                    // Don't handle shortcuts when typing in input fields
                    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
                    
                    // Prevent default behavior for our shortcuts
                    const shortcutKeys = [' ', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'f', 'F', 'm', 'M', 't', 'T'];
                    if (shortcutKeys.includes(e.key)) {
                        e.preventDefault();
                    }
                    
                    switch (e.key) {
                        case ' ': // Spacebar - play/pause
                            this.togglePlayPause();
                            break;
                        case 'ArrowLeft': // Left arrow - rewind 10s
                            this.seekVideo(-10);
                            break;
                        case 'ArrowRight': // Right arrow - forward 10s
                            this.seekVideo(10);
                            break;
                        case 'ArrowUp': // Up arrow - volume up
                            this.adjustVolume(0.1);
                            break;
                        case 'ArrowDown': // Down arrow - volume down
                            this.adjustVolume(-0.1);
                            break;
                        case 'f':
                        case 'F': // F - fullscreen
                            this.toggleFullscreen();
                            break;
                        case 'm':
                        case 'M': // M - mute/unmute
                            this.toggleMute();
                            break;
                        case 't':
                        case 'T': // T - theater mode
                            this.toggleTheaterMode();
                            break;
                        case 'Escape': // Escape - exit fullscreen or theater
                            if (document.fullscreenElement) {
                                document.exitFullscreen();
                            } else if (document.body.classList.contains('theater-mode')) {
                                this.toggleTheaterMode();
                            }
                            break;
                    }
                },
                
                adjustVolume(delta) {
                    const newVolume = Math.max(0, Math.min(1, this.video.volume + delta));
                    this.video.volume = newVolume;
                    this.volumeSlider.value = newVolume;
                    this.updateVolumeIcon();
                    
                    // Show volume feedback
                    const percentage = Math.round(newVolume * 100);
                    UI.showActionIcon('fa-volume-high', `${percentage}%`);
                },
                
                toggleMiniPlayer() {
                    const wrapper = this.videoPlayerWrapper;
                    const appWrapper = document.getElementById('app-wrapper');
                    
                    if (wrapper.classList.contains('mini-player')) {
                        // Exit mini-player mode
                        wrapper.classList.remove('mini-player');
                        
                        // Remove close button
                        const closeBtn = wrapper.querySelector('.mini-player-close');
                        if (closeBtn) closeBtn.remove();
                        
                        // Restore to original position
                        document.getElementById('playerContainer').appendChild(wrapper);
                        
                        // Show other UI elements
                        appWrapper.querySelectorAll('.hero-section, .input-glow, .info-box').forEach(el => {
                            el.style.display = '';
                        });
                        
                        UI.showToast('Exited mini-player mode', 'info');
                    } else {
                        // Enter mini-player mode
                        wrapper.classList.add('mini-player');
                        
                        // Add close button
                        const closeBtn = document.createElement('button');
                        closeBtn.className = 'mini-player-close';
                        closeBtn.innerHTML = '×';
                        closeBtn.addEventListener('click', () => this.toggleMiniPlayer());
                        wrapper.appendChild(closeBtn);
                        
                        // Move to body for fixed positioning
                        document.body.appendChild(wrapper);
                        
                        // Hide other UI elements
                        appWrapper.querySelectorAll('.hero-section, .input-glow, .info-box').forEach(el => {
                            el.style.display = 'none';
                        });
                        
                        UI.showToast('Entered mini-player mode', 'success');
                    }
                },
                
                toggleQualityMenu() {
                    const menu = document.getElementById('quality-menu');
                    menu.classList.toggle('hidden');
                },
                
                setQuality(quality) {
                    const qualityBtn = this.buttons.qualityBtn;
                    const menu = document.getElementById('quality-menu');
                    
                    // Update button text
                    qualityBtn.innerHTML = `<span class="text-xs font-bold">${quality.toUpperCase()}</span>`;
                    
                    // Update active option
                    menu.querySelectorAll('.quality-option').forEach(option => {
                        option.classList.remove('active');
                        if (option.dataset.quality === quality) {
                            option.classList.add('active');
                        }
                    });
                    
                    // Hide menu
                    menu.classList.add('hidden');
                    
                    // Store quality preference
                    localStorage.setItem('preferredQuality', quality);
                    
                    UI.showToast(`Quality set to ${quality}`, 'success');
                }
            };

            // Initialize the application
            App.init();
        });
    </script>
</body>
</html>