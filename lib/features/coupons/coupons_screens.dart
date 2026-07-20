import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../core/theme/vmfs_colors.dart';
import '../../core/widgets/vmfs_crud_screen.dart';
import '../../data/vmfs_repository.dart';
import '../auth/auth_provider.dart';

final couponsProvider = FutureProvider<List<Map<String, dynamic>>>((ref) async {
  return ref.watch(repositoryProvider).fetchCoupons();
});

final lotteriesProvider = FutureProvider<List<Map<String, dynamic>>>((ref) async {
  return ref.watch(repositoryProvider).fetchLotteries();
});

class CouponsScreen extends ConsumerWidget {
  const CouponsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final canManage = ref.watch(authProvider.select((s) => s.user?.canAccess('products') ?? false));
    final repo = ref.read(repositoryProvider);
    final currency = NumberFormat.simpleCurrency();

    return VmfsCrudScreen(
      title: 'Coupons',
      provider: couponsProvider,
      emptyTitle: 'No coupons',
      canManage: canManage,
      fields: const [
        VmfsCrudField(key: 'name', label: 'Coupon name', required: true),
        VmfsCrudField(key: 'coupon_type', label: 'Type (fixed_amount/percentage)', initialValue: 'fixed_amount'),
        VmfsCrudField(key: 'discount_value', label: 'Discount value', required: true, keyboardType: TextInputType.number),
      ],
      itemTitle: (item) => item['name'] as String? ?? 'Coupon',
      itemSubtitle: (item) => 'Min ${currency.format((item['purchase_amount'] as num?)?.toDouble() ?? 0)}',
      itemTrailing: (item) => '${item['code_count'] ?? 0} codes',
      onCreate: repo.createCoupon,
      onUpdate: repo.updateCoupon,
      onDelete: repo.deleteCoupon,
    );
  }
}

class LotteriesScreen extends ConsumerWidget {
  const LotteriesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final canManage = ref.watch(authProvider.select((s) => s.user?.canAccess('products') ?? false));
    final repo = ref.read(repositoryProvider);

    return VmfsCrudScreen(
      title: 'Lotteries',
      provider: lotteriesProvider,
      emptyTitle: 'No lotteries',
      canManage: canManage,
      fields: const [
        VmfsCrudField(key: 'name', label: 'Lottery name', required: true),
        VmfsCrudField(key: 'product_id', label: 'Product ID', required: true, keyboardType: TextInputType.number),
        VmfsCrudField(key: 'machine_no', label: 'Machine number'),
      ],
      itemTitle: (item) => item['name'] as String? ?? 'Lottery',
      itemSubtitle: (item) => '${item['product_name'] ?? ''} · Machine ${item['machine_no'] ?? ''}',
      itemTrailing: (item) => (item['is_active'] as bool? ?? false) ? 'Active' : 'Inactive',
      onCreate: repo.createLottery,
      onUpdate: repo.updateLottery,
      onDelete: repo.deleteLottery,
    );
  }
}
