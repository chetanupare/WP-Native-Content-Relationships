# Security Policy

## Supported Versions

| Version | Supported          |
|---------|--------------------|
| 1.0.x  | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability in Native Content Relationships, please report it responsibly.

### How to Report

**Private Disclosure (Preferred)**
- Email: security@chetanupare.com
- Include "Security Vulnerability" in the subject
- Provide detailed information about the vulnerability
- Include steps to reproduce (if applicable)

**GitHub Private Reporting**
- Create a new [security advisory](https://github.com/chetanupare/WP-Native-Content-Relationships/security/advisories/new)
- Mark as confidential if needed
- Provide detailed vulnerability information

### What to Include

1. **Vulnerability Type**
   - XSS, SQL Injection, CSRF, etc.
   - Authentication bypass
   - Privilege escalation
   - Data exposure

2. **Affected Versions**
   - Which versions are affected
   - Which versions are not affected

3. **Impact Assessment**
   - Severity level (Critical, High, Medium, Low)
   - Potential impact on users
   - Exploitation requirements

4. **Proof of Concept**
   - Steps to reproduce
   - Code examples (if applicable)
   - Screenshots (if UI-related)

5. **Suggested Fix** (Optional)
   - Proposed solution
   - Code patches (if available)

### Response Time

We will acknowledge receipt of your vulnerability report within 48 hours and provide a detailed response within 7 days, including:

- Vulnerability confirmation
- Severity assessment
- Estimated timeline for fix
- Coordination for public disclosure

### Public Disclosure

We follow responsible disclosure practices:

1. **Private Fix Period**: We will work to fix the vulnerability before public disclosure
2. **Coordinated Disclosure**: We will coordinate with you on the disclosure timeline
3. **Security Updates**: We will release security updates for affected versions
4. **Credit**: We will credit you in the security advisory (with your permission)

### Security Best Practices

We follow WordPress security best practices:

- **Input Validation**: All user input is validated and sanitized
- **Output Escaping**: All output is properly escaped
- **Database Security**: All queries use prepared statements
- **Capability Checks**: All actions require appropriate permissions
- **Nonce Verification**: All forms use WordPress nonce protection
- **Data Sanitization**: All data is sanitized before storage

### Current Security Measures

- **Code Review**: All code is reviewed for security issues
- **Automated Testing**: Security tests run on all changes
- **Dependency Updates**: Dependencies are kept up-to-date
- **WordPress Standards**: Follow WordPress security guidelines
- **Regular Audits**: Regular security audits and penetration testing

### Security Team

Our security team reviews all reported vulnerabilities and coordinates fixes.

### Acknowledgments

We thank security researchers for helping us keep Native Content Relationships secure. Your responsible disclosure helps protect all our users.

### Legal Notice

Please do not:
- Attempt to exploit any vulnerability
- Share vulnerability details publicly before disclosure
- Use automated scanners without permission
- Disrupt service for other users

Following these guidelines helps us maintain security for all users while protecting you from legal issues.
