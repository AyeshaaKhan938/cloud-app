/// Runtime configuration for the VMFS mobile app.
///
/// Override [apiBaseUrl] at build time:
/// `flutter run --dart-define=API_BASE_URL=https://vmfs.sm-vending.com/api/v1`
class AppConfig {
  AppConfig._();

  static const String appName = 'VMFS USA';

  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://vmfs.sm-vending.com/api/v1',
  );

  static const String privacyPolicyUrl = String.fromEnvironment(
    'PRIVACY_POLICY_URL',
    defaultValue: 'https://vmfs.sm-vending.com/privacy',
  );

  static const String termsUrl = String.fromEnvironment(
    'TERMS_URL',
    defaultValue: 'https://vmfs.sm-vending.com/terms',
  );

  static const String supportUrl = String.fromEnvironment(
    'SUPPORT_URL',
    defaultValue: 'https://vmfsusa.com/contact',
  );

  /// Deep link host for universal links / app links (must match server config).
  static const String deepLinkHost = String.fromEnvironment(
    'DEEP_LINK_HOST',
    defaultValue: 'vmfs.sm-vending.com',
  );
}
