# 🎬 VibePlayer

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4.svg)](https://php.net/)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6%2B-F7DF1E.svg)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![HTML5](https://img.shields.io/badge/HTML5-Video-E34F26.svg)](https://developer.mozilla.org/en-US/docs/Web/Guide/HTML/HTML5)

> **The Ultimate Open Source Streaming Hub** - A feature-rich, modern video player for streaming direct video links with advanced capabilities.

## ✨ Features

### 🎥 **Core Video Features**
- **Direct Video Streaming** - Play any direct video link instantly
- **Terabox Link Resolution** - Automatically resolves Terabox links without login
- **Multiple Format Support** - MP4, WebM, and other HTML5 compatible formats
- **Adaptive Quality** - Automatic quality selection based on connection

### 🎨 **User Interface**
- **Modern Design** - Clean, responsive interface built with Tailwind CSS
- **Light & Dark Themes** - Seamless theme switching with system preference detection
- **Mobile Responsive** - Optimized for all screen sizes and devices
- **Accessibility** - Full keyboard navigation and screen reader support

### ⌨️ **Controls & Shortcuts**
- **Keyboard Shortcuts** - Full keyboard control for power users
  - `Space` - Play/Pause
  - `←/→` - Seek backward/forward (5s)
  - `↑/↓` - Volume up/down
  - `F` - Toggle fullscreen
  - `M` - Toggle mute
  - `T` - Toggle theater mode
- **Custom Controls** - Advanced video control interface
- **Speed Control** - Playback speed from 0.5x to 2x

### 🎯 **Advanced Features**
- **Multiple View Modes**
  - Theater Mode - Distraction-free viewing
  - Picture-in-Picture - Multitask while watching
  - Fullscreen - Immersive experience
- **Video Download** - Download videos with progress tracking
- **Playback History** - Keep track of recently played videos
- **Settings Persistence** - Save preferences locally
- **CORS Proxy Support** - Bypass streaming restrictions

### 🔧 **Technical Features**
- **No Login Required** - Works with Terabox links without authentication
- **Multiple Proxy Fallbacks** - Enhanced reliability for link resolution
- **SEO Optimized** - Complete meta tags and structured data
- **Progressive Enhancement** - Works even when external resources fail
- **Error Handling** - Comprehensive error management with user feedback

## 📸 Screenshots

### Light Mode
![Light Mode](https://github.com/user-attachments/assets/0a5f8a40-44dc-4fac-910b-0960647579c5)

### Dark Mode
![Dark Mode](https://github.com/user-attachments/assets/5e8d486a-9bed-443e-b6c0-790046a5b3ce)

## 🚀 Quick Start

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/Chauhan-Mukesh/VibePlayer.git
   cd VibePlayer
   ```

2. **Start PHP development server**
   ```bash
   php -S localhost:8000 index.php
   ```

3. **Open in browser**
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

## 📖 Usage

### Basic Video Playback

1. **Paste URL** - Enter any direct video link in the input field
2. **Click Play** - Press the play button or hit Enter
3. **Enjoy** - Use controls or keyboard shortcuts to manage playback

### Terabox Links

Simply paste a Terabox link - no additional configuration needed:
```
https://1024terabox.com/s/your-link-here
https://terabox.com/s/your-link-here
```

### YouTube Links

YouTube links are supported with automatic resolution:
```
https://www.youtube.com/watch?v=VIDEO_ID
https://youtu.be/VIDEO_ID
```

### Advanced Settings

- **Loop Video** - Enable continuous playback
- **Playback Speed** - Adjust speed from 0.5x to 2x
- **CORS Proxy** - Configure custom proxy for restricted content
- **Theme** - Switch between light and dark modes

## ⌨️ Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Space` | Play/Pause video |
| `←` | Seek backward 5 seconds |
| `→` | Seek forward 5 seconds |
| `↑` | Increase volume |
| `↓` | Decrease volume |
| `F` | Toggle fullscreen |
| `M` | Toggle mute |
| `T` | Toggle theater mode |

## 🔧 Configuration

### Environment Variables

Create a `.env` file for custom configuration:

```env
# Default proxy URL
DEFAULT_PROXY_URL=https://your-proxy.com/

# Maximum video file size (MB)
MAX_VIDEO_SIZE=500

# Cache duration (seconds)
CACHE_DURATION=3600
```

### Custom Proxies

For restricted content, configure custom CORS proxies:

1. Open **Settings** → **CORS Proxy URL**
2. Enter your proxy URL (e.g., `https://my-proxy.com/`)
3. The proxy will be applied to all video requests

## 🛠️ API Endpoints

### Terabox Resolution
```http
GET /?resolve&url={terabox_url}
```

**Response:**
```json
{
  "success": true,
  "url": "https://direct-video-link.mp4"
}
```

### Video Download
```http
GET /?download&url={video_url}
```

Returns the video file with proper headers for download.

## 🔒 Privacy & Security

- **No Data Collection** - VibePlayer doesn't collect personal data
- **Local Storage Only** - Settings and history stored locally
- **No External Tracking** - No analytics or tracking scripts
- **Open Source** - Full transparency with GPL-3.0 license

## 🌐 Browser Support

| Browser | Version | Status |
|---------|---------|--------|
| Chrome | 60+ | ✅ Full Support |
| Firefox | 55+ | ✅ Full Support |
| Safari | 11+ | ✅ Full Support |
| Edge | 79+ | ✅ Full Support |
| Mobile Safari | 11+ | ✅ Full Support |
| Chrome Mobile | 60+ | ✅ Full Support |

## 🧪 Development

### Prerequisites

- PHP 8.0 or higher
- Modern web browser
- Basic understanding of HTML/CSS/JavaScript

### Project Structure

```
VibePlayer/
├── index.php          # Main application file
├── README.md          # Documentation
├── LICENSE           # GPL-3.0 license
└── .gitignore        # Git ignore rules
```

### Code Style

- **PHP**: PSR-12 coding standard
- **JavaScript**: ES6+ with modern features
- **HTML/CSS**: Semantic markup with Tailwind CSS
- **Comments**: Comprehensive JSDoc documentation

### Testing

```bash
# Test Terabox resolution
curl "http://localhost:8000/?resolve&url=https%3A%2F%2F1024terabox.com%2Fs%2Ftest"

# Test video download
curl "http://localhost:8000/?download&url=https%3A%2F%2Fexample.com%2Fvideo.mp4"
```

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes (`git commit -m 'Add amazing feature'`)
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

### Contribution Guidelines

- Follow existing code style
- Add tests for new features
- Update documentation
- Ensure cross-browser compatibility

## 📝 Changelog

### Version 2.0.0 (Latest)
- ✨ Complete UI/UX redesign
- 🎯 Enhanced Terabox resolution with multiple fallbacks
- 🎨 Improved light/dark theme system
- ⌨️ Comprehensive keyboard shortcuts
- 📱 Mobile-responsive design
- 🔧 Advanced settings panel
- 📥 Video download with progress tracking
- 🕐 Playback history feature
- 🛡️ Enhanced error handling
- 📚 Complete code documentation

### Version 1.0.0
- 🎬 Basic video playback functionality
- 🔗 Simple Terabox link resolution
- 🎨 Basic UI implementation

## 🆘 Troubleshooting

### Common Issues

**Video won't load**
- Check if the URL is a direct video link
- Try using a custom CORS proxy
- Ensure the video format is supported

**Terabox links not working**
- The link resolver tries multiple methods
- Check browser console for error details
- Try a different Terabox link

**Controls not responding**
- Ensure JavaScript is enabled
- Clear browser cache and reload
- Check for browser compatibility

### Getting Help

- 🐛 **Bug Reports**: [GitHub Issues](https://github.com/Chauhan-Mukesh/VibePlayer/issues)
- 💡 **Feature Requests**: [GitHub Discussions](https://github.com/Chauhan-Mukesh/VibePlayer/discussions)
- 📧 **Contact**: Create an issue for support

## 📄 License

This project is licensed under the **GNU General Public License v3.0** - see the [LICENSE](LICENSE) file for details.

### What this means:
- ✅ **Use** - Use the software for any purpose
- ✅ **Study** - Examine and learn from the source code
- ✅ **Modify** - Make changes and improvements
- ✅ **Share** - Distribute copies and modifications
- ⚠️ **Copyleft** - Derivative works must use the same license

## 🙏 Acknowledgments

- **Tailwind CSS** - For the amazing utility-first CSS framework
- **FontAwesome** - For the comprehensive icon library
- **HTML5 Video** - For modern video capabilities
- **Open Source Community** - For inspiration and contributions

## 🎯 Roadmap

### Upcoming Features
- 🎬 **Playlist Support** - Queue multiple videos
- 🔄 **Auto-Resolution** - Automatic quality switching
- 📺 **Chromecast Support** - Cast to TV devices
- 🎵 **Audio-only Mode** - Extract and play audio
- 🌍 **Multi-language** - Internationalization support
- 🔐 **User Accounts** - Optional cloud sync
- 📊 **Analytics Dashboard** - Usage statistics
- 🎨 **Custom Themes** - User-created color schemes

---

<div align="center">

**Built with ❤️ for the open source community**

[⭐ Star this repo](https://github.com/Chauhan-Mukesh/VibePlayer) • [🐛 Report Bug](https://github.com/Chauhan-Mukesh/VibePlayer/issues) • [💡 Request Feature](https://github.com/Chauhan-Mukesh/VibePlayer/discussions)

</div>