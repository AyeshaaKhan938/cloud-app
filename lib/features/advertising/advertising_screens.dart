import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../core/widgets/vmfs_crud_screen.dart';
import '../../data/vmfs_repository.dart';
import '../auth/auth_provider.dart';

final advertisementsProvider = FutureProvider<List<Map<String, dynamic>>>((ref) async {
  return ref.watch(repositoryProvider).fetchAdvertisements();
});

final advertisementGroupsProvider = FutureProvider<List<Map<String, dynamic>>>((ref) async {
  return ref.watch(repositoryProvider).fetchAdvertisementGroups();
});

final advertisementTagsProvider = FutureProvider<List<Map<String, dynamic>>>((ref) async {
  return ref.watch(repositoryProvider).fetchAdvertisementTags();
});

class AdvertisementsScreen extends ConsumerWidget {
  const AdvertisementsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final canManage = ref.watch(authProvider.select((s) => s.user?.canAccess('advertising') ?? false));
    final repo = ref.read(repositoryProvider);
    final currency = NumberFormat.simpleCurrency();

    return VmfsCrudScreen(
      title: 'Advertisements',
      provider: advertisementsProvider,
      emptyTitle: 'No advertisements',
      canManage: canManage,
      fields: const [
        VmfsCrudField(key: 'title', label: 'Title', required: true),
        VmfsCrudField(key: 'type', label: 'Type (image/video)', initialValue: 'image'),
        VmfsCrudField(key: 'advertiser_name', label: 'Advertiser'),
        VmfsCrudField(key: 'link_url', label: 'Link URL'),
      ],
      itemTitle: (item) => item['title'] as String? ?? 'Ad',
      itemSubtitle: (item) => '${item['type_label'] ?? item['type']} · ${item['advertiser_name'] ?? ''}',
      itemTrailing: (item) => currency.format((item['cost'] as num?)?.toDouble() ?? 0),
      onCreate: repo.createAdvertisement,
      onUpdate: repo.updateAdvertisement,
      onDelete: repo.deleteAdvertisement,
    );
  }
}

class AdvertisementGroupsScreen extends ConsumerWidget {
  const AdvertisementGroupsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final canManage = ref.watch(authProvider.select((s) => s.user?.canAccess('advertising') ?? false));
    final repo = ref.read(repositoryProvider);

    return VmfsCrudScreen(
      title: 'Advertisement groups',
      provider: advertisementGroupsProvider,
      emptyTitle: 'No advertisement groups',
      canManage: canManage,
      fields: const [VmfsCrudField(key: 'name', label: 'Group name', required: true)],
      itemTitle: (item) => item['name'] as String? ?? 'Group',
      itemSubtitle: (item) => '${item['advertisement_count'] ?? 0} ads',
      onCreate: repo.createAdvertisementGroup,
      onUpdate: repo.updateAdvertisementGroup,
      onDelete: repo.deleteAdvertisementGroup,
    );
  }
}

class AdvertisementTagsScreen extends ConsumerWidget {
  const AdvertisementTagsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final canManage = ref.watch(authProvider.select((s) => s.user?.canAccess('advertising') ?? false));
    final repo = ref.read(repositoryProvider);

    return VmfsCrudScreen(
      title: 'Advertisement tags',
      provider: advertisementTagsProvider,
      emptyTitle: 'No tags',
      canManage: canManage,
      fields: const [VmfsCrudField(key: 'name', label: 'Tag name', required: true)],
      itemTitle: (item) => item['name'] as String? ?? 'Tag',
      itemSubtitle: (_) => 'Advertisement tag',
      onCreate: repo.createAdvertisementTag,
      onUpdate: repo.updateAdvertisementTag,
      onDelete: repo.deleteAdvertisementTag,
    );
  }
}
