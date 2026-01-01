# SEO and Performance Recommendations

This file contains recommendations for improving SEO and performance that require server-level configuration changes.

## High Priority

### URL Canonicalization

While a canonical tag has been added to the main layout, it is also recommended to enforce a primary URL at the server level. For example, you can redirect all traffic to the `www` version of your domain (or non-www, whichever you prefer).

**Nginx Example:**

```nginx
server {
    server_name example.com;
    return 301 $scheme://www.example.com$request_uri;
}
```

## Low Priority

### HTML Compression

Enable Gzip or Brotli compression on your web server to reduce the size of the HTML, CSS, and JavaScript files sent to the user.

**Nginx Example (gzip):**

```nginx
gzip on;
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
```

### Strict-Transport-Security (HSTS) Header

To enhance security, add the `Strict-Transport-Security` header to force all connections over HTTPS.

**Nginx Example:**

```nginx
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
```

