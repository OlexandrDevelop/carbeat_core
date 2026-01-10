# Mobile Activity Monitor

## Overview

This system provides real-time monitoring of mobile app usage. It tracks requests from the mobile application (identified by Dart user-agent) and displays activity data in an admin dashboard that updates automatically every 3 seconds.

## Architecture

### Components

1. **Middleware** (`TrackMobileActivity`)
   - Intercepts all API requests
   - Filters only mobile app requests (Dart user-agent)
   - Stores activity data in Redis

2. **Service** (`MobileActivityService`)
   - Manages Redis data operations
   - Provides methods to retrieve activity data and statistics

3. **Controller** (`MobileActivityController`)
   - Handles admin page rendering
   - Provides API endpoints for real-time data updates

4. **Vue Component** (`Admin/MobileActivity/Index.vue`)
   - Real-time dashboard with auto-refresh (3 seconds)
   - Displays active users, statistics, and recent actions

### Data Storage

All data is stored in **Redis** for performance and minimal database load:

- **TTL**: 1 hour (automatic expiration)
- **Key structure**:
  - `app_activity:{ip}` - Hash with user activity data
  - `app_activity:{ip}:actions` - List of recent actions (last 20)
  - `app_activity:active_ips` - Sorted set of active IPs (score = timestamp)

## Features

### Tracked Data

For each active user (identified by IP):
- IP address
- Last activity timestamp
- Request count
- User agent (full string)
- Dart version (extracted from user-agent)
- Last endpoint accessed
- Last HTTP method used
- Recent actions (last 20 actions with descriptions)

### Statistics

- **Total active users** (in last hour)
- **Active in last 5 minutes**
- **Active in last 1 minute**
- **Total requests** (sum of all tracked requests)

### Action Descriptions

The middleware automatically generates human-readable descriptions for common API endpoints:

- "Viewing masters list" - GET /api/v1/masters
- "Viewing master details" - GET /api/v1/masters/{id}
- "Checking master availability" - GET /api/v1/masters/{id}/availability
- "Viewing available time slots" - GET /api/v1/masters/{id}/slots
- "Creating booking" - POST /api/v1/bookings
- "Login" - POST /api/v1/auth/login
- "Registration" - POST /api/v1/auth/register
- And more...

## Dashboard Features

### Real-time Updates
- Auto-refresh every 3 seconds
- Visual indicator showing live/updating status
- No page reload required

### Statistics Cards
- Active Now (users active in last minute) - green highlight
- Last 5 Minutes
- Total Active (last hour)
- Total Requests

### Users Table
Displays all active users with:
- IP address
- Activity status indicator (green = active now, yellow = recent, gray = inactive)
- Last activity time (human-readable: "5s ago", "2m ago", etc.)
- Request count
- Dart version
- Last action description
- Expandable row with detailed recent actions

### Actions
- **Expand/Collapse** - View detailed action history for each user
- **Clear All Data** - Remove all tracking data from Redis (with confirmation)

## Routes

### Admin UI
- `GET /admin/mobile-activity` - Main monitoring dashboard

### API Endpoints
- `GET /admin-api/mobile-activity/data` - Get current activity data (JSON)
- `POST /admin-api/mobile-activity/clear` - Clear all activity data

## Configuration

### Middleware Registration

The middleware is automatically registered for all API routes in `bootstrap/app.php`:

```php
$middleware->api(append: [
    SetLocale::class,
    DetectApp::class,
    TrackMobileActivity::class,
]);
```

### User-Agent Detection

Requests are tracked only if the User-Agent header contains "Dart/" (case-sensitive).

Example mobile app user-agents:
- `Dart/3.10 (dart:io)`
- `Dart/3.5.0 (dart:io)`

### Data Retention

- Active users are tracked for **1 hour** after their last activity
- After 1 hour of inactivity, data is automatically removed from Redis (TTL expiration)
- Recent actions list keeps the **last 20 actions** per user

## Performance Considerations

### Minimal Overhead
- Uses Redis (in-memory) - very fast reads/writes
- No database queries
- Silent failure mode - tracking errors don't break API requests
- TTL-based automatic cleanup (no cron jobs needed)

### Scalability
- Redis can handle millions of operations per second
- Key design allows for horizontal scaling
- No locks or blocking operations

## Usage Example

### Accessing the Dashboard

1. Log in to admin panel
2. Navigate to "Mobile Activity" in the sidebar
3. View real-time activity of mobile app users

### Interpreting Activity Colors

- 🟢 **Green dot** - Active now (< 1 minute ago)
- 🟡 **Yellow dot** - Recent (1-5 minutes ago)
- ⚪ **Gray dot** - Inactive (> 5 minutes ago)

### Viewing User Details

1. Click "Show" button in the "Recent Actions" column
2. See full list of last actions with:
   - Timestamp
   - HTTP method
   - Human-readable description
   - Full endpoint path

## Troubleshooting

### No Users Appearing

**Check:**
1. Is Redis running? (`redis-cli ping` should return "PONG")
2. Are mobile app requests reaching the API?
3. Is the User-Agent header set correctly in mobile app?
4. Check Laravel logs for any middleware errors

### Data Not Updating

**Check:**
1. Browser console for JavaScript errors
2. Network tab - API requests to `/admin-api/mobile-activity/data`
3. Redis connection in Laravel

### Performance Issues

If the dashboard is slow:
1. Check Redis performance
2. Reduce polling interval (change from 3000ms to 5000ms in Vue component)
3. Limit number of tracked actions (currently 20 per user)

## Future Enhancements

Possible improvements:
- WebSocket support for instant updates (instead of polling)
- Export data to CSV
- Activity graphs/charts over time
- Filter by endpoint/action type
- Geolocation data based on IP
- User session tracking (if authenticated)
- Alerts for suspicious activity patterns

## Security Notes

- Dashboard is protected by `auth` and `admin.brand` middleware
- Only accessible to authenticated admin users
- IP addresses are stored temporarily (1 hour) for monitoring purposes
- No personal user data is stored (only IP and user-agent)
- Clear functionality available for data privacy compliance

## Dependencies

- **Redis** - Required for data storage
- **Laravel** - Framework
- **Inertia.js** - Frontend framework
- **Vue 3** - UI components
- **Axios** - HTTP client for polling

