# Vibe Player Dockerfile
FROM php:8.2-cli

LABEL maintainer="Chauhan-Mukesh <70336897+Chauhan-Mukesh@users.noreply.github.com>"
LABEL description="Vibe Player - The Ultimate Open Source Streaming Hub"
LABEL version="2.0.0"

# Install required PHP extensions
RUN apt-get update && apt-get install -y \
    curl \
    && docker-php-ext-install \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /app

# Copy application files
COPY index.php .
COPY README.md .
COPY LICENSE .

# Expose port
EXPOSE 8000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8000/ || exit 1

# Start PHP development server
CMD ["php", "-S", "0.0.0.0:8000", "index.php"]