import 'package:vmfs_app/core/api/api_client.dart';
import 'package:vmfs_app/core/models/lottery_lookup_result.dart';

class LotteryService {
  LotteryService({ApiClient? apiClient}) : _api = apiClient ?? ApiClient();

  final ApiClient _api;

  Future<LotteryLookupResult> lookupCode(String code) async {
    final json = await _api.postJson('/lottery-codes/lookup', body: {'code': code});
    final data = json['data'];

    if (data is! List || data.isEmpty) {
      return const LotteryLookupResult(
        canVend: false,
        message: 'Code not found. Please check the code and try again.',
      );
    }

    final first = data.first;
    if (first is Map<String, dynamic>) {
      return LotteryLookupResult.fromJson(first);
    }

    return const LotteryLookupResult(
      canVend: false,
      message: 'Unexpected response from server.',
    );
  }
}
