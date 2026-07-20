class MachineSummary {
  const MachineSummary({
    required this.id,
    required this.machineName,
    required this.machineNumber,
    required this.isEnabled,
    required this.isOnline,
    required this.slotCount,
    required this.address,
  });

  factory MachineSummary.fromJson(Map<String, dynamic> json) {
    return MachineSummary(
      id: json['id'] as int,
      machineName: json['machine_name'] as String? ?? 'Machine',
      machineNumber: json['machine_number'] as String? ?? '',
      isEnabled: json['is_enabled'] as bool? ?? false,
      isOnline: json['is_online'] as bool? ?? false,
      slotCount: json['slot_count'] as int? ?? 0,
      address: json['detailed_address'] as String? ?? '',
    );
  }

  final int id;
  final String machineName;
  final String machineNumber;
  final bool isEnabled;
  final bool isOnline;
  final int slotCount;
  final String address;
}

class MachineDetail extends MachineSummary {
  const MachineDetail({
    required super.id,
    required super.machineName,
    required super.machineNumber,
    required super.isEnabled,
    required super.isOnline,
    required super.slotCount,
    required super.address,
    required this.groupName,
    required this.ownerAccount,
    required this.lastSeenAt,
    required this.machineGroupId,
    required this.financeGroupId,
    required this.advertisementGroupId,
    required this.machineScenario,
    required this.serviceHotLine,
    required this.ageVerificationEnabled,
    required this.minimumAge,
    required this.remarks,
    required this.latitude,
    required this.longitude,
    required this.slotSummary,
    required this.slots,
  });

  factory MachineDetail.fromJson(Map<String, dynamic> json) {
    final summary = json['machine'] as Map<String, dynamic>? ?? json;
    final slotsJson = json['slots'] as List<dynamic>? ?? [];
    final slotSummary = json['slot_summary'] as Map<String, dynamic>? ?? {};

    return MachineDetail(
      id: summary['id'] as int,
      machineName: summary['machine_name'] as String? ?? 'Machine',
      machineNumber: summary['machine_number'] as String? ?? '',
      isEnabled: summary['is_enabled'] as bool? ?? false,
      isOnline: summary['is_online'] as bool? ?? false,
      slotCount: slotSummary['total'] as int? ?? slotsJson.length,
      address: summary['detailed_address'] as String? ?? '',
      groupName: summary['group_name'] as String? ?? '—',
      ownerAccount: summary['owner_account'] as String? ?? '—',
      lastSeenAt: summary['last_seen_at'] as String?,
      machineGroupId: summary['machine_group_id'] as int?,
      financeGroupId: summary['finance_group_id'] as int?,
      advertisementGroupId: summary['advertisement_group_id'] as int?,
      machineScenario: summary['machine_scenario'] as String?,
      serviceHotLine: summary['service_hot_line'] as String?,
      ageVerificationEnabled: summary['age_verification_enabled'] as bool? ?? false,
      minimumAge: summary['minimum_age'] as int?,
      remarks: summary['remarks'] as String?,
      latitude: (summary['latitude'] as num?)?.toDouble(),
      longitude: (summary['longitude'] as num?)?.toDouble(),
      slotSummary: SlotSummary.fromJson(slotSummary),
      slots: slotsJson.map((e) => MachineSlot.fromJson(e as Map<String, dynamic>)).toList(),
    );
  }

  final String groupName;
  final String ownerAccount;
  final String? lastSeenAt;
  final int? machineGroupId;
  final int? financeGroupId;
  final int? advertisementGroupId;
  final String? machineScenario;
  final String? serviceHotLine;
  final bool ageVerificationEnabled;
  final int? minimumAge;
  final String? remarks;
  final double? latitude;
  final double? longitude;
  final SlotSummary slotSummary;
  final List<MachineSlot> slots;
}

class SlotSummary {
  const SlotSummary({
    required this.total,
    required this.stocked,
    required this.lowStock,
    required this.empty,
    required this.fault,
  });

  factory SlotSummary.fromJson(Map<String, dynamic> json) {
    return SlotSummary(
      total: json['total'] as int? ?? 0,
      stocked: json['stocked'] as int? ?? 0,
      lowStock: json['low_stock'] as int? ?? 0,
      empty: json['empty'] as int? ?? 0,
      fault: json['fault'] as int? ?? 0,
    );
  }

  final int total;
  final int stocked;
  final int lowStock;
  final int empty;
  final int fault;
}

class MachineSlot {
  const MachineSlot({
    required this.id,
    required this.lineNumber,
    required this.productId,
    required this.productName,
    required this.currentStock,
    required this.maxStock,
    required this.stockAlarmThreshold,
    required this.price,
    required this.isActive,
    required this.isFault,
    required this.status,
  });

  factory MachineSlot.fromJson(Map<String, dynamic> json) {
    return MachineSlot(
      id: json['id'] as int,
      lineNumber: json['line_number'] as int? ?? 0,
      productId: json['product_id'] as int?,
      productName: json['product_name'] as String? ?? '— empty —',
      currentStock: json['current_stock'] as int? ?? 0,
      maxStock: json['max_stock'] as int? ?? 0,
      stockAlarmThreshold: json['stock_alarm_threshold'] as int? ?? 0,
      price: (json['price'] as num?)?.toDouble() ?? 0,
      isActive: json['is_active'] as bool? ?? true,
      isFault: json['is_fault'] as bool? ?? false,
      status: json['status'] as String? ?? 'ok',
    );
  }

  final int id;
  final int lineNumber;
  final int? productId;
  final String productName;
  final int currentStock;
  final int maxStock;
  final int stockAlarmThreshold;
  final double price;
  final bool isActive;
  final bool isFault;
  final String status;
}
