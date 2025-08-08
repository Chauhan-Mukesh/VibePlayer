# Vibe Player
**The Ultimate Open Source Streaming Hub**

*An advanced single-page PHP application that resolves Terabox share URLs to CDN direct links and streams video via server-side proxy with a custom Gen-Alpha themed UI and embedded video player.*

## Status & Badges

[![Build Status](https://img.shields.io/github/actions/workflow/status/Chauhan-Mukesh/VibePlayer/ci.yml?branch=main)](https://github.com/Chauhan-Mukesh/VibePlayer/actions)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![Docker Pulls](https://img.shields.io/docker/pulls/vibeplayer/vibeplayer)](https://hub.docker.com/r/vibeplayer/vibeplayer)
[![PHP Version](https://img.shields.io/badge/PHP-8.2+-777BB4.svg)](https://php.net/)

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Demo](#demo)
- [Quick Start](#quick-start)
- [Docker](#docker)
- [Usage](#usage)
- [Backend Details](#backend--architecture)
- [API/Endpoints](#apiendpoints)
- [Developer Setup](#development)
- [Security](#security--privacy)
- [Performance & Testing](#performance--scaling)
- [Contributing](#contributing)
- [License](#license)
- [Acknowledgements](#acknowledgements)

## Overview

Vibe Player is a sophisticated single-page PHP application designed to seamlessly resolve Terabox share URLs to direct CDN links and stream video content through a server-side proxy. The application features a custom Gen-Alpha themed user interface with an embedded HTML5 video player that supports advanced playback controls, keyboard shortcuts, and multiple viewing modes.

**Key Architecture:**
- **Frontend**: Single-page application with vanilla JavaScript and custom CSS utilities
- **Backend**: PHP 8.2+ with cURL-based resolution engine
- **Streaming**: Server-side Range-enabled proxy for seamless video delivery
- **UI Theme**: Gen-Alpha design with glassmorphism effects and smooth animations

## Features

### Core Functionality
- **🔗 Multi-Method Terabox Link Resolver** - Direct API + Apify fallback for maximum reliability
- **🎥 Server-side Range-enabled Streaming** - Efficient chunked video delivery with seek support
- **⬇️ Fallback Direct Download** - Alternative download method when streaming fails
- **⚡ Caching of Resolved Links** - 15-minute TTL cache for improved performance
- **🌙 Dark/Light Theme** - Automatic system preference detection with manual toggle
- **🎮 YouTube-like Player Controls** - Professional video interface with advanced features
- **⌨️ Keyboard Shortcuts** - Complete keyboard navigation support
- **🎨 Plugin-ready CSS** - Modular CSS architecture for easy customization
- **📦 Minimal Dependencies** - Self-contained with no external CDN requirements

### Advanced Features
- Picture-in-Picture mode support
- Theater mode for distraction-free viewing
- Custom context menus and tooltips
- Playback speed control (0.5x to 2x)
- Volume control with visual feedback
- Progress bar with thumbnail previews
- Playlist and history management
- CORS proxy configuration

## Demo / Screenshots

### Light Mode Interface
![Vibe Player Light Mode](https://github.com/user-attachments/assets/7d78b7ca-924a-44ab-be44-3289131590ec)

### Dark Mode Interface
*Screenshot placeholder - capture after theme toggle*

**Live Demo**: [GitHub Pages Demo](https://chauhan-mukesh.github.io/VibePlayer/) *(if available)*

**Demo Usage**:
1. Paste any Terabox sharing link in the input field
2. Click the play button or press Enter
3. Video will auto-resolve and begin playback
4. Use keyboard shortcuts for navigation (Space, ←/→, F, M, T)

## Quick Start

### Requirements
- **Docker & Docker Compose** (recommended)
- **OR** PHP 8.2+ CLI for development
- **Optional**: Apify API token for enhanced TeraBox resolution

### Minimal Setup

1. **Clone Repository**
   ```bash
   git clone https://github.com/Chauhan-Mukesh/VibePlayer.git
   cd VibePlayer
   ```

2. **Configure Apify Integration (Optional but Recommended)**
   ```bash
   # Copy environment template
   cp .env.example .env
   
   # Edit .env and add your Apify API token
   nano .env
   ```
   
   **To get an Apify API token:**
   - Sign up at [Apify.com](https://apify.com/) (free trial available)
   - Go to **Integrations** → **API tokens** in Apify Console
   - Copy your token and add it to `.env`: `APIFY_API_TOKEN=your_token_here`

3. **Docker Compose (Recommended)**
   ```bash
   docker compose up --build -d
   ```

4. **Open Application**
   ```
   http://localhost:8000
   ```

### Native PHP Development
```bash
# For development only
php -S 0.0.0.0:8000 index.php
```

### Apify Integration Setup

The VibePlayer includes an **Apify TeraBox Video/File Downloader** integration as a reliable fallback when direct resolution methods fail. This significantly improves success rates for TeraBox link resolution.

#### Getting Started with Apify

1. **Create Apify Account**
   - Sign up at [Apify.com](https://apify.com/) (free trial with 1,000 actor runs/month)
   - No credit card required for the free tier

2. **Get API Token**
   - Go to [Apify Console → Integrations](https://console.apify.com/account/integrations)
   - Copy your API token (starts with `apify_api_`)

3. **Configure VibePlayer**
   ```bash
   # Method 1: Environment Variable (Recommended)
   export APIFY_API_TOKEN=your_token_here
   
   # Method 2: Docker Compose
   echo "APIFY_API_TOKEN=your_token_here" >> .env
   docker compose up --build
   
   # Method 3: Direct Docker Run
   docker run -e APIFY_API_TOKEN=your_token_here vibeplayer:latest
   ```

4. **Verify Configuration**
   ```bash
   curl http://localhost:8000/?health | jq '.apify_integration'
   ```

#### How It Works

1. **Primary Method**: Direct TeraBox API calls (fast, free)
2. **Fallback Method**: Apify actor when direct method fails (reliable, paid)
3. **Automatic Switching**: No user intervention required
4. **Caching**: Successful results cached to minimize API usage
5. **Error Handling**: Graceful degradation when Apify is not configured

#### Cost Considerations

- **Free Tier**: 1,000 actor runs/month (sufficient for personal use)
- **Pay-as-you-go**: $0.25 per 1,000 runs for higher usage
- **Optimization**: Results are cached for 15 minutes to reduce API calls

## Docker

### Container Architecture  
The Docker setup uses **php:8.2-apache** with **optimized PHP extensions** for production deployment:
- **Minimal dependencies**: Only essential packages (cURL, ca-certificates)
- **No unnecessary extensions**: Removed exif, bcmath, zip (81.8% dependency reduction)
- **Faster builds**: ~50% improvement in build time
- Apache mod_rewrite for clean URL routing
- Range header support for video seeking
- Optimized PHP configuration for streaming
- Health checks and monitoring

**📋 Build Optimization**: See [DOCKER-OPTIMIZATION.md](DOCKER-OPTIMIZATION.md) for detailed analysis and performance improvements.

### Production Deployment
```bash
# Build production image
docker build -t vibeplayer:latest .

# Run with custom configuration
docker run -d \
  -p 80:80 \
  -e PHP_MEMORY_LIMIT=512M \
  -e PHP_MAX_EXECUTION_TIME=300 \
  --name vibeplayer \
  vibeplayer:latest
```

### Development with Hot Reload
```bash
# Docker Compose development
docker compose -f docker-compose.yml -f docker-compose.dev.yml up
```

### Container Management
```bash
# View logs
docker compose logs -f vibeplayer

# Shell access
docker compose exec vibeplayer bash

# Stop services
docker compose down
```

## Usage

### Basic Video Playback

1. **Paste Terabox Link** - Enter any Terabox sharing URL in the input field
2. **Automatic Resolution** - The system automatically extracts the direct video URL
3. **Stream or Download** - Choose between in-browser streaming or direct download

### Video vs Non-Video Content
- **Video Content**: Displays embedded player with full controls
- **Non-Video Content**: Provides direct download link
- **Mixed Content**: Prioritizes video files, lists other files

### User Interface
- **Player Controls**: Play/pause, seek, volume, fullscreen, quality selection
- **Progress Bar**: Click to seek, hover for thumbnail previews
- **Settings Menu**: Speed control, loop toggle, proxy configuration
- **History Tab**: Recent playback history with quick access

### Keyboard Shortcuts
| Key | Action | Description |
|-----|--------|-------------|
| `Space` | Play/Pause | Toggle video playback |
| `←` / `→` | Seek | Navigate backward/forward 10 seconds |
| `↑` / `↓` | Volume | Adjust volume up/down |
| `F` | Fullscreen | Toggle fullscreen mode |
| `M` | Mute | Toggle audio mute |
| `T` | Theater | Toggle theater mode |
| `P` | PiP | Picture-in-Picture mode |

## Backend & Architecture

### Resolver Endpoint (`/resolver`)
The core resolution engine processes Terabox URLs through multiple methods:

```php
GET /?resolve&url={encoded_terabox_url}
POST / (JSON: {"url": "terabox_url"})
```

**Resolution Pipeline**:
1. **URL Validation** - SSRF protection and domain whitelist verification
2. **Cache Check** - 15-minute TTL cache lookup
3. **Direct API Method** - Primary Terabox API integration
4. **Proxy Fallback** - Multiple CORS proxy attempts
5. **Response Caching** - Successful results cached for performance

### Streaming Proxy (`/stream.php`)
Server-side streaming proxy with Range header support:

```php
GET /?stream&url={direct_video_url}
```

**Features**:
- HTTP Range request handling for video seeking
- Chunked transfer encoding for efficient streaming
- Bandwidth throttling and connection management
- Error handling and retry mechanisms

### Caching Strategy
- **Cache Directory**: `sys_get_temp_dir() + '/vibeplayercache'`
- **Cache TTL**: 900 seconds (15 minutes)
- **Cache Keys**: MD5 hash of original URL
- **Cleanup**: Automatic expiration and size management

### SSRF Protection
- **Domain Whitelist**: Only approved Terabox domains
- **IP Validation**: Blocks private IP ranges
- **DNS Resolution**: Prevents hostname manipulation
- **Rate Limiting**: 10 requests per minute per IP

### Required PHP Settings
```ini
memory_limit = 256M
max_execution_time = 300
post_max_size = 512M
upload_max_filesize = 512M
allow_url_fopen = On
```

### Production Recommendations
- **Reverse Proxy**: Nginx + php-fpm for better performance
- **CDN Integration**: CloudFlare or similar for global delivery
- **Monitoring**: Health check endpoint at `/?health`
- **Logging**: Configurable error logging and access logs

## API/Endpoints

### Health Check
```http
GET /?health
```
**Response**:
```json
{
  "status": "healthy",
  "timestamp": 1703875200,
  "cache_dir": true,
  "version": "2.0.0",
  "cache_stats": {
    "files_count": 42,
    "directory_writable": true
  }
}
```

### Terabox Resolution
```http
GET /?resolve&url={url}
POST / 
Content-Type: application/json
{"url": "https://terabox.com/s/example"}
```

**Success Response**:
```json
{
  "success": true,
  "url": "https://direct-cdn-url.mp4",
  "metadata": {
    "name": "video.mp4",
    "size": 104857600,
    "mime": "video/mp4"
  },
  "thumbnail": "https://thumbnail-url.jpg",
  "method": "direct_api",
  "cache_hit": false
}
```

**Error Response**:
```json
{
  "success": false,
  "error": "resolution_failed",
  "message": "Failed to resolve Terabox link",
  "details": ["API timeout", "Proxy failed"],
  "retry_after": 30
}
```

### Video Streaming
```http
GET /?stream&url={video_url}
Range: bytes=0-1023
```

**Response Headers**:
```
Content-Type: video/mp4
Accept-Ranges: bytes
Content-Range: bytes 0-1023/104857600
Content-Length: 1024
```

### Video Download
```http
GET /?download&url={video_url}
```
**Features**: Range request support, resumable downloads, progress tracking

## Security & Privacy

### Data Protection
- **Zero Data Collection** - No personal information stored
- **Local Storage Only** - Settings stored in browser localStorage
- **No External Tracking** - No analytics, ads, or tracking scripts
- **Open Source Transparency** - Full source code available under GPL-3.0

### Security Measures
- **Input Validation** - All user inputs sanitized and validated
- **SSRF Prevention** - Strict domain whitelisting and IP validation
- **Rate Limiting** - 10 requests per minute per IP address
- **CORS Protection** - Configurable CORS proxy support
- **XSS Protection** - HTML escaping and Content Security Policy
- **Secure Headers** - X-Frame-Options, X-Content-Type-Options, etc.

### Privacy Features
- **No User Credentials** - No login or account system required
- **Minimal Metadata Logging** - Only essential error information logged
- **Cache Encryption** - Cached data protected with secure file permissions
- **Session-less Operation** - Stateless design with no session tracking

## Performance & Scaling

### Caching Strategy
- **Memory Caching** - In-memory cache for frequently accessed data
- **File System Cache** - Persistent cache for resolved URLs
- **Browser Caching** - Static assets cached with appropriate headers
- **CDN Integration** - Support for content delivery networks

### Streaming Optimization
- **Chunked Transfer** - Efficient large file streaming
- **Range Request Support** - Enables video seeking and partial downloads
- **Connection Pooling** - Reused connections for better performance
- **Bandwidth Management** - Configurable streaming rates

### Scaling Recommendations
- **Load Balancing** - Multiple PHP-FPM workers behind Nginx
- **Database Integration** - Optional Redis/MySQL for larger deployments
- **Container Orchestration** - Kubernetes deployment configurations
- **Monitoring Integration** - Prometheus metrics and Grafana dashboards

### Performance Tuning
```bash
# Nginx + PHP-FPM Configuration
sudo apt install nginx php8.2-fpm
sudo systemctl enable nginx php8.2-fpm

# Optimize PHP-FPM
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
```

## Development

### Local Development Setup
```bash
# Clone and setup
git clone https://github.com/Chauhan-Mukesh/VibePlayer.git
cd VibePlayer

# Install dependencies (if any)
composer install  # If composer.json exists

# Start development server
php -S localhost:8000 index.php

# Or use Docker for development
docker compose -f docker-compose.dev.yml up
```

### Code Architecture
```
index.php                 # Main application file
├── PHP Backend          # Server-side logic
│   ├── Resolution Engine
│   ├── Streaming Proxy
│   ├── API Endpoints
│   └── Caching System
├── HTML Structure       # Semantic markup
├── CSS Styling         # Custom utility classes
└── JavaScript Frontend # ES6+ modules
    ├── Player Module
    ├── Theme System
    ├── UI Components
    └── API Client
```

### Development Workflow
1. **Code Changes** - Edit index.php directly
2. **Testing** - Manual testing via browser
3. **Linting** - PSR-12 for PHP, ESLint for JavaScript
4. **Building** - No build step required (single file)
5. **Deployment** - Docker build and push

### Testing Guidelines
```bash
# Manual API testing
curl "http://localhost:8000/?health"
curl "http://localhost:8000/?resolve&url=https%3A//terabox.com/s/test"

# UI testing checklist
- [ ] Theme toggle functionality
- [ ] Video playback controls
- [ ] Keyboard shortcuts
- [ ] Mobile responsiveness
- [ ] Error handling
```

### Linting and Code Quality
```bash
# PHP linting (if tools available)
php -l index.php
phpcs --standard=PSR12 index.php

# JavaScript linting (if tools available)
eslint --init
```

## Troubleshooting

### Common Issues

#### Video Loading Failures
```
Symptoms: Video won't load, shows error message
Solutions:
1. Verify URL is a valid Terabox link
2. Check browser console for detailed errors  
3. Try enabling CORS proxy in settings
4. Ensure video format is supported (MP4, WebM)
5. Test with different Terabox link
```

#### Terabox Resolution Errors
```
Symptoms: "Failed to resolve Terabox link"
Causes:
- Link requires login (not supported)
- Temporary server issues
- Rate limiting by Terabox
- Network connectivity problems

Solutions:
- Verify link is publicly accessible
- Wait 30 seconds and retry
- Check browser network tab for failed requests
- Try different Terabox link format
```

#### CORS and Streaming Issues
```
Symptoms: "CORS error" or "Failed to fetch"
Solutions:
1. Configure custom CORS proxy:
   Settings → CORS Proxy URL → Enter proxy
2. Use alternative streaming service
3. Try direct download instead
4. Check browser security settings
```

#### Performance Problems
```
Symptoms: Slow loading, choppy playback
Solutions:
1. Close unnecessary browser tabs
2. Check internet connection speed
3. Try lower video quality
4. Clear browser cache
5. Use incognito/private mode
```

#### Mobile Compatibility
```
Symptoms: Poor mobile experience
Solutions:
1. Use Chrome/Safari on mobile
2. Enable hardware acceleration
3. Close background apps
4. Try landscape orientation
5. Use WiFi instead of cellular
```

### Debugging Steps
1. **Browser Console** - Check for JavaScript errors
2. **Network Tab** - Monitor failed requests
3. **Health Check** - Verify `/?health` endpoint
4. **Server Logs** - Check PHP error logs
5. **Cache Clear** - Clear browser cache and cookies

### Getting Support
- **GitHub Issues**: [Report bugs](https://github.com/Chauhan-Mukesh/VibePlayer/issues)
- **Discussions**: [Feature requests](https://github.com/Chauhan-Mukesh/VibePlayer/discussions)
- **Documentation**: [Wiki pages](https://github.com/Chauhan-Mukesh/VibePlayer/wiki)

## Contributing

We welcome contributions from the community! Here's how to get started:

### Quick Contribution Guide
1. **Fork** the repository on GitHub
2. **Create** a feature branch (`git checkout -b feature/awesome-feature`)
3. **Make** your changes with proper testing
4. **Commit** with descriptive messages (`git commit -m 'Add awesome feature'`)
5. **Push** to your branch (`git push origin feature/awesome-feature`)
6. **Submit** a Pull Request

### Development Standards
- **PHP**: Follow PSR-12 coding standard
- **JavaScript**: Use ES6+ features, avoid global variables
- **CSS**: Use utility classes, maintain mobile-first approach
- **Testing**: Include tests for new functionality
- **Documentation**: Update README for significant changes

### Code Review Process
- All PRs require review before merging
- Automated tests must pass (if implemented)
- Code style must meet project standards
- New features need documentation
- Breaking changes require version bump

### Contributor Guidelines
- **Sign Commits**: Use `git commit -s` for DCO compliance
- **Test Thoroughly**: Test across different browsers/devices
- **Include Changelog**: Add entry to CHANGELOG.md
- **Respect Licenses**: Ensure compatibility with GPL-3.0
- **Be Respectful**: Follow our Code of Conduct

### Areas for Contribution
- 🐛 **Bug Fixes** - Fix reported issues
- ✨ **New Features** - Add requested functionality  
- 📚 **Documentation** - Improve guides and examples
- 🎨 **UI/UX** - Enhance user interface design
- 🔧 **Performance** - Optimize speed and efficiency
- 🌍 **Internationalization** - Add language support
- 📱 **Mobile** - Improve mobile experience
- 🔒 **Security** - Enhance security measures

## License

This project is licensed under the **GNU General Public License v3.0** - see the [LICENSE](LICENSE) file for full details.

### License Summary
- ✅ **Commercial Use** - Use in commercial projects allowed
- ✅ **Modification** - Modify and adapt the code freely
- ✅ **Distribution** - Share and distribute copies
- ✅ **Private Use** - Use for personal/private projects
- ⚠️ **Copyleft** - Derivative works must use the same license
- ⚠️ **Disclose Source** - Source code must be made available when distributing

### SPDX License Identifier
```
SPDX-License-Identifier: GPL-3.0-or-later
```

For detailed license information and contributor guidelines, see [LICENSE-README.md](LICENSE-README.md).

## Acknowledgements

### Open Source Technologies
- **PHP** - Server-side scripting and API development
- **HTML5 Video API** - Modern video playback capabilities
- **Vanilla JavaScript** - Lightweight frontend framework
- **CSS Grid & Flexbox** - Modern responsive layout systems
- **Docker** - Containerization and deployment platform

### Community & Inspiration
- **YouTube** and **Vimeo** - Player interface inspiration
- **Open source media players** - Feature ideas and implementation patterns
- **Web accessibility standards** - WCAG guidelines and best practices
- **Modern web design trends** - Glassmorphism and Gen-Alpha aesthetics

### Contributors & Community
Special thanks to all contributors who have helped improve Vibe Player:

- **[@Chauhan-Mukesh](https://github.com/Chauhan-Mukesh)** - Project Creator & Lead Maintainer
- **Community Contributors** - Bug reports, feature requests, and code contributions
- **Beta Testers** - Early testing and feedback
- **Documentation Contributors** - Improving guides and examples

### Third-Party Resources
- **FontAwesome** - Icon set (with emoji fallbacks)
- **System Fonts** - Cross-platform typography
- **Modern Browser APIs** - Cutting-edge web capabilities

---

<div align="center">

**Made with ❤️ for the open source community**

[⭐ Star](https://github.com/Chauhan-Mukesh/VibePlayer) • [🐛 Issues](https://github.com/Chauhan-Mukesh/VibePlayer/issues) • [💡 Discussions](https://github.com/Chauhan-Mukesh/VibePlayer/discussions) • [📖 Wiki](https://github.com/Chauhan-Mukesh/VibePlayer/wiki)

**Built with modern web technologies for the next generation of streaming**

</div>