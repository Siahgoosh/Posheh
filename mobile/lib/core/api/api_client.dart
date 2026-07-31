import 'dart:io' show Platform;
import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../utils/input_normalizers.dart';

/// Base API URL. Override at build time with:
///   --dart-define=API_URL=https://posheapp.ir/api/v1
///
/// The production server currently serves the API over plain HTTP, so the
/// default uses http:// to avoid TLS handshake failures. Android cleartext is
/// enabled via network_security_config for posheapp.ir.
const String baseUrl = String.fromEnvironment(
  'API_URL',
  defaultValue: 'https://posheapp.ir/api/v1',
);

const storage = FlutterSecureStorage(
  aOptions: AndroidOptions(encryptedSharedPreferences: true),
);

String get clientPlatform {
  if (kIsWeb) return 'web';
  if (Platform.isAndroid) return 'android';
  if (Platform.isIOS) return 'ios';
  if (Platform.isWindows) return 'windows';
  if (Platform.isMacOS) return 'macos';
  if (Platform.isLinux) return 'linux';
  return 'mobile';
}

final dioProvider = Provider<Dio>((ref) {
  final dio = Dio(BaseOptions(
    baseUrl: baseUrl,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    connectTimeout: const Duration(seconds: 30),
    receiveTimeout: const Duration(seconds: 30),
    validateStatus: (status) => status != null && status < 500,
  ));

  dio.interceptors.add(InterceptorsWrapper(
    onRequest: (options, handler) async {
      final token = await storage.read(key: 'token');
      if (token != null) {
        options.headers['Authorization'] = 'Bearer $token';
      }
      handler.next(options);
    },
    onError: (error, handler) {
      if (error.response?.statusCode == 401) {
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

/// Thrown for expected API errors with a user-friendly Persian message.
class ApiException implements Exception {
  final String message;
  final int? statusCode;
  ApiException(this.message, {this.statusCode});
  @override
  String toString() => message;
}

class ApiClient {
  final Dio _dio;

  ApiClient(this._dio);

  static const _fieldLabels = {
    'mobile': 'شماره موبایل',
    'code': 'کد تأیید',
    'first_name': 'نام',
    'last_name': 'نام خانوادگی',
    'office_name': 'نام دفتر',
    'office_phone': 'تلفن دفتر',
    'office_address': 'آدرس دفتر',
    'office_city': 'شهر',
    'plan_slug': 'پلن',
    'registration_token': 'توکن ثبت‌نام',
    'payment': 'پرداخت',
    'purchase_token': 'توکن خرید',
    'product_id': 'شناسه محصول',
  };

  String _localizeError(String field, String raw) {
    if (raw.contains('office name field is required')) {
      return 'نام دفتر الزامی است. در اپ اندروید فقط پنل فردی قابل ثبت‌نام است؛ برای پلن دفتر از وب‌سایت ثبت‌نام کنید.';
    }
    if (raw.contains('required unless plan slug is in solo')) {
      return 'برای پلن‌های دفتر، ثبت‌نام از وب‌سایت posheapp.ir انجام شود.';
    }
    final label = _fieldLabels[field] ?? field;
    if (raw.toLowerCase().contains('required')) {
      return '$label الزامی است.';
    }
    return raw;
  }

  Never _throw(Response? response, String fallback) {
    final data = response?.data;
    if (data is Map) {
      final errors = data['errors'];
      if (errors is Map) {
        for (final entry in errors.entries) {
          final list = entry.value;
          if (list is List && list.isNotEmpty) {
            throw ApiException(
              _localizeError(entry.key.toString(), list.first.toString()),
              statusCode: response?.statusCode,
            );
          }
        }
      }
      final message = data['message'];
      if (message is String && message.isNotEmpty) {
        throw ApiException(message, statusCode: response?.statusCode);
      }
    }
    throw ApiException(fallback, statusCode: response?.statusCode);
  }

  Future<T> _guard<T>(
    Future<Response> Function() request,
    String fallback, {
    required T Function(Response res) ok,
  }) async {
    try {
      final res = await request();
      final code = res.statusCode ?? 0;
      if (code >= 200 && code < 300) return ok(res);
      _throw(res, fallback);
    } on DioException catch (e) {
      if (e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.connectionTimeout ||
          e.type == DioExceptionType.receiveTimeout) {
        throw ApiException('اتصال به سرور برقرار نشد. اینترنت را بررسی کنید.');
      }
      _throw(e.response, fallback);
    }
  }

  Future<Map<String, dynamic>> login(String login, String password) {
    return _guard(
      () => _dio.post('/auth/login', data: {
        'login': login.contains('@') ? login.trim() : login.trim().toLowerCase(),
        'password': password,
        'device_id': 'posheh-$clientPlatform',
        'device_name': 'Posheh ${clientPlatform.toUpperCase()}',
        'platform': clientPlatform,
      }),
      'خطا در ورود',
      ok: (res) => Map<String, dynamic>.from(res.data as Map),
    );
  }

  @Deprecated('Use login() — OTP auth disabled')
  Future<Map<String, dynamic>> sendOtp(String mobile) {
    return _guard(
      () => _dio.post('/auth/otp/send',
          data: {'mobile': normalizeMobile(mobile), 'purpose': 'login'}),
      'خطا در ارسال کد',
      ok: (res) => Map<String, dynamic>.from(res.data as Map),
    );
  }

  Future<Map<String, dynamic>> verifyOtp(String mobile, String code) {
    return _guard(
      () => _dio.post('/auth/otp/verify', data: {
        'mobile': normalizeMobile(mobile),
        'code': normalizeOtpCode(code),
        'device_id': 'posheh-$clientPlatform',
        'device_name': 'Posheh ${clientPlatform.toUpperCase()}',
        'platform': clientPlatform,
      }),
      'کد نامعتبر است',
      ok: (res) => Map<String, dynamic>.from(res.data as Map),
    );
  }

  Future<Map<String, dynamic>> me() {
    return _guard(
      () => _dio.get('/auth/me'),
      'خطا در دریافت اطلاعات کاربر',
      ok: (res) => Map<String, dynamic>.from(res.data as Map),
    );
  }

  Future<void> logout() async {
    try {
      await _dio.post('/auth/logout',
          data: {'device_id': 'posheh-$clientPlatform'});
    } catch (_) {
      // Ignore network errors on logout; token is cleared locally anyway.
    }
  }

  Future<void> logoutAll() async {
    try {
      await _dio.post('/auth/logout-all');
    } catch (_) {}
  }

  Future<List<dynamic>> devices() {
    return _guard(
      () => _dio.get('/auth/devices'),
      'خطا در دریافت دستگاه‌ها',
      ok: (res) => (res.data['data'] as List?) ?? const [],
    );
  }

  Future<Map<String, dynamic>> getDashboard() {
    return _guard(
      () => _dio.get('/dashboard'),
      'خطا در دریافت داشبورد',
      ok: (res) => Map<String, dynamic>.from(res.data as Map),
    );
  }

  Future<Map<String, dynamic>> getProperties({Map<String, dynamic>? params}) {
    return _guard(
      () => _dio.get('/properties', queryParameters: params),
      'خطا در دریافت املاک',
      ok: (res) => Map<String, dynamic>.from(res.data as Map),
    );
  }

  Future<Map<String, dynamic>> getProperty(int id) {
    return _guard(
      () => _dio.get('/properties/$id'),
      'خطا در دریافت ملک',
      ok: (res) => Map<String, dynamic>.from(res.data as Map),
    );
  }

  Future<Map<String, dynamic>> createProperty(Map<String, dynamic> data) {
    return _guard(
      () => _dio.post('/properties', data: data),
      'خطا در ثبت ملک',
      ok: (res) => Map<String, dynamic>.from(res.data as Map),
    );
  }

  Future<List<dynamic>> getList(String path, {Map<String, dynamic>? params}) {
    return _guard(
      () => _dio.get(path, queryParameters: params),
      'خطا در دریافت اطلاعات',
      ok: (res) {
        final data = res.data;
        if (data is Map && data['data'] is List) return data['data'] as List;
        if (data is List) return data;
        return const [];
      },
    );
  }

  Future<Map<String, dynamic>> getData(String path, {Map<String, dynamic>? params}) {
    return _guard(
      () => _dio.get(path, queryParameters: params),
      'خطا در دریافت اطلاعات',
      ok: (res) {
        final data = res.data;
        if (data is Map && data['data'] is Map) {
          return Map<String, dynamic>.from(data['data'] as Map);
        }
        if (data is Map) return Map<String, dynamic>.from(data);
        return const {};
      },
    );
  }

  Future<List<dynamic>> getOwners() => getList('/owners');
  Future<List<dynamic>> getCustomers() => getList('/customers');
  Future<List<dynamic>> getVisits() => getList('/visits');
  Future<List<dynamic>> getFavorites() => getList('/properties', params: {'favorites_only': true});
  Future<List<dynamic>> getCrmDeals() => getList('/crm/deals');
  Future<List<dynamic>> getAccounting() => getList('/accounting');
  Future<List<dynamic>> getCommissions() => getList('/commissions');
  Future<List<dynamic>> getContracts() => getList('/contracts');
  Future<List<dynamic>> getTeam() => getList('/office/team');
  Future<List<dynamic>> getTickets() => getList('/tickets');
  Future<Map<String, dynamic>> getReports() => getData('/reports/dashboard');
  Future<Map<String, dynamic>> getSubscription() => getData('/subscription/current');
  Future<List<dynamic>> getPlans() => getList('/plans');

  Future<Map<String, dynamic>> getFilingSchema() => getData('/filing/schema');

  Future<Map<String, dynamic>> getFilingFields(String category, String transaction) {
    return getData('/filing/fields', params: {
      'property_category': category,
      'transaction_type': transaction,
    });
  }

  Future<Map<String, dynamic>> sendRegisterOtp(String mobile) {
    return _guard(
      () => _dio.post('/auth/otp/send',
          data: {'mobile': normalizeMobile(mobile), 'purpose': 'register'}),
      'خطا در ارسال کد',
      ok: (res) => Map<String, dynamic>.from(res.data as Map),
    );
  }

  Future<Map<String, dynamic>> verifyRegisterOtp(String mobile, String code) {
    return _guard(
      () => _dio.post('/auth/otp/verify', data: {
        'mobile': normalizeMobile(mobile),
        'code': normalizeOtpCode(code),
        'purpose': 'register',
      }),
      'کد نامعتبر است',
      ok: (res) => Map<String, dynamic>.from(res.data as Map),
    );
  }

  Future<Map<String, dynamic>> register(Map<String, dynamic> data) {
    return _guard(
      () => _dio.post('/auth/register', data: data),
      'خطا در ثبت‌نام',
      ok: (res) => Map<String, dynamic>.from(res.data as Map),
    );
  }

  Future<Map<String, dynamic>> verifyBazaarPurchase({
    required int planId,
    required String productId,
    required String purchaseToken,
    String? orderId,
  }) {
    return _guard(
      () => _dio.post('/subscribe/bazaar/verify', data: {
        'plan_id': planId,
        'product_id': productId,
        'purchase_token': purchaseToken,
        if (orderId != null) 'order_id': orderId,
      }),
      'خطا در تأیید خرید',
      ok: (res) => Map<String, dynamic>.from(res.data as Map),
    );
  }
}
