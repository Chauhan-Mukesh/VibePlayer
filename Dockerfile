# SPDX-License-Identifier: GPL-3.0-or-later
# (c) 2025 Chauhan-Mukesh
#
# Production-ready Dockerfile for Vibe Player
# Uses php:8.2-apache for optimal performance and Range header support

FROM php:8.2-apache

LABEL maintainer="Chauhan-Mukesh <70336897+Chauhan-Mukesh@users.noreply.github.com>"
LABEL description="Vibe Player - The Ultimate Open Source Streaming Hub"
LABEL version="2.0.0"
LABEL org.opencontainers.image.source="https://github.com/Chauhan-Mukesh/VibePlayer"
LABEL org.opencontainers.image.licenses="GPL-3.0-or-later"

# Install system dependencies and PHP extensions in single layer
# Using parallel compilation and explicit configuration for faster, more reliable builds
RUN echo "=== Installing system dependencies ===" \
    && apt-get update && apt-get install -y --no-install-recommends \
    curl \
    ca-certificates \
    gnupg \
    libcurl4-openssl-dev \
    libzip-dev \
    zip \
    unzip \
    zlib1g-dev \
    libpng-dev \
    libonig-dev \
    # Additional build dependencies for faster and more reliable compilation
    build-essential \
    autoconf \
    pkg-config \
    && echo "=== Configuring PHP extensions ===" \
    && docker-php-ext-configure bcmath --enable-bcmath \
    && echo "=== Installing PHP extensions with parallel compilation ===" \
    && docker-php-ext-install -j$(nproc) \
    zip \
    exif \
    bcmath \
    && echo "=== Cleaning up package cache ===" \
    && rm -rf /var/lib/apt/lists/* \
    && echo "=== PHP extensions installed successfully ==="

# Enable required Apache modules for production
RUN a2enmod rewrite headers expires deflate

# Create non-root user for security
RUN groupadd -r vibe && useradd -r -g vibe vibe

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY --chown=vibe:vibe index.php .
COPY --chown=vibe:vibe .htaccess .
COPY --chown=vibe:vibe README.md .
COPY --chown=vibe:vibe LICENSE .
COPY --chown=vibe:vibe LICENSE-README.md .

# Create cache directory with proper permissions
RUN mkdir -p /tmp/vibeplayercache \
    && chown -R vibe:vibe /tmp/vibeplayercache \
    && chmod 755 /tmp/vibeplayercache

# Create PHP ini file with optimized configuration
RUN echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/vibeplayer.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/vibeplayer.ini \
    && echo "post_max_size = 512M" >> /usr/local/etc/php/conf.d/vibeplayer.ini \
    && echo "upload_max_filesize = 512M" >> /usr/local/etc/php/conf.d/vibeplayer.ini \
    && echo "allow_url_fopen = On" >> /usr/local/etc/php/conf.d/vibeplayer.ini \
    && echo "curl.cainfo = /etc/ssl/certs/ca-certificates.crt" >> /usr/local/etc/php/conf.d/vibeplayer.ini

# Set proper ownership for web content
RUN chown -R vibe:vibe /var/www/html

# Configure Apache DocumentRoot and security
RUN echo "ServerTokens Prod" >> /etc/apache2/apache2.conf \
    && echo "ServerSignature Off" >> /etc/apache2/apache2.conf \
    && echo "TraceEnable Off" >> /etc/apache2/apache2.conf

# Expose port 80 for production
EXPOSE 80

# Health check for container monitoring
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -fsS http://localhost/?health || exit 1

# Use non-root user for runtime security
USER vibe

# Start Apache in foreground
CMD ["apache2-foreground"]