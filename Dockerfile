FROM debian:12-slim

ENV DEBIAN_FRONTEND=noninteractive

# Install MariaDB + PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    mariadb-server \
    php php-mysql php-gd php-mbstring php-xml php-curl \
    curl python3 \
    && rm -rf /var/lib/apt/lists/*

# Copy application
COPY . /workspace
WORKDIR /workspace

# Create upload directory
RUN mkdir -p /workspace/assets/uploads && chmod 777 /workspace/assets/uploads

# Entrypoint script
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

EXPOSE 80

CMD ["/docker-entrypoint.sh"]
