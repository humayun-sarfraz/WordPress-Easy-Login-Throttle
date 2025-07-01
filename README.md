# WordPress Easy Login Throttle

Adds a lightweight brute-force protection to WordPress by limiting login attempts per IP.

## Plugin URI

https://github.com/humayun-sarfraz/wp-easy-login-throttle

## Author

Humayun Sarfraz  
https://github.com/humayun-sarfraz

## Description

- **Limit**: Blocks an IP after 3 failed login attempts (default).  
- **Window**: Tracks attempts in a 5-minute window (default).  
- **Reset**: Clears the counter on successful login.  
- **Filters**:  
  - `we_lt_max_attempts` to change the maximum allowed attempts.  
  - `we_lt_time_window` to change the time window in seconds.

## Installation

1. Upload the `wp-easy-login-throttle` folder to `/wp-content/plugins/`.  
2. Activate **WordPress Easy Login Throttle** via the **Plugins** screen.

## Customization

```php
// Change max attempts to 5
add_filter( 'we_lt_max_attempts', function() {
    return 5;
} );

// Change time window to 10 minutes
add_filter( 'we_lt_time_window', function() {
    return 10 * 60;
} );

Changelog
1.0.0

    Initial release: login attempt throttling via IP, configurable via filters.

License

GPL v2 or later — see LICENSE.
