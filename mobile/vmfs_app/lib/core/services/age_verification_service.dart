import 'dart:io';

import 'package:vmfs_app/core/api/api_client.dart';
import 'package:vmfs_app/core/models/age_verification_session.dart';

class AgeVerificationService {
  AgeVerificationService({ApiClient? apiClient})
      : _api = apiClient ?? ApiClient();

  final ApiClient _api;

  Future<AgeVerificationSession> getSession(String sessionId) async {
    final json = await _api.getJson('/age-verification/sessions/$sessionId');
    return AgeVerificationSession.fromJson(json);
  }

  Future<Map<String, dynamic>> uploadDocument({
    required String sessionId,
    required File document,
    required String documentType,
  }) async {
    return _api.uploadMultipart(
      '/age-verification/sessions/$sessionId/document',
      file: document,
      fields: {'document_type': documentType},
    );
  }
}
