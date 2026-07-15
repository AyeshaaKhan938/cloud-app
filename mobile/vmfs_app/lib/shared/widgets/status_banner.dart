import 'package:flutter/material.dart';
import 'package:vmfs_app/config/theme.dart';

enum StatusBannerType { info, success, error }

class StatusBanner extends StatelessWidget {
  const StatusBanner({
    super.key,
    required this.message,
    required this.type,
  });

  final String message;
  final StatusBannerType type;

  @override
  Widget build(BuildContext context) {
    final (bg, fg) = switch (type) {
      StatusBannerType.info => (
          VmfsTheme.accentSky.withValues(alpha: 0.15),
          const Color(0xFFBAE6FD),
        ),
      StatusBannerType.success => (
          VmfsTheme.success.withValues(alpha: 0.15),
          const Color(0xFFBBF7D0),
        ),
      StatusBannerType.error => (
          VmfsTheme.error.withValues(alpha: 0.15),
          const Color(0xFFFECACA),
        ),
    };

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(10),
      ),
      child: Text(
        message,
        style: TextStyle(color: fg, fontSize: 15, height: 1.4),
      ),
    );
  }
}
