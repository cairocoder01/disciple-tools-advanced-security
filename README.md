![Advanced Security banner](/assets/banner-772x250.png)

# Disciple Tools Advanced Security
The Disciple Tools Advanced Security Plugin is intended to facilitate advanced security audits 
of activity on your Disciple.Tools site. It adds and admin view of the D.T activity log
(to admin users only) and enables writing select activity logs to a line-delimited JSON
log file for ingestion into external security audit tools.

## Activity Logs
All logging comes directly from Disciple.Tools' activity logging process. As such, a number
of new activity logs are added in order to also log those actions to file for security auditing.

**New Activity Logs**
* User
  * Failed login attempts
  * Password reset
  * New user creation
  * Add/Remove user roles
  * Add/Remove user from sites
  * Grant/Revoke super admin
  * Delete user
* Plugins
  * Activate/Deactivate plugin
  * Delete plugin
  * Update plugin
* Core
  * Upgrade WordPress
  
## File Logging Filter
Not all activity logs should be saved out to file as some are part of the normal operation of
the system. As a result, a filtering process is run every time an activity log is saved to 
check if it should be saved to file.

By default, the follow types of activity are saved to file:
* `action` = `export`
* `object_type` = `core`
* `object_type` = `plugin`
* `object_type` = `theme`
* `object_type` = `site_link_system`
* `object_type` = `User`

Additional activity can be logged to file using the filter `dt_advanced_security_activity_included`:

```
add_filter( 'dt_advanced_security_activity_included', 'my_filter', 10, 2 );
function my_filter( $include, $args ) {
  return $include || $args['object_type'] == 'my_post_type';
}
```

# Contributing
## Getting Started
Install Composer packages: `composer install`

## Submitting Updates
1. Before submitting any code, ensure lint rules all pass: `composer run lint`
  1. Run `composer run lint-fix` to automatically fix as much as possible
2. Submit pull request with detailed description of the need and function of the added/changed code
3. If CI build fails, fix errors and push code so it can be reviewed
