import 'package:flutter/material.dart';
import 'package:vmfs_app/config/theme.dart';

class VmfsCard extends StatelessWidget {
  const VmfsCard({super.key, required this.child, this.padding});

  final Widget child;
  final EdgeInsetsGeometry? padding;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: VmfsTheme.cardDark,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: VmfsTheme.border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.25),
            blurRadius: 30,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      padding: padding ?? const EdgeInsets.all(20),
      child: child,
    );
  }
}
