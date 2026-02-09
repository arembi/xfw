# Security Notes

This project includes basic safeguards, but security-sensitive behavior is configurable. Review and override these defaults before deploying.

## Input handling
- **CSRF**: Requests that trigger input handlers require a CSRF token by default (`_csrf` in the request body). Configure with `csrfRequired`.
- **Clearance**: Input handling requires a non-guest clearance by default (`inputClearance` defaults to `1`).
- **Allowlist**: You can restrict which input handlers may run with `inputHandlerAllowlist` in domain settings. If set to an array, only listed modules/methods are allowed.

## File serving
`Router::serveFiles()` only serves assets from the configured public directory (`publicFilesDir`, default `public`) and enforces a limited set of extensions. Keep sensitive files outside the public directory.

## Uploads
Uploads are stored outside the site directory by default (`uploadsDir`) and written with private visibility. Ensure accepted MIME types are configured and stored outside your web root.
