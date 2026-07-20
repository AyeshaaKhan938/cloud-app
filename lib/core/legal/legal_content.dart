abstract final class LegalContent {
  static const String companyName = 'VMFS USA';
  static const String supportEmail = 'support@vmfsusa.com';
  static const String websiteUrl = 'https://cloud.vmfsusa.com';
  static const String privacyPolicyUrl = 'https://cloud.vmfsusa.com/privacy';
  static const String termsUrl = 'https://cloud.vmfsusa.com/terms';

  static const String privacyPolicy = '''
Last updated: July 2026

$companyName ("VMFS", "we", "us") provides the VMFS Cloud platform and mobile application for authorized vending operators, franchise partners, and administrators.

Information we collect
• Account credentials (email) to authenticate against your VMFS Cloud account.
• Operational data displayed in the app: machines, slots, products, orders, advertisements, lotteries, coupons, wallet balance, team members, and support tickets — according to your role permissions.
• Technical data required to operate the service (IP address, device type, app version, and diagnostic logs if enabled by your device).

How we use information
• To authenticate you and maintain a secure session.
• To display the same business data available in the VMFS Cloud web admin portal.
• To process support tickets you create in the app.
• To improve reliability and security of the platform.

Data sharing
• We do not sell personal information.
• Data is shared only with your organization’s authorized VMFS administrators and service providers required to host VMFS Cloud.

Security
• Sessions use encrypted HTTPS to $websiteUrl.
• Authentication tokens are stored securely on your device.

Your choices
• Use the same web admin account credentials; the mobile app does not create standalone accounts.
• To request account closure or data deletion, contact $supportEmail or your VMFS administrator.

Contact
• Email: $supportEmail
• Web: $websiteUrl/admin
''';

  static const String termsOfService = '''
Last updated: July 2026

VMFS USA — Cloud Platform Terms & Conditions

1. Acceptance
By signing in to the VMFS Cloud mobile app you agree to these Terms & Conditions and our Privacy Policy. This app is an extension of the VMFS Cloud web admin portal at $websiteUrl.

2. Authorized use
• You must hold a valid VMFS Cloud account issued by your organization or VMFS USA.
• You may use the app only for legitimate vending operations: monitoring machines and slots, reviewing products, orders, advertisements, lotteries, coupons, wallet activity, reports, and support tickets.
• You must keep your credentials confidential and notify your administrator of unauthorized access.

3. Platform scope
• Mobile features mirror web permissions for your role. Some administrative actions may remain web-only.
• Data shown in the app is sourced from VMFS Cloud; verify critical operational decisions using the primary web system when required.

4. Service availability
• The app requires connectivity to $websiteUrl.
• VMFS may update, suspend, or modify features to maintain security and platform integrity.

5. Acceptable conduct
You agree not to:
• Reverse engineer, scrape, or overload VMFS Cloud systems.
• Access data outside your assigned role or organization.
• Upload malicious content through support channels.

6. Intellectual property
VMFS USA logos, software, and platform content remain the property of VMFS USA and its licensors.

7. Disclaimer
Operational metrics, inventory, and sales data are provided for business monitoring "as is" without warranty of uninterrupted accuracy.

8. Limitation of liability
To the maximum extent permitted by law, VMFS USA is not liable for indirect or consequential damages arising from use of the mobile app or cloud platform.

9. Termination
Access may be suspended for policy violations or when your VMFS Cloud account is disabled by an administrator.

10. Contact
Questions about these terms: $supportEmail
''';

  static const List<({String question, String answer})> helpFaq = [
    (
      question: 'How do I sign in?',
      answer:
          'Use the same email and password as the VMFS Cloud web admin at cloud.vmfsusa.com/admin. Accept the Terms & Privacy Policy on the login screen.',
    ),
    (
      question: 'Why do I see "Unauthenticated" on first open?',
      answer:
          'Update to the latest app build. If it persists, sign out and sign in again. The app verifies your session before loading the dashboard.',
    ),
    (
      question: 'Why do I see "Cannot reach VMFS Cloud"?',
      answer:
          'Check your internet connection and confirm cloud.vmfsusa.com opens in your phone browser.',
    ),
    (
      question: 'What can I do in the mobile app?',
      answer:
          'View dashboard stats, machines & slots, products, orders, advertisements, lotteries, coupons, wallet, reports, team members, and support tickets — based on your web role permissions.',
    ),
    (
      question: 'How do I get support?',
      answer:
          'Tap Support in the top bar or open Help in the More menu. Email support@vmfsusa.com.',
    ),
    (
      question: 'How do I delete my account?',
      answer:
          'Contact your VMFS administrator or email support@vmfsusa.com. Account removal is handled in VMFS Cloud, not inside this app.',
    ),
  ];
}
