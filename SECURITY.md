# Security

Do not publish credentials, personal data, exploitable proof-of-concept details or private logs in public issues. Report suspected vulnerabilities privately to the repository maintainer. Use GitHub private vulnerability reporting when it is enabled; otherwise arrange a private channel with the maintainer before sharing details.

Include the affected revision, reproduction steps, expected/observed behavior and a minimal sanitized example. Remove credentials, reset/verification links and personal information from attachments.

For deployments, use HTTPS, disable debug output, keep an independent application key, supervise workers, restrict integration credentials to required resources and test recovery. Staff MFA and encrypted archives are available but are optional until configured. Keep local mail on the log driver during development; logs contain sensitive authentication links.

Rotate exposed credentials and invalidate affected sessions or tokens as appropriate. Do not rotate `APP_KEY` casually: existing encrypted secrets and MFA data require the original key. Preserve it securely for restoration. Review dependency advisories with `composer audit` and `npm audit` as part of maintenance.
