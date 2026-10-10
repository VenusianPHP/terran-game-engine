# Security Policy

## Supported versions

venusian/terran is pre-1.0. No 0.x release receives security fixes or advisories; fixes land in the
next release line. Security support starts with 1.0.

| Version | Security fixes |
|---------|----------------|
| < 1.0   | No             |

## Reporting a vulnerability

Please don't open a public issue for a security problem.

Report it privately through GitHub: the **Report a vulnerability** button on this repository's
**Security** tab. If that isn't available, email **info@projectsaturnstudios.com**.

Include what you found, the affected version, the stager or toolkit and platform in use, and steps to
reproduce. Reports are read and weighed for the release line in development; before 1.0 there is
no response-time commitment.

## Security model

Terran is plain PHP. It draws through the rendering engines and output targets Surface hands it and runs on the framework's event loop; it holds no native code of its own and opens no sockets. What a PHP process may draw on or open is decided below it: the toolkit or stager package in use and the operating system's permissions on the display, window system or panel device.
