import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:vmfs_app/config/theme.dart';
import 'package:vmfs_app/core/models/lottery_lookup_result.dart';
import 'package:vmfs_app/features/age_verification/age_verification_screen.dart';
import 'package:vmfs_app/features/home/home_screen.dart';
import 'package:vmfs_app/features/legal/legal_screens.dart';
import 'package:vmfs_app/features/lottery/lottery_lookup_screen.dart';
import 'package:vmfs_app/features/lottery/lottery_result_screen.dart';

final GoRouter appRouter = GoRouter(
  initialLocation: '/',
  routes: [
    GoRoute(
      path: '/',
      builder: (context, state) => const HomeScreen(),
    ),
    GoRoute(
      path: '/verify',
      builder: (context, state) {
        final sessionId = state.uri.queryParameters['session'];
        if (sessionId == null || sessionId.isEmpty) {
          return const _MissingSessionScreen();
        }
        return AgeVerificationScreen(sessionId: sessionId);
      },
    ),
    GoRoute(
      path: '/lottery',
      builder: (context, state) => const LotteryLookupScreen(),
    ),
    GoRoute(
      path: '/lottery/result',
      builder: (context, state) {
        final result = state.extra;
        if (result is! LotteryLookupResult) {
          return const LotteryLookupScreen();
        }
        return LotteryResultScreen(result: result);
      },
    ),
    GoRoute(
      path: '/privacy',
      builder: (context, state) => const PrivacyPolicyScreen(),
    ),
    GoRoute(
      path: '/terms',
      builder: (context, state) => const TermsOfServiceScreen(),
    ),
  ],
);

class _MissingSessionScreen extends StatelessWidget {
  const _MissingSessionScreen();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [VmfsTheme.bgDark, VmfsTheme.bgGradientEnd],
          ),
        ),
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.qr_code_scanner, size: 64, color: VmfsTheme.error),
                const SizedBox(height: 16),
                const Text(
                  'Missing verification session',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w600,
                    color: VmfsTheme.textPrimary,
                  ),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Scan the QR code on the VMFS kiosk to start age verification.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: VmfsTheme.textMuted, height: 1.5),
                ),
                const SizedBox(height: 24),
                ElevatedButton(
                  onPressed: () => context.go('/'),
                  child: const Text('Go to home'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
