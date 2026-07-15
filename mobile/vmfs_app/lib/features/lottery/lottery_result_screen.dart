import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:vmfs_app/config/theme.dart';
import 'package:vmfs_app/core/models/lottery_lookup_result.dart';
import 'package:vmfs_app/shared/widgets/status_banner.dart';
import 'package:vmfs_app/shared/widgets/vmfs_card.dart';

class LotteryResultScreen extends StatelessWidget {
  const LotteryResultScreen({super.key, required this.result});

  final LotteryLookupResult result;

  @override
  Widget build(BuildContext context) {
    final amount = result.prizeAmount != null
        ? double.tryParse(result.prizeAmount!)?.toStringAsFixed(2) ??
            result.prizeAmount
        : null;

    return Scaffold(
      appBar: AppBar(title: const Text('Your result')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 420),
            child: VmfsCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(
                    result.canVend ? 'Your result' : 'Code check',
                    style: const TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.w700,
                      color: VmfsTheme.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 16),
                  if (!result.canVend)
                    StatusBanner(
                      message: result.message,
                      type: StatusBannerType.error,
                    )
                  else ...[
                    if (result.code != null)
                      Text(
                        'Code: ${result.code}',
                        style: const TextStyle(color: VmfsTheme.textMuted),
                      ),
                    const SizedBox(height: 12),
                    Text(
                      result.priceTier ?? '—',
                      style: const TextStyle(
                        fontSize: 32,
                        fontWeight: FontWeight.w700,
                        color: VmfsTheme.amberPrimary,
                      ),
                    ),
                    if (result.prizeName != null && result.prizeName!.isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.only(top: 4),
                        child: Text(
                          result.prizeName!,
                          style: const TextStyle(color: VmfsTheme.textPrimary),
                        ),
                      ),
                    if (amount != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 8),
                        child: Text(
                          'Prize value: \$$amount USD',
                          style: const TextStyle(
                            fontSize: 22,
                            color: VmfsTheme.textPrimary,
                          ),
                        ),
                      ),
                    const SizedBox(height: 12),
                    StatusBanner(
                      message: result.message,
                      type: StatusBannerType.success,
                    ),
                    if (result.isRedeemed)
                      const Padding(
                        padding: EdgeInsets.only(top: 12),
                        child: Text(
                          'This code was already marked as redeemed.',
                          style: TextStyle(
                            color: VmfsTheme.textMuted,
                            fontSize: 14,
                          ),
                        ),
                      ),
                    if (result.productName != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 12),
                        child: Text(
                          'Product: ${result.productName}${result.productSku != null ? ' (${result.productSku})' : ''}',
                          style: const TextStyle(
                            color: VmfsTheme.textMuted,
                            fontSize: 14,
                          ),
                        ),
                      ),
                  ],
                  const SizedBox(height: 20),
                  OutlinedButton(
                    onPressed: () => context.go('/lottery'),
                    child: const Text('Check another code'),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
