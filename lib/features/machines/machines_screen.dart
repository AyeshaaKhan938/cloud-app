import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/utils/debouncer.dart';
import '../../core/theme/vmfs_colors.dart';
import '../../core/widgets/vmfs_widgets.dart';
import '../../data/vmfs_repository.dart';
import '../auth/auth_provider.dart';
import '../../models/machine.dart';

final machinesProvider = FutureProvider.family<List<MachineSummary>, String>((ref, search) async {
  if (search.isEmpty) {
    ref.keepAlive();
  }
  return ref.read(repositoryProvider).fetchMachines(search: search);
});

class MachinesScreen extends ConsumerStatefulWidget {
  const MachinesScreen({super.key});

  @override
  ConsumerState<MachinesScreen> createState() => _MachinesScreenState();
}

class _MachinesScreenState extends ConsumerState<MachinesScreen> {
  String _search = '';
  final _debouncer = Debouncer();

  @override
  void dispose() {
    _debouncer.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final machines = ref.watch(machinesProvider(_search));
    final canCreate = ref.watch(authProvider.select((s) => s.user?.canAccess('machines_create') ?? false));

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
          child: Row(
            children: [
              Expanded(
                child: TextField(
                  decoration: const InputDecoration(
                    hintText: 'Search machines...',
                    prefixIcon: Icon(Icons.search),
                  ),
                  onChanged: (v) => _debouncer.run(() => setState(() => _search = v.trim())),
                ),
              ),
              if (canCreate) ...[
                const SizedBox(width: 8),
                IconButton.filled(
                  tooltip: 'Add machine',
                  onPressed: () => context.push('/machines/new'),
                  icon: const Icon(Icons.add),
                ),
              ],
            ],
          ),
        ),
        Expanded(
          child: machines.when(
            loading: () => const VmfsLoadingView(),
            error: (e, _) => VmfsErrorView(
              message: e.toString(),
              onRetry: () => ref.invalidate(machinesProvider(_search)),
            ),
            data: (items) {
              if (items.isEmpty) {
                return const VmfsEmptyState(
                  title: 'No machines',
                  message: 'No machines are linked to this account yet.',
                );
              }

              return RefreshIndicator(
                onRefresh: () async => ref.invalidate(machinesProvider(_search)),
                child: ListView.separated(
                  padding: const EdgeInsets.all(16),
                  cacheExtent: 400,
                  itemCount: items.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 10),
                  itemBuilder: (context, index) {
                    final machine = items[index];
                    return RepaintBoundary(
                      child: Card(
                        child: ListTile(
                        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                        leading: CircleAvatar(
                          backgroundColor: VmfsColors.primaryLight,
                          child: Icon(
                            Icons.memory_rounded,
                            color: machine.isOnline ? VmfsColors.success : VmfsColors.textSecondary,
                          ),
                        ),
                        title: Text(machine.machineName, style: const TextStyle(fontWeight: FontWeight.w700)),
                        subtitle: Text('#${machine.machineNumber} · ${machine.slotCount} slots'),
                        trailing: VmfsStatusPill(
                          label: machine.isOnline ? 'Online' : 'Offline',
                          color: machine.isOnline ? VmfsColors.success : VmfsColors.textSecondary,
                        ),
                        onTap: () => context.push('/machines/${machine.id}'),
                      ),
                    ),
                    );
                  },
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}

final machineDetailProvider = FutureProvider.family<MachineDetail, int>((ref, id) async {
  return ref.watch(repositoryProvider).fetchMachine(id);
});

class MachineDetailScreen extends ConsumerWidget {
  const MachineDetailScreen({super.key, required this.machineId});

  final int machineId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final detail = ref.watch(machineDetailProvider(machineId));
    final user = ref.watch(authProvider.select((s) => s.user));
    final canEdit = user?.canAccess('machines_create') == true || user?.canAccess('machine_slots') == true;
    final canManageSlots = user?.canAccess('machine_slots') == true;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Machine'),
        actions: [
          if (canEdit)
            IconButton(
              tooltip: 'Edit machine',
              onPressed: () async {
                final changed = await context.push<bool>('/machines/$machineId/edit');
                if (changed == true) ref.invalidate(machineDetailProvider(machineId));
              },
              icon: const Icon(Icons.edit_outlined),
            ),
          if (canManageSlots)
            IconButton(
              tooltip: 'Add slot',
              onPressed: () async {
                final changed = await context.push<bool>('/machines/$machineId/slots/new');
                if (changed == true) ref.invalidate(machineDetailProvider(machineId));
              },
              icon: const Icon(Icons.add),
            ),
        ],
      ),
      body: detail.when(
        loading: () => const VmfsLoadingView(),
        error: (e, _) => VmfsErrorView(
          message: e.toString(),
          onRetry: () => ref.invalidate(machineDetailProvider(machineId)),
        ),
        data: (machine) {
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              VmfsHeroBanner(
                kicker: 'Machine overview',
                title: machine.machineName,
                subtitle: '#${machine.machineNumber}',
                trailing: VmfsStatusPill(
                  label: machine.isOnline ? 'Online' : 'Offline',
                  color: machine.isOnline ? VmfsColors.success : VmfsColors.textSecondary,
                ),
              ),
              const SizedBox(height: 16),
              GridView.count(
                crossAxisCount: 3,
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                mainAxisSpacing: 8,
                crossAxisSpacing: 8,
                children: [
                  VmfsStatCard(label: 'Total', value: '${machine.slotSummary.total}'),
                  VmfsStatCard(label: 'Stocked', value: '${machine.slotSummary.stocked}', color: VmfsColors.success),
                  VmfsStatCard(label: 'Low', value: '${machine.slotSummary.lowStock}', color: VmfsColors.warning),
                  VmfsStatCard(label: 'Empty', value: '${machine.slotSummary.empty}', color: VmfsColors.danger),
                  VmfsStatCard(label: 'Fault', value: '${machine.slotSummary.fault}', color: VmfsColors.danger),
                ],
              ),
              const SizedBox(height: 16),
              const Text('Slots & products', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
              const SizedBox(height: 8),
              ...machine.slots.map(
                (slot) => Card(
                  child: ListTile(
                    title: Text('Slot #${slot.lineNumber}'),
                    subtitle: Text('${slot.productName} · ${slot.currentStock}/${slot.maxStock}'),
                    trailing: Text('\$${slot.price.toStringAsFixed(2)}'),
                    onTap: canManageSlots
                        ? () async {
                            final changed = await context.push<bool>(
                              '/machines/$machineId/slots/${slot.id}/edit',
                            );
                            if (changed == true) ref.invalidate(machineDetailProvider(machineId));
                          }
                        : null,
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
