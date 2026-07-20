import '../../core/network/api_client.dart';
import '../../core/storage/token_storage.dart';
import '../../models/auth_user.dart';
import '../../models/dashboard.dart';
import '../../models/machine.dart';
import '../../models/order.dart';
import '../../models/product.dart';
import '../../models/reports.dart';
import '../../models/support_ticket.dart';

class VmfsRepository {
  VmfsRepository({
    ApiClient? apiClient,
    TokenStorage? tokenStorage,
    void Function()? onUnauthorized,
  }) : _tokenStorage = tokenStorage ?? TokenStorage() {
    _api = apiClient ?? ApiClient(
      tokenStorage: _tokenStorage,
      onUnauthorized: onUnauthorized,
    );
  }

  late final ApiClient _api;
  final TokenStorage _tokenStorage;

  Future<void> warmSession() => _api.warmAuthHeader();

  Future<void> clearSession() => _tokenStorage.clearToken();

  Future<AuthUser> login({required String email, required String password}) async {
    final data = await _api.post('/auth/login', body: {
      'email': email,
      'password': password,
    });

    final token = data['token'] as String?;
    if (token == null || token.isEmpty) {
      throw Exception('Login failed — no token returned.');
    }

    await _tokenStorage.saveToken(token);

    return AuthUser.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<void> logout() async {
    try {
      await _api.post('/auth/logout');
    } finally {
      await _tokenStorage.clearToken();
    }
  }

  Future<AuthUser?> restoreSession() async {
    final token = await _tokenStorage.readToken();
    if (token == null || token.isEmpty) {
      return null;
    }

    try {
      final data = await _api.get('/auth/me');
      return AuthUser.fromJson(data['user'] as Map<String, dynamic>);
    } catch (_) {
      await _tokenStorage.clearToken();
      return null;
    }
  }

  Future<DashboardStats> fetchDashboard() async {
    final data = await _api.get('/dashboard');
    return DashboardStats.fromJson(data);
  }

  Future<List<MachineSummary>> fetchMachines({String? search}) async {
    final data = await _api.get('/machines', query: {
      if (search != null && search.isNotEmpty) 'search': search,
    });
    final list = data['machines'] as List<dynamic>? ?? [];
    return list.map((e) => MachineSummary.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<MachineDetail> fetchMachine(int id) async {
    final data = await _api.get('/machines/$id');
    return MachineDetail.fromJson(data);
  }

  Future<MachineDetail> createMachine({
    required String machineNumber,
    required String machineName,
    String? detailedAddress,
    int? machineGroupId,
    bool isEnabled = true,
    bool ageVerificationEnabled = false,
    int? minimumAge,
    String? remarks,
  }) async {
    final data = await _api.post('/machines', body: {
      'machine_number': machineNumber,
      'machine_name': machineName,
      if (detailedAddress != null && detailedAddress.isNotEmpty) 'detailed_address': detailedAddress,
      if (machineGroupId != null) 'machine_group_id': machineGroupId,
      'is_enabled': isEnabled,
      'age_verification_enabled': ageVerificationEnabled,
      if (minimumAge != null) 'minimum_age': minimumAge,
      if (remarks != null && remarks.isNotEmpty) 'remarks': remarks,
    });
    return MachineDetail.fromJson({'machine': data['machine']});
  }

  Future<MachineDetail> updateMachine({
    required int id,
    String? machineNumber,
    String? machineName,
    String? detailedAddress,
    int? machineGroupId,
    bool? isEnabled,
    bool? ageVerificationEnabled,
    int? minimumAge,
    String? remarks,
  }) async {
    final data = await _api.patch('/machines/$id', body: {
      if (machineNumber != null) 'machine_number': machineNumber,
      if (machineName != null) 'machine_name': machineName,
      if (detailedAddress != null) 'detailed_address': detailedAddress,
      if (machineGroupId != null) 'machine_group_id': machineGroupId,
      if (isEnabled != null) 'is_enabled': isEnabled,
      if (ageVerificationEnabled != null) 'age_verification_enabled': ageVerificationEnabled,
      if (minimumAge != null) 'minimum_age': minimumAge,
      if (remarks != null) 'remarks': remarks,
    });
    return MachineDetail.fromJson({'machine': data['machine']});
  }

  Future<void> deleteMachine(int id) => _api.delete('/machines/$id');

  Future<MachineSlot> createMachineSlot({
    required int machineId,
    required int lineNumber,
    int? productId,
    required double price,
    required int maxStock,
    required int currentStock,
    required int stockAlarmThreshold,
    bool isActive = true,
    bool isFault = false,
  }) async {
    final data = await _api.post('/machines/$machineId/slots', body: {
      'line_number': lineNumber,
      if (productId != null) 'product_id': productId,
      'price': price,
      'max_stock': maxStock,
      'current_stock': currentStock,
      'stock_alarm_threshold': stockAlarmThreshold,
      'is_active': isActive,
      'is_fault': isFault,
    });
    return MachineSlot.fromJson(data['slot'] as Map<String, dynamic>);
  }

  Future<MachineSlot> updateMachineSlot({
    required int machineId,
    required int slotId,
    int? lineNumber,
    int? productId,
    double? price,
    int? maxStock,
    int? currentStock,
    int? stockAlarmThreshold,
    bool? isActive,
    bool? isFault,
    bool clearProduct = false,
  }) async {
    final data = await _api.patch('/machines/$machineId/slots/$slotId', body: {
      if (lineNumber != null) 'line_number': lineNumber,
      if (clearProduct) 'product_id': null,
      if (!clearProduct && productId != null) 'product_id': productId,
      if (price != null) 'price': price,
      if (maxStock != null) 'max_stock': maxStock,
      if (currentStock != null) 'current_stock': currentStock,
      if (stockAlarmThreshold != null) 'stock_alarm_threshold': stockAlarmThreshold,
      if (isActive != null) 'is_active': isActive,
      if (isFault != null) 'is_fault': isFault,
    });
    return MachineSlot.fromJson(data['slot'] as Map<String, dynamic>);
  }

  Future<void> deleteMachineSlot({required int machineId, required int slotId}) =>
      _api.delete('/machines/$machineId/slots/$slotId');

  Future<List<ProductSummary>> fetchProducts({String? search}) async {
    final data = await _api.get('/products', query: {
      if (search != null && search.isNotEmpty) 'search': search,
    });
    final list = data['products'] as List<dynamic>? ?? [];
    return list.map((e) => ProductSummary.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<ProductDetail> fetchProduct(int id) async {
    final data = await _api.get('/products/$id');
    return ProductDetail.fromJson(data);
  }

  Future<ProductSummary> createProduct({
    required String name,
    required double cost,
    double? price,
    String? description,
    String? barcode,
    int? specificationId,
    int? productTagId,
    bool isActive = true,
    bool requiresAgeVerification = false,
    int? minimumAge,
  }) async {
    final data = await _api.post('/products', body: {
      'name': name,
      'cost': cost,
      if (price != null) 'price': price,
      if (description != null && description.isNotEmpty) 'description': description,
      if (barcode != null && barcode.isNotEmpty) 'barcode': barcode,
      if (specificationId != null) 'specification_id': specificationId,
      if (productTagId != null) 'product_tag_id': productTagId,
      'is_active': isActive,
      'requires_age_verification': requiresAgeVerification,
      if (minimumAge != null) 'minimum_age': minimumAge,
    });
    return ProductSummary.fromJson(data['product'] as Map<String, dynamic>);
  }

  Future<ProductSummary> updateProduct({
    required int id,
    String? name,
    double? cost,
    double? price,
    String? description,
    String? barcode,
    int? specificationId,
    int? productTagId,
    bool? isActive,
    bool? requiresAgeVerification,
    int? minimumAge,
  }) async {
    final data = await _api.patch('/products/$id', body: {
      if (name != null) 'name': name,
      if (cost != null) 'cost': cost,
      if (price != null) 'price': price,
      if (description != null) 'description': description,
      if (barcode != null) 'barcode': barcode,
      if (specificationId != null) 'specification_id': specificationId,
      if (productTagId != null) 'product_tag_id': productTagId,
      if (isActive != null) 'is_active': isActive,
      if (requiresAgeVerification != null) 'requires_age_verification': requiresAgeVerification,
      if (minimumAge != null) 'minimum_age': minimumAge,
    });
    return ProductSummary.fromJson(data['product'] as Map<String, dynamic>);
  }

  Future<void> deleteProduct(int id) => _api.delete('/products/$id');

  Future<List<OrderSummary>> fetchOrders() async {
    final data = await _api.get('/orders');
    final list = data['orders'] as List<dynamic>? ?? [];
    return list.map((e) => OrderSummary.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<OrderDetail> fetchOrder(int id) async {
    final data = await _api.get('/orders/$id');
    return OrderDetail.fromJson(data);
  }

  Future<List<SupportTicketSummary>> fetchSupportTickets() async {
    final data = await _api.get('/support-tickets');
    final list = data['tickets'] as List<dynamic>? ?? [];
    return list.map((e) => SupportTicketSummary.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<SupportTicketDetail> fetchSupportTicket(int id) async {
    final data = await _api.get('/support-tickets/$id');
    return SupportTicketDetail.fromJson(data);
  }

  Future<SupportTicketSummary> createSupportTicket({
    required int machineId,
    required String issueDescription,
    String priority = 'normal',
  }) async {
    final data = await _api.post('/support-tickets', body: {
      'machine_id': machineId,
      'issue_description': issueDescription,
      'priority': priority,
    });
    return SupportTicketSummary.fromJson(data['ticket'] as Map<String, dynamic>);
  }

  Future<SupportTicketMessage> sendSupportMessage({
    required int ticketId,
    required String body,
  }) async {
    final data = await _api.post('/support-tickets/$ticketId/messages', body: {'body': body});
    return SupportTicketMessage.fromJson(data['message'] as Map<String, dynamic>);
  }

  Future<Map<String, dynamic>> fetchWallet() async {
    return _api.get('/wallet');
  }

  Future<List<Map<String, dynamic>>> fetchRechargeRecords() async {
    final data = await _api.get('/wallet/recharge-records');
    return (data['records'] as List<dynamic>? ?? [])
        .map((e) => Map<String, dynamic>.from(e as Map))
        .toList();
  }

  Future<ReportsSummary> fetchReportsSummary({String period = '30d'}) async {
    final data = await _api.get('/reports/summary', query: {'period': period});
    return ReportsSummary.fromJson(data);
  }

  Future<List<Map<String, dynamic>>> fetchAdvertisements() async {
    final data = await _api.get('/advertisements');
    return _mapList(data['advertisements']);
  }

  Future<List<Map<String, dynamic>>> fetchAdvertisementGroups() async {
    final data = await _api.get('/advertisement-groups');
    return _mapList(data['groups']);
  }

  Future<List<Map<String, dynamic>>> fetchAdvertisementTags() async {
    final data = await _api.get('/advertisement-tags');
    return _mapList(data['tags']);
  }

  Future<List<Map<String, dynamic>>> fetchCoupons() async {
    final data = await _api.get('/coupons');
    return _mapList(data['coupons']);
  }

  Future<List<Map<String, dynamic>>> fetchLotteries() async {
    final data = await _api.get('/lotteries');
    return _mapList(data['lotteries']);
  }

  Future<List<Map<String, dynamic>>> fetchTeamMembers() async {
    final data = await _api.get('/team-members');
    return _mapList(data['members']);
  }

  Future<List<Map<String, dynamic>>> fetchMachineGroups() async {
    final data = await _api.get('/machine-groups');
    return _mapList(data['groups']);
  }

  Future<List<Map<String, dynamic>>> fetchMachineAlarms() async {
    final data = await _api.get('/machine-alarms');
    return _mapList(data['alarms']);
  }

  Future<List<Map<String, dynamic>>> fetchMachineMap() async {
    final data = await _api.get('/machines-map');
    return _mapList(data['machines']);
  }

  Future<List<Map<String, dynamic>>> fetchProductCategories() async {
    final data = await _api.get('/product-categories');
    return _mapList(data['categories']);
  }

  Future<List<Map<String, dynamic>>> fetchProductTags() async {
    final data = await _api.get('/product-tags');
    return _mapList(data['tags']);
  }

  Future<List<Map<String, dynamic>>> fetchProductTypes() async {
    final data = await _api.get('/product-types');
    return _mapList(data['types']);
  }

  Future<void> rechargeWallet(double amount) async {
    await _api.post('/wallet/recharge', body: {'amount': amount});
  }

  Future<void> updateProfile({String? name, String? timezone, String? phone}) async {
    await _api.patch('/profile', body: {
      if (name != null) 'name': name,
      if (timezone != null) 'timezone': timezone,
      if (phone != null) 'phone': phone,
    });
  }

  Future<void> updatePassword({
    required String currentPassword,
    required String password,
    required String passwordConfirmation,
  }) async {
    await _api.patch('/profile/password', body: {
      'current_password': currentPassword,
      'password': password,
      'password_confirmation': passwordConfirmation,
    });
  }

  Future<void> deleteSupportTicket(int id) => _api.delete('/support-tickets/$id');

  Future<void> updateSupportTicket(int id, {String? status}) async {
    await _api.patch('/support-tickets/$id', body: {
      if (status != null) 'status': status,
    });
  }

  Future<void> createNamedResource(String collection, Map<String, dynamic> values) async {
    await _api.post(collection, body: _nameBody(values));
  }

  Future<void> updateNamedResource(String collection, int id, Map<String, dynamic> values) async {
    await _api.patch('$collection/$id', body: _nameBody(values));
  }

  Future<void> deleteResource(String collection, int id) => _api.delete('$collection/$id');

  Future<void> createMachineGroup(Map<String, dynamic> values) =>
      createNamedResource('machine-groups', values);

  Future<void> updateMachineGroup(int id, Map<String, dynamic> values) =>
      updateNamedResource('machine-groups', id, values);

  Future<void> deleteMachineGroup(int id) => deleteResource('machine-groups', id);

  Future<void> createProductCategory(Map<String, dynamic> values) =>
      _api.post('product-categories', body: {
        'name': values['name'],
        if ((values['value']?.toString().trim().isNotEmpty ?? false)) 'value': values['value'],
      });

  Future<void> updateProductCategory(int id, Map<String, dynamic> values) =>
      _api.patch('product-categories/$id', body: _nameBody(values, extraKeys: ['value']));

  Future<void> deleteProductCategory(int id) => deleteResource('product-categories', id);

  Future<void> createProductTag(Map<String, dynamic> values) =>
      createNamedResource('product-tags', values);

  Future<void> updateProductTag(int id, Map<String, dynamic> values) =>
      updateNamedResource('product-tags', id, values);

  Future<void> deleteProductTag(int id) => deleteResource('product-tags', id);

  Future<void> createProductType(Map<String, dynamic> values) =>
      createNamedResource('product-types', values);

  Future<void> updateProductType(int id, Map<String, dynamic> values) =>
      updateNamedResource('product-types', id, values);

  Future<void> deleteProductType(int id) => deleteResource('product-types', id);

  Future<void> createAdvertisement(Map<String, dynamic> values) async {
    await _api.post('advertisements', body: {
      'title': values['title'],
      'type': values['type']?.toString().isNotEmpty == true ? values['type'] : 'image',
      if (values['link_url']?.toString().isNotEmpty ?? false) 'link_url': values['link_url'],
      if (values['advertiser_name']?.toString().isNotEmpty ?? false) 'advertiser_name': values['advertiser_name'],
    });
  }

  Future<void> updateAdvertisement(int id, Map<String, dynamic> values) async {
    await _api.patch('advertisements/$id', body: {
      if (values['title']?.toString().isNotEmpty ?? false) 'title': values['title'],
      if (values['type']?.toString().isNotEmpty ?? false) 'type': values['type'],
      if (values['link_url']?.toString().isNotEmpty ?? false) 'link_url': values['link_url'],
    });
  }

  Future<void> deleteAdvertisement(int id) => deleteResource('advertisements', id);

  Future<void> createAdvertisementGroup(Map<String, dynamic> values) =>
      createNamedResource('advertisement-groups', values);

  Future<void> updateAdvertisementGroup(int id, Map<String, dynamic> values) =>
      updateNamedResource('advertisement-groups', id, values);

  Future<void> deleteAdvertisementGroup(int id) => deleteResource('advertisement-groups', id);

  Future<void> createAdvertisementTag(Map<String, dynamic> values) =>
      createNamedResource('advertisement-tags', values);

  Future<void> updateAdvertisementTag(int id, Map<String, dynamic> values) =>
      updateNamedResource('advertisement-tags', id, values);

  Future<void> deleteAdvertisementTag(int id) => deleteResource('advertisement-tags', id);

  Future<void> createCoupon(Map<String, dynamic> values) async {
    await _api.post('coupons', body: {
      'name': values['name'],
      'coupon_type': values['coupon_type']?.toString().isNotEmpty == true ? values['coupon_type'] : 'fixed_amount',
      'discount_value': double.tryParse(values['discount_value']?.toString() ?? '') ?? 0,
    });
  }

  Future<void> updateCoupon(int id, Map<String, dynamic> values) async {
    await _api.patch('coupons/$id', body: {
      if (values['name']?.toString().isNotEmpty ?? false) 'name': values['name'],
      if (values['discount_value']?.toString().isNotEmpty ?? false)
        'discount_value': double.tryParse(values['discount_value'].toString()),
    });
  }

  Future<void> deleteCoupon(int id) => deleteResource('coupons', id);

  Future<void> createLottery(Map<String, dynamic> values) async {
    await _api.post('lotteries', body: {
      'name': values['name'],
      'product_id': int.parse(values['product_id'].toString()),
      if (values['machine_no']?.toString().isNotEmpty ?? false) 'machine_no': values['machine_no'],
    });
  }

  Future<void> updateLottery(int id, Map<String, dynamic> values) async {
    await _api.patch('lotteries/$id', body: {
      if (values['name']?.toString().isNotEmpty ?? false) 'name': values['name'],
      if (values['machine_no']?.toString().isNotEmpty ?? false) 'machine_no': values['machine_no'],
    });
  }

  Future<void> deleteLottery(int id) => deleteResource('lotteries', id);

  Map<String, dynamic> _nameBody(Map<String, dynamic> values, {List<String> extraKeys = const []}) {
    final body = <String, dynamic>{'name': values['name']};
    for (final key in extraKeys) {
      if (values[key]?.toString().trim().isNotEmpty ?? false) {
        body[key] = values[key];
      }
    }
    return body;
  }

  List<Map<String, dynamic>> _mapList(dynamic value) {
    return (value as List<dynamic>? ?? []).map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }
}
