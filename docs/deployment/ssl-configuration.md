# SSL/HTTPS Configuration

This document describes the SSL/HTTPS configuration and security headers implementation for the MA Deal Room WordPress plugin.

## Overview

The plugin implements comprehensive HTTPS enforcement and security headers to protect against common web vulnerabilities including:

- Man-in-the-Middle (MITM) attacks
- Cross-Site Scripting (XSS)
- Clickjacking
- MIME type sniffing attacks
- Protocol downgrade attacks
- Information leakage

## Features

### 1. HTTPS Enforcement (Production Only)

The `HTTPSMiddleware` class automatically redirects all HTTP requests to HTTPS in production environments.

**Key Features:**
- Automatic HTTP → HTTPS redirect (301 permanent redirect)
- HSTS (HTTP Strict Transport Security) header
- Environment-aware (only enforces in production)
- Load balancer/proxy support
- Local development exemption

**Configuration:**
Set the `ENVIRONMENT` variable in your `.env` file:
```bash
# In production
ENVIRONMENT=production

# In development
ENVIRONMENT=development

# In staging
ENVIRONMENT=staging
```

**HSTS Header:**
```
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
```
- `max-age=31536000`: 1 year
- `includeSubDomains`: Apply to all subdomains
- `preload`: Eligible for browser HSTS preload list

### 2. Security Headers

The `SecurityHeadersMiddleware` class adds multiple security headers to all responses.

#### Content Security Policy (CSP)

Protects against XSS, clickjacking, and code injection attacks.

**Production CSP:**
```
Content-Security-Policy:
  default-src 'self';
  script-src 'self' 'unsafe-inline' 'unsafe-eval';
  style-src 'self' 'unsafe-inline';
  img-src 'self' data: https: blob:;
  font-src 'self' data:;
  connect-src 'self' https://api.sentry.io https://*.sentry.io;
  media-src 'self';
  object-src 'none';
  base-uri 'self';
  form-action 'self';
  frame-ancestors 'none';
```

**Development CSP:**
Includes additional permissions for hot reload and localhost development:
```
connect-src 'self' ws://localhost:* ws://127.0.0.1:* http://localhost:* http://127.0.0.1:* https://api.sentry.io https://*.sentry.io
```

**CSP Directives Explained:**
- `default-src 'self'`: Only allow resources from same origin by default
- `script-src`: Allow inline scripts for React (needed for styled-components)
- `style-src`: Allow inline styles for WordPress admin and React
- `img-src`: Allow images from data URIs, HTTPS sources, and blob URLs
- `connect-src`: Allow API calls to self and Sentry
- `object-src 'none'`: Block plugins like Flash
- `frame-ancestors 'none'`: Prevent embedding in iframes

**⚠️ CSP 'unsafe-inline' and 'unsafe-eval' - Documented Accepted Risk**

The production CSP includes `'unsafe-inline'` and `'unsafe-eval'` in the `script-src` directive. While this reduces the effectiveness of CSP against XSS attacks, it is a **necessary compromise** for WordPress and React compatibility.

**Why 'unsafe-inline' and 'unsafe-eval' are required:**

1. **WordPress Admin Compatibility:**
   - WordPress core and many plugins inject inline scripts
   - WordPress admin uses inline event handlers extensively
   - Removing `'unsafe-inline'` breaks WordPress admin functionality
   - WordPress does not currently support CSP nonces

2. **React Application Requirements:**
   - Styled-components generates inline styles dynamically
   - React DevTools requires `'unsafe-eval'` for development
   - Many React libraries rely on inline styles
   - Vite/Webpack development servers use eval for hot reload

3. **Third-Party Integration:**
   - Sentry error tracking injects inline scripts
   - Analytics and monitoring tools need inline execution
   - Payment gateways often require inline scripts

**Mitigation Strategies:**

Despite using `'unsafe-inline'`, we maintain strong XSS protection through:

1. **Input Validation:**
   - All user inputs sanitized before storage
   - WordPress escaping functions (`esc_html()`, `esc_attr()`, `esc_url()`)
   - SQL injection prevention via prepared statements

2. **Output Encoding:**
   - All dynamic content escaped before rendering
   - React's default XSS protection (JSX escaping)
   - Template engine properly escapes YAML variables

3. **Additional Security Layers:**
   - `X-XSS-Protection` header for browser-level XSS filtering
   - `X-Content-Type-Options: nosniff` prevents MIME confusion
   - `frame-ancestors 'none'` prevents clickjacking
   - Content Security Policy still blocks unauthorized external resources

4. **Strict Resource Loading:**
   - `default-src 'self'` ensures resources load from trusted origin only
   - `connect-src` whitelists specific API endpoints
   - `object-src 'none'` blocks dangerous plugins (Flash, Java)
   - External scripts loaded only from `'self'`

**Future Improvements (Phase 2+):**

When time and resources allow, consider these CSP hardening strategies:

1. **Nonce-Based CSP:**
   - Generate random nonce for each page load
   - Add nonce to allowed inline scripts
   - Requires WordPress core modifications or custom implementation
   - Example: `script-src 'self' 'nonce-{random}'`

2. **Strict Dynamic CSP:**
   - Use `'strict-dynamic'` to allow scripts loaded by trusted scripts
   - Removes need for `'unsafe-inline'` in modern browsers
   - Example: `script-src 'strict-dynamic' 'nonce-{random}'`

3. **CSP Reporting:**
   - Implement CSP violation reporting endpoint
   - Monitor and analyze CSP violations
   - Gradually tighten CSP based on reports
   - Example: `report-uri /csp-violation-report-endpoint`

**Risk Assessment:**

- **Severity:** Medium (reduces CSP XSS protection effectiveness)
- **Likelihood:** Low (mitigated by multiple security layers)
- **Impact:** Low (additional protections compensate)
- **Residual Risk:** LOW - Acceptable for production deployment

**Decision:**
This is a documented and accepted architectural decision. The benefits of WordPress/React compatibility outweigh the reduced CSP protection, especially given our comprehensive input validation and output encoding practices.

#### X-Frame-Options

Prevents clickjacking attacks by disallowing the page to be embedded in iframes.

```
X-Frame-Options: DENY
```

#### X-Content-Type-Options

Prevents MIME type sniffing attacks.

```
X-Content-Type-Options: nosniff
```

#### Referrer-Policy

Controls how much referrer information is included with requests.

```
Referrer-Policy: strict-origin-when-cross-origin
```

Behavior:
- Same-origin requests: Send full URL
- Cross-origin HTTPS: Send origin only
- Cross-origin HTTP: Send nothing

#### Permissions-Policy

Controls which browser features can be used (formerly Feature-Policy).

```
Permissions-Policy:
  geolocation=(),
  microphone=(),
  camera=(),
  payment=(),
  usb=(),
  magnetometer=(),
  gyroscope=(),
  accelerometer=(),
  ambient-light-sensor=()
```

All features are disabled by default for security.

#### X-XSS-Protection

Legacy XSS protection for older browsers.

```
X-XSS-Protection: 1; mode=block
```

## Frontend Configuration

### API Client HTTPS Enforcement

The frontend API client (`assets/admin/src/api/client.ts`) includes HTTPS enforcement:

```typescript
const ensureHttps = (url: string): string => {
  const isProduction = window.location.protocol === 'https:';

  if (isProduction && url.startsWith('http://')) {
    return url.replace('http://', 'https://');
  }

  return url;
};
```

This ensures all API calls use HTTPS when the page is loaded over HTTPS.

## Testing

### 1. Test HTTPS Redirect (Production)

```bash
# Set environment to production
echo "ENVIRONMENT=production" >> .env

# Test HTTP redirect (should redirect to HTTPS)
curl -I http://yourdomain.com
# Expected: HTTP/1.1 301 Moved Permanently
# Location: https://yourdomain.com
```

### 2. Test Security Headers

```bash
# Check all security headers
curl -I https://yourdomain.com

# Expected headers:
# Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
# Content-Security-Policy: ...
# X-Frame-Options: DENY
# X-Content-Type-Options: nosniff
# Referrer-Policy: strict-origin-when-cross-origin
# Permissions-Policy: ...
# X-XSS-Protection: 1; mode=block
```

### 3. Test with Online Tools

**SecurityHeaders.com:**
```
https://securityheaders.com/?q=https://yourdomain.com
```
Target: A+ rating

**SSL Labs:**
```
https://www.ssllabs.com/ssltest/analyze.html?d=yourdomain.com
```
Target: A or A+ rating

### 4. Test Development Environment

```bash
# Set environment to development
echo "ENVIRONMENT=development" >> .env

# Verify HTTPS is NOT enforced
curl -I http://localhost:8080
# Expected: HTTP/1.1 200 OK (no redirect)
```

## SSL Certificate Installation

### Option 1: Let's Encrypt (Free)

```bash
# Install certbot
sudo apt-get update
sudo apt-get install certbot python3-certbot-nginx

# Generate certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal is configured automatically
# Verify auto-renewal
sudo certbot renew --dry-run
```

### Option 2: Commercial SSL

1. Purchase SSL certificate from trusted CA
2. Generate CSR (Certificate Signing Request)
3. Upload certificate files to server
4. Configure web server to use certificate

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;

    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    # SSL Certificate
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # SSL Configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # WordPress root
    root /var/www/html;
    index index.php;

    # ... rest of configuration
}
```

### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com

    # Redirect HTTP to HTTPS
    Redirect permanent / https://yourdomain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com

    # SSL Certificate
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem

    # SSL Configuration
    SSLProtocol all -SSLv2 -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite HIGH:!aNULL:!MD5
    SSLHonorCipherOrder on

    DocumentRoot /var/www/html

    # ... rest of configuration
</VirtualHost>
```

## WordPress Configuration

Update `wp-config.php` to enforce HTTPS:

```php
// Force WordPress to use HTTPS
define('FORCE_SSL_ADMIN', true);

// If behind a proxy/load balancer
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}
```

Update site URL:
```bash
wp option update home 'https://yourdomain.com'
wp option update siteurl 'https://yourdomain.com'
```

## Load Balancer Configuration

If using a load balancer or reverse proxy (AWS ELB, CloudFlare, nginx):

1. Configure SSL termination at load balancer
2. Forward `X-Forwarded-Proto` header
3. The middleware automatically detects this header

Example Nginx proxy configuration:
```nginx
location / {
    proxy_pass http://backend;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header Host $host;
}
```

## Mixed Content Issues

After enabling HTTPS, fix any mixed content warnings:

### 1. Update Database URLs

```bash
# Update all HTTP URLs to HTTPS in database
wp search-replace 'http://yourdomain.com' 'https://yourdomain.com' --all-tables --precise
```

### 2. Check Resources

- Images: Should use relative URLs or HTTPS
- Scripts: Should use relative URLs or HTTPS
- Stylesheets: Should use relative URLs or HTTPS
- Fonts: Should use relative URLs or HTTPS

### 3. Browser Console

Check browser console for mixed content warnings:
```
Mixed Content: The page at 'https://...' was loaded over HTTPS, but requested an insecure resource 'http://...'. This request has been blocked; the content must be served over HTTPS.
```

## HSTS Preload Submission

Once HTTPS is stable, submit your domain to the HSTS preload list:

1. Ensure HSTS header includes `preload` directive (already configured)
2. Visit: https://hstspreload.org/
3. Enter your domain and submit
4. Wait for approval (can take weeks/months)

**Requirements:**
- Serve a valid certificate
- Redirect from HTTP to HTTPS
- Serve all subdomains over HTTPS
- Serve HSTS header with:
  - `max-age` >= 31536000 (1 year)
  - `includeSubDomains` directive
  - `preload` directive

## Troubleshooting

### Issue: Redirect Loop

**Symptoms:** Page keeps redirecting indefinitely

**Solution:**
1. Check if behind a load balancer/proxy
2. Ensure `X-Forwarded-Proto` header is set correctly
3. Verify `is_https()` method detects HTTPS correctly

### Issue: Mixed Content Warnings

**Symptoms:** Some resources not loading, console shows mixed content errors

**Solution:**
1. Run database search-replace command
2. Check theme/plugin hardcoded URLs
3. Update external resources to use HTTPS

### Issue: Headers Not Appearing

**Symptoms:** Security headers not showing in response

**Solution:**
1. Verify environment variable is set correctly
2. Check if another plugin/theme is overriding headers
3. Clear WordPress cache
4. Check web server isn't stripping headers

### Issue: CSP Blocking Resources

**Symptoms:** Scripts/styles not loading, CSP violations in console

**Solution:**
1. Check browser console for specific violations
2. Update CSP directives in `SecurityHeadersMiddleware.php`
3. Add necessary sources to appropriate directives
4. Test thoroughly before deploying

## Security Checklist

- [ ] SSL certificate installed and valid
- [ ] HTTP redirects to HTTPS
- [ ] HSTS header present
- [ ] All security headers configured
- [ ] No mixed content warnings
- [ ] A+ rating on SecurityHeaders.com
- [ ] A/A+ rating on SSL Labs
- [ ] Database URLs updated to HTTPS
- [ ] WordPress configured for HTTPS
- [ ] Auto-renewal configured for SSL certificate
- [ ] Test in all major browsers
- [ ] Monitor for CSP violations
- [ ] Document any CSP exceptions

## Files Modified

- `ma-deal-room/src/Middleware/HTTPSMiddleware.php` - HTTPS enforcement
- `ma-deal-room/src/Middleware/SecurityHeadersMiddleware.php` - Security headers
- `ma-deal-room/src/Core/Plugin.php` - Middleware registration
- `ma-deal-room/assets/admin/src/api/client.ts` - Frontend HTTPS enforcement

## References

- [OWASP Secure Headers Project](https://owasp.org/www-project-secure-headers/)
- [Content Security Policy Reference](https://content-security-policy.com/)
- [MDN Web Security](https://developer.mozilla.org/en-US/docs/Web/Security)
- [SecurityHeaders.com](https://securityheaders.com/)
- [SSL Labs](https://www.ssllabs.com/)
- [HSTS Preload](https://hstspreload.org/)
- [Let's Encrypt](https://letsencrypt.org/)
