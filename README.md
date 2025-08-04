# 🎬 Vibe Player

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4.svg)](https://php.net/)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6%2B-F7DF1E.svg)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![HTML5](https://img.shields.io/badge/HTML5-Video-E34F26.svg)](https://developer.mozilla.org/en-US/docs/Web/Guide/HTML/HTML5)

> **The Ultimate Open Source Streaming Hub** - A powerful, feature-rich video player that seamlessly resolves Terabox links and streams any direct video content without requiring user authentication.

![Vibe Player Light Mode](https://github.com/user-attachments/assets/e62e8d2c-d484-4492-a4d1-7a42963f5044)

## ✨ Key Features

### 🎯 **Core Functionality**
- **🔗 Terabox Link Resolution** - Automatically resolves Terabox links without login
- **📺 Direct Video Streaming** - Play any direct video link instantly
- **🌐 Multiple Format Support** - MP4, WebM, AVI, MOV, MKV and more
- **⚡ Fast Resolution** - Multiple fallback methods for maximum reliability

### 🎨 **Modern Interface**
- **🌙 Light & Dark Themes** - Seamless theme switching with system preference detection
- **📱 Mobile Responsive** - Optimized for all screen sizes and devices  
- **🎯 Self-Contained** - Works even when external CDNs are blocked
- **♿ Accessibility** - Full keyboard navigation and screen reader support

### ⌨️ **Advanced Controls**
- **Keyboard Shortcuts** - Complete keyboard control for power users
  - `Space` - Play/Pause
  - `←/→` - Seek backward/forward (5s)
  - `↑/↓` - Volume up/down
  - `F` - Toggle fullscreen
  - `M` - Toggle mute
  - `T` - Toggle theater mode
- **Custom Player Controls** - Professional-grade video interface
- **Speed Control** - Playback speed from 0.5x to 2x
- **Loop Functionality** - Continuous playback support

### 🎭 **Viewing Modes**
- **🎪 Theater Mode** - Distraction-free viewing experience
- **📺 Picture-in-Picture** - Multitask while watching
- **🖥️ Fullscreen** - Immersive full-screen experience
- **🔄 Responsive Design** - Adapts to any screen size

### 🛠️ **Advanced Features**
- **📥 Video Download** - Download videos with progress tracking
- **📜 Playback History** - Keep track of recently played videos
- **⚙️ Settings Persistence** - Save preferences locally
- **🌐 CORS Proxy Support** - Bypass streaming restrictions
- **🔄 Multiple Resolution Methods** - Server-side and client-side fallbacks
- **📊 Real-time Feedback** - Toast notifications for all operations

## 📸 Screenshots

### Light Mode
![Light Mode Interface](https://github.com/user-attachments/assets/e62e8d2c-d484-4492-a4d1-7a42963f5044)

### Dark Mode
![Dark Mode Interface](https://github.com/user-attachments/assets/af7ca5c6-2817-437b-a4c6-37fd4f3e0632)

## 🚀 Quick Start

### Prerequisites
- PHP 8.0 or higher
- Modern web browser (Chrome 60+, Firefox 55+, Safari 11+, Edge 79+)
- Web server (Apache, Nginx, or PHP built-in server)

### Installation

1. **Clone the Repository**
   ```bash
   git clone https://github.com/Chauhan-Mukesh/VibePlayer.git
   cd VibePlayer
   ```

2. **Start PHP Development Server**
   ```bash
   php -S localhost:8000 index.php
   ```

3. **Open in Browser**
   ```
   http://localhost:8000
   ```

### Docker Setup (Optional)

```bash
# Build Docker image
docker build -t vibeplayer .

# Run container
docker run -p 8000:8000 vibeplayer
```

### Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 📖 Usage Guide

### Basic Video Playback

1. **Paste URL** - Enter any direct video link or Terabox link
2. **Auto-Resolution** - Terabox links are automatically resolved
3. **Enjoy** - Use controls or keyboard shortcuts to manage playback

### Supported Link Types

#### Terabox Links ✅
```
https://1024terabox.com/s/your-link-here
https://terabox.com/s/your-link-here
https://www.terabox.com/s/your-link-here
```

#### Direct Video Links ✅
```
https://example.com/video.mp4
https://cdn.example.com/videos/movie.webm
https://storage.example.com/content.avi
```

#### Streaming URLs ✅
```
https://stream.example.com/playlist.m3u8
https://live.example.com/stream/video.mp4
```

### Advanced Settings

#### CORS Proxy Configuration
For restricted content, configure custom CORS proxies:

1. Open **Settings** → **CORS Proxy URL**
2. Enter your proxy URL: `https://your-proxy.com/`
3. The proxy will be applied to all video requests

#### Playback Settings
- **Loop Video** - Enable continuous playback
- **Playback Speed** - Adjust from 0.5x to 2x speed
- **Volume Control** - Fine-tune audio levels
- **Quality Selection** - Automatic quality adaptation

## ⌨️ Keyboard Shortcuts

| Shortcut | Action | Description |
|----------|--------|-------------|
| `Space` | Play/Pause | Toggle video playback |
| `←` | Seek Backward | Jump back 5 seconds |
| `→` | Seek Forward | Jump forward 5 seconds |
| `↑` | Volume Up | Increase volume by 10% |
| `↓` | Volume Down | Decrease volume by 10% |
| `F` | Fullscreen | Toggle fullscreen mode |
| `M` | Mute | Toggle audio mute |
| `T` | Theater Mode | Toggle theater view |

## 🔧 API Endpoints

### Terabox Resolution API
```http
GET /?resolve&url={terabox_url}
```

**Request Example:**
```bash
curl "http://localhost:8000/?resolve&url=https%3A%2F%2F1024terabox.com%2Fs%2Fyour-link"
```

**Response Format:**
```json
{
  "success": true,
  "url": "https://direct-video-url.mp4"
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Resolution failed",
  "errors": [
    "Direct method: No video URL found",
    "Proxy errors..."
  ]
}
```

### Video Download API
```http
GET /?download&url={video_url}
```

**Features:**
- Resumable downloads with range requests
- Progress tracking support
- Automatic filename detection
- Error handling and validation

## 🏗️ Architecture

### Resolution Methods

#### 1. Direct Server-Side Resolution
- Direct HTML scraping with enhanced patterns
- Multiple regex patterns for video URL extraction
- Advanced User-Agent simulation
- JSON data extraction from script tags

#### 2. Proxy-Based Resolution
- Multiple CORS proxy fallbacks
- Automatic proxy rotation on failure
- Enhanced error handling and reporting
- Network timeout management

#### 3. Client-Side Fallback
- Browser-based resolution when server fails
- CORS proxy utilization
- Real-time error feedback
- Seamless fallback integration

### Technology Stack

- **Backend:** PHP 8.0+ with cURL
- **Frontend:** Vanilla JavaScript ES6+
- **Styling:** Self-contained CSS with utility classes
- **Icons:** FontAwesome with emoji fallbacks
- **Video:** HTML5 Video API with custom controls

## 🛡️ Security & Privacy

### Data Protection
- **No Data Collection** - Zero personal data storage
- **Local Storage Only** - Settings stored in browser
- **No External Tracking** - No analytics or tracking scripts
- **Open Source** - Full transparency with GPL-3.0 license

### Security Measures
- Input validation and sanitization
- CORS protection with configurable proxies
- Secure cURL configurations
- XSS protection in all user inputs

## 🌐 Browser Compatibility

| Browser | Version | Support Level |
|---------|---------|---------------|
| Chrome | 60+ | ✅ Full Support |
| Firefox | 55+ | ✅ Full Support |
| Safari | 11+ | ✅ Full Support |
| Edge | 79+ | ✅ Full Support |
| Mobile Safari | 11+ | ✅ Full Support |
| Chrome Mobile | 60+ | ✅ Full Support |
| Opera | 47+ | ✅ Full Support |

### Feature Support
- **HTML5 Video** - Full support across all browsers
- **Fullscreen API** - Supported in modern browsers
- **Picture-in-Picture** - Chrome 70+, Safari 13.1+
- **Keyboard Events** - Universal support
- **Local Storage** - Universal support

## 🧪 Development

### Project Structure
```
VibePlayer/
├── index.php              # Main application file
├── README.md              # Project documentation
├── LICENSE               # GPL-3.0 license
├── .gitignore           # Git ignore rules
└── screenshots/         # UI screenshots
```

### Code Architecture
```php
// PHP Backend
├── Terabox Resolution Logic
├── CORS Proxy Handling
├── Video Download API
└── Error Management

// JavaScript Frontend
├── Player Module
├── UI Management
├── Theme System
├── Settings Persistence
└── Keyboard Controls
```

### Development Setup

```bash
# Clone repository
git clone https://github.com/Chauhan-Mukesh/VibePlayer.git
cd VibePlayer

# Start development server
php -S localhost:8000 index.php

# Open in browser
open http://localhost:8000
```

### Code Style Guidelines

- **PHP**: PSR-12 coding standard
- **JavaScript**: ES6+ with modern features
- **HTML/CSS**: Semantic markup with utility classes
- **Comments**: Comprehensive documentation

### Testing

#### Manual Testing
```bash
# Test Terabox resolution
curl "http://localhost:8000/?resolve&url=https%3A%2F%2F1024terabox.com%2Fs%2Ftest"

# Test video download
curl "http://localhost:8000/?download&url=https%3A%2F%2Fexample.com%2Fvideo.mp4"
```

#### UI Testing
- Theme switching functionality
- Keyboard shortcut responsiveness
- Settings persistence
- Mobile responsiveness
- Player control functionality

## 🤝 Contributing

We welcome contributions! Here's how to get started:

### Getting Started
1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes (`git commit -m 'Add amazing feature'`)
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

### Contribution Guidelines
- Follow existing code style and conventions
- Add comprehensive comments for new functionality
- Test thoroughly across different browsers
- Update documentation for new features
- Ensure mobile responsiveness

### Areas for Contribution
- 🎵 Audio-only playback mode
- 📱 Mobile app development
- 🌍 Internationalization (i18n)
- 🎨 Custom theme creation
- 📊 Analytics dashboard
- 🔐 User account system
- 📺 Chromecast support

## 🆘 Troubleshooting

### Common Issues

#### Video Won't Load
```
Solution Steps:
1. Verify the URL is a direct video link
2. Check if Terabox link is valid and accessible
3. Try using a custom CORS proxy in settings
4. Ensure the video format is supported (MP4, WebM, etc.)
5. Check browser console for detailed error messages
```

#### Terabox Links Not Working
```
Possible Causes:
- Link may require login (not supported)
- Temporary server issues
- Rate limiting by Terabox
- Network restrictions

Solutions:
- Try a different Terabox link
- Wait and retry later
- Check browser console for specific errors
- Verify link format is correct
```

#### Controls Not Responding
```
Quick Fixes:
1. Ensure JavaScript is enabled in browser
2. Clear browser cache and reload page
3. Try a different browser
4. Check for browser compatibility
5. Disable browser extensions that might interfere
```

#### Performance Issues
```
Optimization Tips:
1. Close unnecessary browser tabs
2. Ensure stable internet connection
3. Try lower video quality if available
4. Clear browser cache and cookies
5. Update browser to latest version
```

### Getting Help

- 🐛 **Bug Reports**: [GitHub Issues](https://github.com/Chauhan-Mukesh/VibePlayer/issues)
- 💡 **Feature Requests**: [GitHub Discussions](https://github.com/Chauhan-Mukesh/VibePlayer/discussions)
- 📧 **Support**: Create an issue with detailed information
- 💬 **Community**: Join discussions in GitHub Discussions

## 📄 License

This project is licensed under the **GNU General Public License v3.0** - see the [LICENSE](LICENSE) file for details.

### License Summary
- ✅ **Commercial Use** - Use in commercial projects
- ✅ **Modification** - Modify and adapt the code
- ✅ **Distribution** - Share and distribute copies
- ✅ **Private Use** - Use for personal projects
- ⚠️ **Copyleft** - Derivative works must use same license
- ⚠️ **Disclose Source** - Source code must be made available

## 🙏 Acknowledgments

### Technologies
- **PHP** - Server-side processing and API handling
- **HTML5 Video API** - Modern video playback capabilities
- **Vanilla JavaScript** - Lightweight, fast frontend
- **CSS Grid & Flexbox** - Modern layout systems

### Inspiration
- Modern video players like YouTube and Vimeo
- Open source media players
- Community feedback and feature requests
- Accessibility standards and best practices

### Contributors
Special thanks to all contributors who have helped improve Vibe Player:

- [@Chauhan-Mukesh](https://github.com/Chauhan-Mukesh) - Project Creator & Maintainer
- Community contributors and testers
- Bug reporters and feature requesters

## 🎯 Roadmap

### Version 2.1 (Next Release)
- 🎬 **Playlist Support** - Queue and manage multiple videos
- 🔄 **Auto-Resolution** - Automatic quality switching based on connection
- 📺 **Chromecast Support** - Cast videos to TV devices
- 🎵 **Audio-only Mode** - Extract and play audio tracks

### Version 2.2 (Future)
- 🌍 **Multi-language Support** - Internationalization
- 🔐 **Optional User Accounts** - Cloud sync for settings and history
- 📊 **Analytics Dashboard** - Detailed usage statistics
- 🎨 **Custom Themes** - User-created color schemes

### Version 3.0 (Long-term)
- 📱 **Mobile Apps** - Native iOS and Android applications
- 🤖 **AI Features** - Smart recommendations and auto-categorization
- 🎮 **VR Support** - Virtual reality video playback
- 🔗 **Social Features** - Sharing and collaborative playlists

---

<div align="center">

**Built with ❤️ for the open source community**

[⭐ Star this repo](https://github.com/Chauhan-Mukesh/VibePlayer) • [🐛 Report Bug](https://github.com/Chauhan-Mukesh/VibePlayer/issues) • [💡 Request Feature](https://github.com/Chauhan-Mukesh/VibePlayer/discussions) • [📖 Documentation](https://github.com/Chauhan-Mukesh/VibePlayer/wiki)

**Stay Connected**
- 🐦 [Follow Updates](https://twitter.com/VibePlayer)
- 💬 [Join Discord](https://discord.gg/vibeplayer)
- 📧 [Newsletter](https://vibeplayer.dev/newsletter)

</div>