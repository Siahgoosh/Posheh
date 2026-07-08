import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';
import '../../core/utils/input_normalizers.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _mobileController = TextEditingController();
  final _otpController = TextEditingController();
  bool _otpSent = false;
  bool _loading = false;
  String? _error;
  String _normalizedMobile = '';

  @override
  void dispose() {
    _mobileController.dispose();
    _otpController.dispose();
    super.dispose();
  }

  void _clearOtpInput() {
    _otpController.clear();
    setState(() {});
  }

  Future<void> _sendOtp() async {
    final mobile = normalizeMobile(_mobileController.text);
    if (mobile.length != 11 || !mobile.startsWith('09')) {
      setState(() => _error = 'شماره موبایل معتبر نیست');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      await api.sendOtp(mobile);
      setState(() {
        _otpSent = true;
        _normalizedMobile = mobile;
      });
      _clearOtpInput();
    } catch (e) {
      setState(() => _error = _extractErrorMessage(e, fallback: 'خطا در ارسال کد'));
    } finally {
      setState(() => _loading = false);
    }
  }

  Future<void> _verifyOtp() async {
    final code = normalizeOtpCode(_otpController.text);
    if (code.length != 6) {
      setState(() => _error = 'کد ۶ رقمی را کامل وارد کنید');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final result = await api.verifyOtp(_normalizedMobile, code);
      const storage = FlutterSecureStorage();
      await storage.write(key: 'token', value: result['token']);
      if (mounted) context.go('/dashboard');
    } catch (e) {
      _clearOtpInput();
      setState(() => _error = _extractErrorMessage(e, fallback: 'کد نامعتبر است'));
    } finally {
      setState(() => _loading = false);
    }
  }

  String _extractErrorMessage(Object error, {required String fallback}) {
    if (error is DioException) {
      final data = error.response?.data;
      if (data is Map<String, dynamic>) {
        final errors = data['errors'];
        if (errors is Map<String, dynamic>) {
          for (final field in ['code', 'mobile']) {
            final messages = errors[field];
            if (messages is List && messages.isNotEmpty) {
              return messages.first.toString();
            }
          }
        }
        final message = data['message'];
        if (message is String && message.isNotEmpty) {
          return message;
        }
      }
    }

    return fallback;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF6366F1), Color(0xFF8B5CF6)],
                  ),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: const Icon(Icons.apartment, color: Colors.white, size: 40),
              ),
              const SizedBox(height: 24),
              Text('پوشه', style: Theme.of(context).textTheme.headlineLarge?.copyWith(
                fontWeight: FontWeight.bold,
              )),
              const SizedBox(height: 8),
              Text('سامانه ابری ثبت املاک', style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: Colors.grey,
              )),
              const SizedBox(height: 48),
              if (!_otpSent) ...[
                TextField(
                  controller: _mobileController,
                  keyboardType: TextInputType.phone,
                  textDirection: TextDirection.ltr,
                  textAlign: TextAlign.center,
                  decoration: const InputDecoration(
                    labelText: 'شماره موبایل',
                    hintText: '09121234567',
                  ),
                ),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _loading ? null : _sendOtp,
                    child: Text(_loading ? 'در حال ارسال...' : 'دریافت کد تأیید'),
                  ),
                ),
              ] else ...[
                Text('کد به $_normalizedMobile ارسال شد'),
                const SizedBox(height: 16),
                TextField(
                  controller: _otpController,
                  keyboardType: TextInputType.number,
                  textDirection: TextDirection.ltr,
                  textAlign: TextAlign.center,
                  maxLength: 6,
                  autofocus: true,
                  decoration: const InputDecoration(
                    labelText: 'کد تأیید',
                    counterText: '',
                  ),
                ),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _loading ? null : _verifyOtp,
                    child: Text(_loading ? 'در حال بررسی...' : 'ورود'),
                  ),
                ),
                const SizedBox(height: 12),
                TextButton(
                  onPressed: _loading
                      ? null
                      : () {
                          setState(() {
                            _otpSent = false;
                            _error = null;
                          });
                          _clearOtpInput();
                        },
                  child: const Text('تغییر شماره'),
                ),
              ],
              if (_error != null) ...[
                const SizedBox(height: 16),
                Text(_error!, style: const TextStyle(color: Colors.red)),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
