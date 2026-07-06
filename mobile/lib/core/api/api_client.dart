import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

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

  // Auth
  Future<Map<String, dynamic>> sendOtp(String mobile) async {
    final response = await _dio.post('/auth/otp/send', data: {'mobile': mobile});
    return response.data;
  }

  Future<Map<String, dynamic>> verifyOtp(String mobile, String code) async {
    final response = await _dio.post('/auth/otp/verify', data: {
      'mobile': mobile,
      'code': code,
      'device_id': 'flutter-mobile',
      'platform': 'android',
    });
    return response.data;
  }

  Future<Map<String, dynamic>> getMe() async {
    final response = await _dio.get('/auth/me');
    return response.data;
  }

  // Dashboard
  Future<Map<String, dynamic>> getDashboard() async {
    final response = await _dio.get('/dashboard');
    return response.data;
  }

  // Properties
  Future<Map<String, dynamic>> getProperties({Map<String, dynamic>? params}) async {
    final response = await _dio.get('/properties', queryParameters: params);
    return response.data;
  }

  Future<Map<String, dynamic>> getFavorites() async {
    final response = await _dio.get('/properties/favorites');
    return response.data;
  }

  Future<Map<String, dynamic>> getProperty(int id) async {
    final response = await _dio.get('/properties/$id');
    return response.data;
  }

  Future<Map<String, dynamic>> getSimilarProperties(int id) async {
    final response = await _dio.get('/properties/$id/similar');
    return response.data;
  }

  Future<Map<String, dynamic>> createProperty(Map<String, dynamic> data) async {
    final response = await _dio.post('/properties', data: data);
    return response.data;
  }

  Future<Map<String, dynamic>> toggleFavorite(int id) async {
    final response = await _dio.post('/properties/$id/favorite');
    return response.data;
  }

  // Tasks
  Future<Map<String, dynamic>> getTasks() async {
    final response = await _dio.get('/tasks');
    return response.data;
  }

  Future<Map<String, dynamic>> createTask(String title) async {
    final response = await _dio.post('/tasks', data: {'title': title});
    return response.data;
  }

  Future<Map<String, dynamic>> updateTask(int id, Map<String, dynamic> data) async {
    final response = await _dio.put('/tasks/$id', data: data);
    return response.data;
  }

  Future<void> deleteTask(int id) async {
    await _dio.delete('/tasks/$id');
  }

  // Team
  Future<List<dynamic>> getTeam() async {
    final response = await _dio.get('/office/team');
    return response.data['data'] ?? [];
  }

  Future<void> inviteMember(String mobile) async {
    await _dio.post('/office/invite', data: {'mobile': mobile});
  }

  // Subscription
  Future<List<dynamic>> getPlans() async {
    final response = await _dio.get('/plans');
    return response.data['data'] ?? [];
  }

  Future<Map<String, dynamic>> getCurrentSubscription() async {
    final response = await _dio.get('/subscription/current');
    return response.data;
  }

  Future<Map<String, dynamic>> subscribe(int planId, String gateway) async {
    final response = await _dio.post('/subscribe', data: {
      'plan_id': planId,
      'gateway': gateway,
    });
    return response.data;
  }

  // Notifications
  Future<Map<String, dynamic>> getNotifications() async {
    final response = await _dio.get('/notifications');
    return response.data;
  }

  Future<void> markNotificationRead(String id) async {
    await _dio.post('/notifications/$id/read');
  }

  // Saved searches
  Future<List<dynamic>> getSavedSearches() async {
    final response = await _dio.get('/saved-searches');
    return response.data['data'] ?? [];
  }

  Future<void> saveSearch(String name, Map<String, dynamic> filters) async {
    await _dio.post('/saved-searches', data: {'name': name, 'filters': filters});
  }

  // Contacts
  Future<Map<String, dynamic>> getContacts() async {
    final response = await _dio.get('/contacts');
    return response.data;
  }

  Future<void> createContact(String name, String mobile) async {
    await _dio.post('/contacts', data: {'name': name, 'mobile': mobile, 'type': 'lead'});
  }
}
