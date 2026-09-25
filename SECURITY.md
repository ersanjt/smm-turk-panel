# Security Policy

## Reporting a vulnerability

Please do not open a public issue for a suspected vulnerability.

Report security-sensitive findings privately to the repository owner through GitHub. Include the affected component, reproduction steps, and expected impact.

## Secrets

Never commit:

- database credentials
- provider API keys
- SMTP credentials
- admin passwords
- private keys
- production backups
- customer data

Use local configuration files and environment-specific secret storage. Public examples must contain placeholders only.

## Supported code

Security fixes target the current `main` branch.
