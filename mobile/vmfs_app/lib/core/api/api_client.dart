import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:vmfs_app/config/app_config.dart';
import 'package:vmfs_app/core/api/api_exception.dart';

class ApiClient {
  ApiClient({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  String get _base => AppConfig.apiBaseUrl.replaceAll(RegExp(r'/+$'), '');

  Future<Map<String, dynamic>> getJson(String path) async {
    final response = await _client.get(
      Uri.parse('$_base$path'),
      headers: const {'Accept': 'application/json'},
    );
    return _decodeJson(response);
  }

  Future<Map<String, dynamic>> postJson(
    String path, {
    Map<String, dynamic>? body,
  }) async {
    final response = await _client.post(
      Uri.parse('$_base$path'),
      headers: const {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: body != null ? jsonEncode(body) : null,
    );
    return _decodeJson(response);
  }

  Future<Map<String, dynamic>> uploadMultipart(
    String path, {
    required File file,
    required Map<String, String> fields,
  }) async {
    final request = http.MultipartRequest('POST', Uri.parse('$_base$path'));
    request.headers['Accept'] = 'application/json';
    request.fields.addAll(fields);
    request.files.add(await http.MultipartFile.fromPath('document', file.path));

    final streamed = await _client.send(request);
    final response = await http.Response.fromStream(streamed);
    return _decodeJson(response);
  }

  Map<String, dynamic> _decodeJson(http.Response response) {
    Map<String, dynamic>? data;
    final contentType = response.headers['content-type'] ?? '';

    if (contentType.contains('application/json') && response.body.isNotEmpty) {
      final decoded = jsonDecode(response.body);
      if (decoded is Map<String, dynamic>) {
        data = decoded;
      }
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return data ?? <String, dynamic>{};
    }

    if (data != null) {
      final errors = data['errors'];
      if (errors is Map) {
        final messages = errors.values
            .expand((value) => value is List ? value : [value])
            .map((e) => e.toString())
            .join(' ');
        if (messages.isNotEmpty) {
          throw ApiException(messages, statusCode: response.statusCode);
        }
      }
      final message = data['message'];
      if (message is String && message.isNotEmpty) {
        throw ApiException(message, statusCode: response.statusCode);
      }
    }

    throw ApiException(
      'Request failed (HTTP ${response.statusCode}).',
      statusCode: response.statusCode,
    );
  }

  void dispose() => _client.close();
}
