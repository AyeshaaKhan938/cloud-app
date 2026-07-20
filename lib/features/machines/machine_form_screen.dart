import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/api_exception.dart';
import '../../core/widgets/vmfs_widgets.dart';
import '../../models/machine.dart';
import '../auth/auth_provider.dart';

class MachineFormScreen extends ConsumerStatefulWidget {
  const MachineFormScreen({super.key, this.machineId});

  final int? machineId;

  bool get isEditing => machineId != null;

  @override
  ConsumerState<MachineFormScreen> createState() => _MachineFormScreenState();
}

class _MachineFormScreenState extends ConsumerState<MachineFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _numberController = TextEditingController();
  final _nameController = TextEditingController();
  final _addressController = TextEditingController();
  final _remarksController = TextEditingController();
  bool _isEnabled = true;
  bool _ageVerification = false;
  int? _groupId;
  bool _loading = false;
  bool _initialized = false;
  List<Map<String, dynamic>> _groups = const [];

  @override
  void initState() {
    super.initState();
    _loadLookups();
  }

  Future<void> _loadLookups() async {
    final groups = await ref.read(repositoryProvider).fetchMachineGroups();
    if (!mounted) return;
    setState(() => _groups = groups);

    if (widget.machineId != null) {
      await _loadMachine();
    }
  }

  Future<void> _loadMachine() async {
    final machine = await ref.read(repositoryProvider).fetchMachine(widget.machineId!);
    if (!mounted) return;
    setState(() {
      _numberController.text = machine.machineNumber;
      _nameController.text = machine.machineName;
      _addressController.text = machine.address;
      _remarksController.text = machine.remarks ?? '';
      _isEnabled = machine.isEnabled;
      _ageVerification = machine.ageVerificationEnabled;
      _groupId = machine.machineGroupId;
      _initialized = true;
    });
  }

  @override
  void dispose() {
    _numberController.dispose();
    _nameController.dispose();
    _addressController.dispose();
    _remarksController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _loading = true);
    try {
      final repo = ref.read(repositoryProvider);
      if (widget.isEditing) {
        await repo.updateMachine(
          id: widget.machineId!,
          machineNumber: _numberController.text.trim(),
          machineName: _nameController.text.trim(),
          detailedAddress: _addressController.text.trim(),
          machineGroupId: _groupId,
          isEnabled: _isEnabled,
          ageVerificationEnabled: _ageVerification,
          remarks: _remarksController.text.trim(),
        );
        if (mounted) context.pop(true);
      } else {
        final created = await repo.createMachine(
          machineNumber: _numberController.text.trim(),
          machineName: _nameController.text.trim(),
          detailedAddress: _addressController.text.trim(),
          machineGroupId: _groupId,
          isEnabled: _isEnabled,
          ageVerificationEnabled: _ageVerification,
          remarks: _remarksController.text.trim(),
        );
        if (mounted) context.go('/machines/${created.id}');
      }
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (widget.isEditing && !_initialized) {
      return const Scaffold(body: VmfsLoadingView());
    }

    return Scaffold(
      appBar: AppBar(title: Text(widget.isEditing ? 'Edit machine' : 'Add machine')),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            TextFormField(
              controller: _numberController,
              decoration: const InputDecoration(labelText: 'Machine number *'),
              validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _nameController,
              decoration: const InputDecoration(labelText: 'Machine name *'),
              validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<int?>(
              value: _groupId,
              decoration: const InputDecoration(labelText: 'Machine group'),
              items: [
                const DropdownMenuItem<int?>(value: null, child: Text('None')),
                for (final group in _groups)
                  DropdownMenuItem<int?>(
                    value: group['id'] as int,
                    child: Text(group['name']?.toString() ?? 'Group'),
                  ),
              ],
              onChanged: (v) => setState(() => _groupId = v),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _addressController,
              decoration: const InputDecoration(labelText: 'Address'),
              maxLines: 2,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _remarksController,
              decoration: const InputDecoration(labelText: 'Remarks'),
              maxLines: 2,
            ),
            SwitchListTile(
              title: const Text('Enabled'),
              value: _isEnabled,
              onChanged: (v) => setState(() => _isEnabled = v),
            ),
            SwitchListTile(
              title: const Text('Age verification'),
              value: _ageVerification,
              onChanged: (v) => setState(() => _ageVerification = v),
            ),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: _loading ? null : _submit,
              child: _loading
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : Text(widget.isEditing ? 'Save changes' : 'Create machine'),
            ),
          ],
        ),
      ),
    );
  }
}
