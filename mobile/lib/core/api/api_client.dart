import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../utils/input_normalizers.dart';

const String baseUrl = String.fromEnvironment(
  'API_URL',
  defaultValue: 'http://localhost:8000/api/v1',
);

final dioProvider = Provider<Dio>((ref) {
  final dio = Dio(BaseOptions(
    baseUrl: baseUrl,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    connectTimeout: const Duration(seconds: 30),
    receiveTimeout: const Duration(seconds: 30),
  ));

  dio.interceptors.add(InterceptorsWrapper(
    onRequest: (options, handler) async {
      const storage = FlutterSecureStorage();
      final token = await storage.read(key: 'token');
      if (token != null) {
        options.headers['Authorization'] = 'Bearer $token';
      }
      handler.next(options);
    },
    onError: (error, handler) {
      if (error.response?.statusCode == 401) {
        const storage = FlutterSecureStorage();
        storage.delete(key: 'token');
      }
      handler.next(error);
    },
  ));

  return dio;
});

final apiClientProvider = Provider<ApiClient>((ref) {
  return ApiClient(ref.watch(dioProvider));
});

class ApiClient {
  final Dio _dio;

  ApiClient(this._dio);

  Future<Map<String, dynamic>> sendOtp(String mobile) async {
    final response = await _dio.post('/auth/otp/send', data: {
      'mobile': normalizeMobile(mobile),
    });
    return response.data;
  }

  Future<Map<String, dynamic>> verifyOtp(String mobile, String code) async {
    final response = await _dio.post('/auth/otp/verify', data: {
      'mobile': normalizeMobile(mobile),
      'code': normalizeOtpCode(code),
      'device_id': 'flutter-mobile',
      'platform': 'android',
    });
    return response.data;
  }

  Future<Map<String, dynamic>> getDashboard() async {
    final response = await _dio.get('/dashboard');
    return response.data;
  }

  Future<Map<String, dynamic>> getProperties({Map<String, dynamic>? params}) async {
    final response = await _dio.get('/properties', queryParameters: params);
    return response.data;
  }

  Future<Map<String, dynamic>> createProperty(Map<String, dynamic> data) async {
    final response = await _dio.post('/properties', data: data);
    return response.data;
  }

  Future<Map<String, dynamic>> getProperty(int id) async {
    final response = await _dio.get('/properties/$id');
    return response.data;
  }
}
