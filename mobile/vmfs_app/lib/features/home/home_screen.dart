import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:vmfs_app/config/legal_content.dart';
import 'package:vmfs_app/config/app_config.dart';
import 'package:vmfs_app/config/theme.dart';
import 'package:vmfs_app/shared/widgets/vmfs_card.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  Future<void> _openUrl(String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFF002244), Color(0xFF003D7A), Color(0xFF0066CC)],
          ),
        ),
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 420),
                child: Column(
                  children: [
                    const SizedBox(height: 24),
                    Container(
                      width: 72,
                      height: 72,
                      decoration: BoxDecoration(
                        color: VmfsTheme.amberPrimary.withValues(alpha: 0.2),
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: const Icon(
                        Icons.storefront_outlined,
                        size: 40,
                        color: VmfsTheme.amberPrimary,
                      ),
                    ),
                    const SizedBox(height: 16),
                    const Text(
                      AppConfig.appName,
                      style: TextStyle(
                        fontSize: 28,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                      ),
                    ),
                    const SizedBox(height: 8),
                    const Text(
                      'Vending Machine Management Platform',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: Color(0xFFBAE6FD),
                        fontSize: 15,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      LegalContent.appAgeNotice,
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.75),
                        fontSize: 13,
                      ),
                    ),
                    const SizedBox(height: 32),
                    VmfsCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          _FeatureTile(
                            icon: Icons.verified_user_outlined,
                            title: 'Age verification',
                            subtitle:
                                'Scan the QR code on a VMFS kiosk to verify your age for regulated products.',
                            onTap: () => _showAgeVerificationInfo(context),
                          ),
                          const Divider(color: VmfsTheme.border, height: 24),
                          _FeatureTile(
                            icon: Icons.confirmation_number_outlined,
                            title: 'Lottery code lookup',
                            subtitle:
                                'Check your lottery or coupon code to see your prize tier and amount.',
                            onTap: () => context.push('/lottery'),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        TextButton(
                          onPressed: () => context.push('/privacy'),
                          child: const Text(
                            'Privacy Policy',
                            style: TextStyle(color: Color(0xFFBAE6FD)),
                          ),
                        ),
                        TextButton(
                          onPressed: () => context.push('/terms'),
                          child: const Text(
                            'Terms',
                            style: TextStyle(color: Color(0xFFBAE6FD)),
                          ),
                        ),
                      ],
                    ),
                    TextButton(
                      onPressed: () => _openUrl(AppConfig.supportUrl),
                      child: const Text(
                        'Contact Support',
                        style: TextStyle(color: Color(0xFFBAE6FD)),
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      '© 2020–${DateTime.now().year} VMFS USA™ All Rights Reserved',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.6),
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _showAgeVerificationInfo(BuildContext context) {
    showDialog<void>(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: VmfsTheme.cardDark,
        title: const Text(
          'Age verification',
          style: TextStyle(color: VmfsTheme.textPrimary),
        ),
        content: const Text(
          'To verify your age, scan the QR code displayed on the VMFS vending machine kiosk. '
          'The app will open automatically with your verification session.\n\n'
          'You can also open a verification link sent by the kiosk in your mobile browser — '
          'this app handles the same flow natively when installed.',
          style: TextStyle(color: VmfsTheme.textMuted, height: 1.5),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Got it'),
          ),
        ],
      ),
    );
  }
}

class _FeatureTile extends StatelessWidget {
  const _FeatureTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: VmfsTheme.accentSky, size: 28),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      fontSize: 17,
                      fontWeight: FontWeight.w600,
                      color: VmfsTheme.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: const TextStyle(
                      color: VmfsTheme.textMuted,
                      height: 1.4,
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, color: VmfsTheme.textMuted),
          ],
        ),
      ),
    );
  }
}
