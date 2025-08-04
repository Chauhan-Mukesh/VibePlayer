# VibePlayer

The Ultimate Open Source Streaming Hub - A feature-rich, modern video player for streaming direct video links with advanced capabilities.

## Features

🎥 **Direct Video Streaming** - Play direct video links instantly
🔧 **Terabox Link Resolution** - Automatically resolves Terabox links with proxy support
🎨 **Beautiful UI** - Modern, responsive design with light/dark themes
⌨️ **Keyboard Shortcuts** - Full keyboard control for seamless playback
📱 **Multiple View Modes** - Theater, Picture-in-Picture, and Fullscreen modes
⚡ **Playback Controls** - Speed control, loop, volume, and progress seeking
📥 **Video Download** - Download videos with progress tracking
📚 **History Tracking** - Keep track of your recently played videos
🛠️ **CORS Proxy Support** - Bypass streaming restrictions with custom proxies
🎯 **SEO Optimized** - Full meta tags and structured data for search engines

## Screenshots

### Light Mode
![Light Mode](https://github.com/user-attachments/assets/7365a6ad-f0dd-4cbf-8413-3df69e82fcc1)

### Dark Mode  
![Dark Mode](https://github.com/user-attachments/assets/ef9b6d16-2f56-4d58-8801-3ed12d9e59d9)

## Installation

1. Clone this repository:
```bash
git clone https://github.com/Chauhan-Mukesh/VibePlayer.git
```

2. Navigate to the project directory:
```bash
cd VibePlayer
```

3. Start a PHP server:
```bash
php -S localhost:8000 index.php
```

4. Open your browser and visit `http://localhost:8000`

## Usage

### Basic Video Playback
1. Paste a direct video link in the input field
2. Click the play button or press Enter
3. Use the video controls to manage playback

### Terabox Link Resolution
- Simply paste a Terabox link - it will be automatically resolved
- Multiple proxy servers are tried for maximum success rate

### Keyboard Shortcuts
- `Space` - Play/Pause
- `←` - Seek backward 5 seconds  
- `→` - Seek forward 5 seconds
- `↑` - Volume up
- `↓` - Volume down
- `F` - Toggle fullscreen
- `M` - Toggle mute

### Settings & Features
- **Loop Video**: Enable continuous playback
- **Playback Speed**: Adjust from 0.5x to 2x speed
- **CORS Proxy**: Configure custom proxy for restricted content
- **Theme Toggle**: Switch between light and dark modes
- **History**: Access recently played videos

## API Endpoints

### Terabox Resolution
```
GET /?resolve&url=<terabox_url>
```
Returns JSON with resolved video URL or error message.

### Video Download
```
GET /?download&url=<video_url>
```
Streams the video file for download with proper headers.

## Technical Features

- **PHP Backend** - Handles Terabox resolution and video downloads
- **Responsive Design** - Works on desktop and mobile devices
- **Progressive Enhancement** - Graceful degradation for older browsers
- **Error Handling** - Comprehensive error management with user feedback
- **Local Storage** - Persistent settings and history
- **Toast Notifications** - User-friendly feedback system

## Browser Support

- Chrome/Chromium (recommended)
- Firefox
- Safari
- Edge
- Mobile browsers

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Credits

Built with ❤️ for the open source community.