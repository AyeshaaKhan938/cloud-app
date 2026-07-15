/// Legal copy shown in-app (mirrors server /privacy and /terms pages).
class LegalContent {
  LegalContent._();

  static const int minimumAge = 18;

  static const String privacyPolicy = '''
Privacy Policy — VMFS USA

Who this app is for
The VMFS USA app is for adults aged $minimumAge or older. It is not directed at children under 13.

Information we collect
• Age verification: photo of government-issued ID and document type when you scan a kiosk QR code.
• Lottery lookup: promotional codes you enter to view prize details.
• Technical data: standard server logs for security (no ad tracking).

How we use your information
• Verify age for regulated vending products
• Link verification to the kiosk session
• Validate promotional codes
• Prevent fraud

ID document retention
Uploaded ID images are deleted within 24 hours after processing. Verification pass/fail status may be kept for compliance without retaining the image.

Data sharing
We do not sell your data. We may share with identity verification providers, infrastructure hosts, or authorities when required by law.

Your rights
Contact support to request access, correction, or deletion of your personal data.

Security
All data is transmitted over HTTPS.
''';

  static const String termsOfService = '''
Terms of Service — VMFS USA

Service
VMFS USA is a companion app for VMFS vending machines: age verification and promotional lottery/coupon lookup.

Eligibility
You must be $minimumAge+ for age verification. This is not a gambling app — lottery codes are promotional prizes for physical vending products only.

Age verification
You agree to submit accurate government-issued ID. Misrepresentation may result in denied service.

Acceptable use
• Use only for legitimate VMFS vending interactions
• Do not circumvent verification or code validation
• Do not upload fraudulent documents

Disclaimer
The app is provided "as is". VMFS USA is not liable for machine availability or third-party verification delays.
''';

  static const String ageVerificationConsent = '''
I confirm that I am at least $minimumAge years old. I consent to VMFS USA collecting and processing a photo of my government-issued ID solely for age verification at this vending machine session. I understand my ID image will be deleted within 24 hours after processing.
''';

  static const String lotteryDisclaimer =
      'Promotional coupon lookup only. This is not real-money gambling. '
      'Prizes are dispensed from VMFS vending machines.';

  static const String appAgeNotice =
      'This app is intended for users aged $minimumAge and older.';
}
