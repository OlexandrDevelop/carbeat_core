FROM node:18-alpine

WORKDIR /usr/src/app

# Install dependencies
COPY package.json package-lock.json* ./
# Use npm ci when lockfile exists; otherwise fallback to npm install
RUN if [ -f package-lock.json ]; then \
      npm ci --omit=dev; \
    else \
      npm install --omit=dev; \
    fi

# Copy source
COPY server.js ./

EXPOSE 3000

CMD ["node", "server.js"]


