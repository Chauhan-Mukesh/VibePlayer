# VibePlayer Health Check Documentation

## Overview
The VibePlayer application includes a robust health check system designed to work in various environments, including restrictive build environments with network monitoring.

## Health Check Components

### 1. HTTP Health Endpoint (`/?health`)
- **URL**: `http://your-domain/?health`
- **Method**: GET
- **Response**: JSON with health status and system information
- **Usage**: External monitoring, load balancers, API clients

### 2. Local Health Check Script (`healthcheck.php`)
- **Path**: `/var/www/html/healthcheck.php`
- **Usage**: Docker health checks, internal monitoring
- **Method**: Direct PHP execution (no network calls)
- **Benefits**: Works in restricted environments, faster execution

## Health Check Features

### Checks Performed
- ✅ **File Integrity**: Verifies critical files exist and have valid PHP syntax
- ✅ **Cache Directory**: Ensures cache directory exists and is writable
- ✅ **PHP Configuration**: Validates PHP settings (container only)
- ✅ **Apache Configuration**: Checks Apache config (container only)
- ✅ **JSON Functionality**: Tests PHP JSON encoding/decoding
- ✅ **File System Operations**: Verifies read/write capabilities
- ✅ **Process Status**: Checks Apache process status (container only)

### Response Format
```json
{
  "status": "healthy|unhealthy",
  "timestamp": 1754651120,
  "checks": {
    "index_file": true,
    "index_syntax": true,
    "htaccess_file": true,
    "cache_dir": true,
    "php_config": true,
    "apache_config": true,
    "php_json": true,
    "filesystem": true,
    "apache_process": true
  },
  "method": "local_checks|http_endpoint"
}
```

## Docker Health Check

The Docker health check uses the local script to avoid network restrictions:

```dockerfile
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD php /var/www/html/healthcheck.php || exit 1
```

### Benefits of Local Health Check
- **No Network Dependencies**: Works in restricted build environments
- **Faster Execution**: Direct PHP execution without HTTP overhead
- **Build-Safe**: Avoids DNS monitoring proxy issues
- **Comprehensive**: Tests core functionality without external dependencies

## Usage Examples

### Check Container Health
```bash
# Docker health status
docker ps --format "table {{.Names}}\t{{.Status}}"

# Manual health check
docker exec container-name php /var/www/html/healthcheck.php

# HTTP endpoint check
curl -f http://localhost/?health
```

### Integration with Monitoring
```bash
# Use in monitoring scripts
if curl -f http://your-domain/?health > /dev/null 2>&1; then
    echo "Service is healthy"
else
    echo "Service is unhealthy"
fi
```

## Troubleshooting

### Common Issues
1. **Permission Errors**: Ensure healthcheck.php is executable
2. **Cache Directory**: Verify /tmp/vibeplayercache exists and is writable
3. **PHP Syntax**: Check for syntax errors in index.php

### Debug Commands
```bash
# Test health check locally
php healthcheck.php

# Check file permissions
ls -la /var/www/html/healthcheck.php

# Verify cache directory
ls -la /tmp/vibeplayercache
```

## Environment Compatibility

### Container Environment
- Full health checks including Apache and PHP configuration
- Process status monitoring
- Optimized for production deployment

### Local Development
- Adapted checks for local file paths
- Skips container-specific validations
- Works with PHP built-in server

### Build Environment
- No network calls to avoid DNS monitoring issues
- File-based checks only
- Fast and reliable execution