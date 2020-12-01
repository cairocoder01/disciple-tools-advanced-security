
# Disciple Tools Advanced Security
The Disciple Tools Advanced Security Plugin is intended to facilitate advanced security audits 
of activity on your Disciple.Tools site. It adds and admin view of the D.T activity log
(to admin users only) and enables writing select activity logs to a line-delimited JSON
log file for ingestion into external security audit tools.

# Contributing
## Getting Started
Install Composer packages: `composer install`

## Submitting Updates
1. Before submitting any code, ensure lint rules all pass: `composer run lint`
  1. Run `composer run lint-fix` to automatically fix as much as possible
2. Submit pull request with detailed description of the need and function of the added/changed code
3. If CI build fails, fix errors and push code so it can be reviewed
