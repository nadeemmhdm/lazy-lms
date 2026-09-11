# Security Policy & Vulnerability Disclosure

Lazy LMS is committed to providing a secure, dependable, and self-hosted Learning Management System for educational institutions, academies, and private organizations. We take security vulnerabilities seriously.

---

## 🛡️ Supported Versions

Only the latest active release branch receives security patches:

| Version | Supported          |
| ------- | ------------------ |
| 2.0.x   | :white_check_mark: |
| 1.0.x   | :x:                |

---

## 🔒 Security Architecture Highlights

Lazy LMS is built around zero-trust and defense-in-depth principles:

1. **Defense Against IDOR / BOLA**:
   - Every public-facing resource uses non-sequential 5–8 character alphanumeric IDs (`[A-Za-z0-9]`) generated and resolved via `HashId`. Internal SQLite auto-increment IDs are never leaked in public URLs, JSON responses, or hidden HTML form fields.
   - All controller endpoints enforce server-side authorization checks verifying batch, course, lesson, attempt, and ownership hierarchy.

2. **Session Security & Remember-Me**:
   - Continuous session duration is capped at **5 hours** of continuous use.
   - Persistent "Remember Me" logins issue a rotating, cryptographically random token with SHA-256 server-side hashing, expiring in **12 hours**. Tokens are transmitted via `HttpOnly`, `SameSite=Lax`, and `Secure` cookies.
   - Session fixation is mitigated with proactive ID regeneration upon role elevation and authentication.

3. **New Device & IP Login Auditing**:
   - Any login from an unfamiliar IP address or User-Agent triggers an automatic entry in `security_events` and an automated security email dispatch via custom SMTP.
   - Brute-force protection includes progressive delays and lockout thresholds.

4. **Submission & Asset File Protection**:
   - Student assignments and exam uploads are stored strictly outside the webroot at `storage/uploads/submissions/`.
   - All file downloads are brokered through authorization-gated endpoints (`/submissions/download/{id}`) with MIME type validation, file extension whitelisting, and path traversal (`..`) defense. Executable uploads (`.php`, `.exe`, `.sh`, `.bat`, etc.) are prohibited at the kernel level.

5. **Data Protection & Maintenance**:
   - Maintenance Mode presents a 503 response for students and faculty while preserving uninterrupted access for administrators.
   - One-click hot SQLite snapshot backups are stored in a protected storage directory with restricted access.
   - Two-Factor Authentication (RFC 6238 TOTP) with recovery codes.

---

## 🚨 Reporting a Vulnerability

If you discover a security vulnerability in Lazy LMS, please follow responsible disclosure:

1. **Do not disclose the issue publicly** (e.g., via GitHub Issues, public discussions, or social media).
2. Email your findings directly to the maintainers at:
   **security@lazylms.org** (or open a private [GitHub Security Advisory](https://github.com/nadeemmhdm/lazy-lms/security/advisories)).
3. Please include:
   - A detailed description of the vulnerability.
   - Step-by-step reproduction instructions or a minimal Proof of Concept (PoC).
   - Affected URLs, routes, or components.
   - Assessment of potential impact and suggested mitigations.

### Our Response Timeline

- **Initial Response**: Within 24 hours acknowledging receipt.
- **Triage & Reproduction**: Within 48 hours.
- **Fix & Patch Release**: Dependent on severity, typically within 3–7 business days.
- **Public Disclosure**: Coordinated after the fix is merged and released.

---

## 📜 Safe Harbor

We consider security research conducted under this policy to be:
* Authorized in accordance with applicable computer crime laws.
* Exempt from claims of copyright infringement or reverse engineering terms.
* Eligible for recognition in our Security Acknowledgments.

Thank you for helping keep Lazy LMS and our users secure!
