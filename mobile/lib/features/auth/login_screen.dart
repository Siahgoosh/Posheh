import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';
import '../../core/auth/auth_controller.dart';
import '../../core/constants/app_urls.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../core/utils/input_normalizers.dart';
import '../../core/widgets/app_background.dart';
import '../../core/widgets/app_logo.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/gradient_text.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

enum _Step { mobile, otp }

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _mobileController = TextEditingController();
  final _otpController = TextEditingController();

  _Step _step = _Step.mobile;
  bool _loading = false;
  String? _error;
  String? _devHint;
  String _normalizedMobile = '';
  int _countdown = 0;
  Timer? _timer;

  @override
  void dispose() {
    _timer?.cancel();
    _mobileController.dispose();
    _otpController.dispose();
    super.dispose();
  }

  void _startCountdown() {
    _timer?.cancel();
    setState(() => _countdown = 120);
    _timer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (_countdown <= 1) {
        t.cancel();
        setState(() => _countdown = 0);
      } else {
        setState(() => _countdown--);
      }
    });
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
      _devHint = null;
    });
    try {
      final res = await ref.read(apiClientProvider).sendOtp(mobile);
      _otpController.clear();
      setState(() {
        _normalizedMobile = mobile;
        _step = _Step.otp;
        final hint = res['dev_hint'];
        _devHint = hint is String && hint.isNotEmpty ? hint : null;
      });
      _startCountdown();
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'خطا در ارسال کد');
    } finally {
      if (mounted) setState(() => _loading = false);
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
      final res =
          await ref.read(apiClientProvider).verifyOtp(_normalizedMobile, code);

      if (res['needs_registration'] == true) {
        _otpController.clear();
        setState(() => _error =
            'این شماره ثبت‌نام نشده. روی «ثبت‌نام رایگان» بزنید یا به ${AppUrls.register} بروید.');
        return;
      }

      final token = res['token']?.toString();
      final user = res['user'];
      if (token == null || user is! Map) {
        setState(() => _error = 'پاسخ نامعتبر از سرور');
        return;
      }
      await ref
          .read(authControllerProvider.notifier)
          .onLoggedIn(token, Map<String, dynamic>.from(user));
      if (mounted) context.go('/dashboard');
    } on ApiException catch (e) {
      _otpController.clear();
      setState(() => _error = e.message);
    } catch (_) {
      _otpController.clear();
      setState(() => _error = 'خطا در تأیید کد. دوباره تلاش کنید.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: AppBackground(
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 420),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const AppLogo(size: 64),
                    const SizedBox(height: 16),
                    GradientText(
                      'پوشه',
                      style: Theme.of(context)
                          .textTheme
                          .headlineMedium
                          ?.copyWith(fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 6),
                    const Text(
                      'سامانه ابری ثبت و مدیریت املاک',
                      style: TextStyle(color: AppColors.muted),
                    ),
                    const SizedBox(height: 28),
                    GlassCard(
                      padding: const EdgeInsets.all(20),
                      child: _step == _Step.mobile
                          ? _buildMobileForm()
                          : _buildOtpForm(),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _sectionTitle(String text) {
    return Row(
      children: [
        const Icon(Icons.smartphone_rounded,
            size: 20, color: AppColors.primary),
        const SizedBox(width: 8),
        Text(text,
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
      ],
    );
  }

  Widget _buildMobileForm() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _sectionTitle('ورود با موبایل'),
        const SizedBox(height: 18),
        const Text('شماره موبایل',
            style: TextStyle(color: AppColors.muted, fontSize: 13)),
        const SizedBox(height: 8),
        TextField(
          controller: _mobileController,
          keyboardType: TextInputType.phone,
          textDirection: TextDirection.ltr,
          textAlign: TextAlign.center,
          maxLength: 11,
          style: const TextStyle(fontSize: 18, letterSpacing: 3),
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          decoration: const InputDecoration(
            counterText: '',
            hintText: '09121234567',
          ),
          onSubmitted: (_) => _loading ? null : _sendOtp(),
        ),
        if (_error != null) ...[
          const SizedBox(height: 12),
          _ErrorText(_error!),
        ],
        const SizedBox(height: 16),
        ElevatedButton(
          onPressed: _loading ? null : _sendOtp,
          child: _loading
              ? const _BtnSpinner()
              : const Text('دریافت کد تأیید'),
        ),
        const SizedBox(height: 14),
        Center(
          child: TextButton(
            onPressed: _loading ? null : () => context.go('/register'),
            child: const Text('ثبت‌نام رایگان ۴۸ ساعته'),
          ),
        ),
      ],
    );
  }

  Widget _buildOtpForm() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _sectionTitle('تأیید کد'),
        const SizedBox(height: 18),
        Text(
          'کد تأیید به ${toPersianDigits(_normalizedMobile)} ارسال شد',
          style: const TextStyle(color: AppColors.muted, fontSize: 13),
        ),
        if (_devHint != null) ...[
          const SizedBox(height: 10),
          Center(
            child: Text(_devHint!,
                style: const TextStyle(
                    color: AppColors.warning, fontWeight: FontWeight.w600)),
          ),
        ],
        const SizedBox(height: 14),
        TextField(
          controller: _otpController,
          keyboardType: TextInputType.number,
          textDirection: TextDirection.ltr,
          textAlign: TextAlign.center,
          maxLength: 6,
          autofocus: true,
          style: const TextStyle(
              fontSize: 26, letterSpacing: 8, fontWeight: FontWeight.bold),
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          decoration: const InputDecoration(
            counterText: '',
            hintText: '––––––',
          ),
          onSubmitted: (_) => _loading ? null : _verifyOtp(),
        ),
        if (_error != null) ...[
          const SizedBox(height: 12),
          _ErrorText(_error!),
        ],
        const SizedBox(height: 16),
        ElevatedButton(
          onPressed: _loading ? null : _verifyOtp,
          child: _loading ? const _BtnSpinner() : const Text('ورود'),
        ),
        const SizedBox(height: 14),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            TextButton(
              onPressed: _loading
                  ? null
                  : () {
                      _otpController.clear();
                      setState(() {
                        _step = _Step.mobile;
                        _error = null;
                        _devHint = null;
                      });
                    },
              style: TextButton.styleFrom(foregroundColor: AppColors.muted),
              child: const Text('تغییر شماره'),
            ),
            if (_countdown > 0)
              Text(
                'ارسال مجدد (${toPersianDigits(_countdown.toString())})',
                style: const TextStyle(color: AppColors.muted, fontSize: 13),
              )
            else
              TextButton(
                onPressed: _loading ? null : _sendOtp,
                child: const Text('ارسال مجدد'),
              ),
          ],
        ),
      ],
    );
  }
}

class _ErrorText extends StatelessWidget {
  final String text;
  const _ErrorText(this.text);

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: AppColors.danger.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.danger.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          const Icon(Icons.error_outline_rounded,
              size: 18, color: AppColors.danger),
          const SizedBox(width: 8),
          Expanded(
            child: Text(text,
                style: const TextStyle(
                    color: AppColors.danger, fontSize: 13, height: 1.5)),
          ),
        ],
      ),
    );
  }
}

class _BtnSpinner extends StatelessWidget {
  const _BtnSpinner();
  @override
  Widget build(BuildContext context) {
    return const SizedBox(
      height: 20,
      width: 20,
      child: CircularProgressIndicator(
        strokeWidth: 2.4,
        color: AppColors.primaryFg,
      ),
    );
  }
}
