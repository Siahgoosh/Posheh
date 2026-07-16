import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';
import '../../core/auth/auth_controller.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../core/utils/input_normalizers.dart';
import '../../core/widgets/app_background.dart';
import '../../core/widgets/app_logo.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/gradient_text.dart';

enum _RegisterStep { plan, mobile, otp, details }

class RegisterScreen extends ConsumerStatefulWidget {
  const RegisterScreen({super.key});

  @override
  ConsumerState<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends ConsumerState<RegisterScreen> {
  _RegisterStep _step = _RegisterStep.plan;
  String _planSlug = 'solo';
  final _mobile = TextEditingController();
  final _otp = TextEditingController();
  final _firstName = TextEditingController();
  final _lastName = TextEditingController();
  String _registrationToken = '';
  List<dynamic> _plans = [];
  bool _loading = false;
  String? _error;
  String? _devHint;
  int _countdown = 0;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _loadPlans();
  }

  @override
  void dispose() {
    _timer?.cancel();
    _mobile.dispose();
    _otp.dispose();
    _firstName.dispose();
    _lastName.dispose();
    super.dispose();
  }

  Future<void> _loadPlans() async {
    try {
      final plans = await ref.read(apiClientProvider).getPlans();
      if (mounted) setState(() => _plans = plans);
    } catch (_) {}
  }

  void _startCountdown() {
    _timer?.cancel();
    setState(() => _countdown = 120);
    _timer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (_countdown <= 1) {
        t.cancel();
        if (mounted) setState(() => _countdown = 0);
      } else if (mounted) {
        setState(() => _countdown--);
      }
    });
  }

  Future<void> _sendOtp() async {
    final mobile = normalizeMobile(_mobile.text);
    if (mobile.length != 11) {
      setState(() => _error = 'شماره موبایل معتبر نیست');
      return;
    }
    setState(() { _loading = true; _error = null; _devHint = null; });
    try {
      final res = await ref.read(apiClientProvider).sendRegisterOtp(mobile);
      setState(() {
        _step = _RegisterStep.otp;
        _devHint = res['dev_hint']?.toString();
      });
      _startCountdown();
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _verifyOtp() async {
    setState(() { _loading = true; _error = null; });
    try {
      final res = await ref.read(apiClientProvider).verifyRegisterOtp(
        _mobile.text,
        _otp.text,
      );
      if (res['needs_registration'] == true) {
        setState(() {
          _registrationToken = res['registration_token']?.toString() ?? '';
          _step = _RegisterStep.details;
        });
      } else if (res['token'] != null && res['user'] is Map) {
        await ref.read(authControllerProvider.notifier).onLoggedIn(
          res['token'].toString(),
          Map<String, dynamic>.from(res['user'] as Map),
        );
        if (mounted) context.go('/dashboard');
      }
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _completeRegister() async {
    if (_firstName.text.trim().isEmpty) {
      setState(() => _error = 'نام را وارد کنید');
      return;
    }
    setState(() { _loading = true; _error = null; });
    try {
      final res = await ref.read(apiClientProvider).register({
        'registration_token': _registrationToken,
        'plan_slug': _planSlug,
        'first_name': _firstName.text.trim(),
        'last_name': _lastName.text.trim(),
        'device_id': 'posheh-$clientPlatform',
        'device_name': 'Posheh ${clientPlatform.toUpperCase()}',
        'platform': clientPlatform,
      });
      final token = res['token']?.toString();
      final user = res['user'];
      if (token != null && user is Map) {
        await ref.read(authControllerProvider.notifier).onLoggedIn(
          token,
          Map<String, dynamic>.from(user),
        );
        if (mounted) context.go('/dashboard');
      }
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: AppBackground(
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Column(
              children: [
                const AppLogo(size: 56),
                const SizedBox(height: 12),
                GradientText('ثبت‌نام پوشه',
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold)),
                const SizedBox(height: 6),
                const Text('۴۸ ساعت رایگان — پنل فردی', style: TextStyle(color: AppColors.warning)),
                const SizedBox(height: 24),
                GlassCard(
                  padding: const EdgeInsets.all(20),
                  child: _buildStep(),
                ),
                const SizedBox(height: 16),
                TextButton(
                  onPressed: () => context.go('/login'),
                  child: const Text('قبلاً ثبت‌نام کرده‌ام — ورود'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildStep() {
    switch (_step) {
      case _RegisterStep.plan:
        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text('انتخاب پلن', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 12),
            ..._plans.map((p) {
              final slug = p['slug']?.toString() ?? '';
              return RadioListTile<String>(
                value: slug,
                groupValue: _planSlug,
                onChanged: (v) => setState(() => _planSlug = v ?? 'solo'),
                title: Text('${p['name']}'),
                subtitle: Text(slug == 'solo' ? '۴۸ ساعت رایگان' : ''),
              );
            }),
            if (_plans.isEmpty)
              RadioListTile<String>(
                value: 'solo',
                groupValue: _planSlug,
                onChanged: null,
                title: const Text('پنل فردی — ۴۸ ساعت رایگان'),
              ),
            ElevatedButton(
              onPressed: () => setState(() => _step = _RegisterStep.mobile),
              child: const Text('ادامه'),
            ),
          ],
        );
      case _RegisterStep.mobile:
        return _otpStep(sendOtp: true);
      case _RegisterStep.otp:
        return _otpStep(sendOtp: false);
      case _RegisterStep.details:
        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text('اطلاعات شما', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 12),
            TextField(controller: _firstName, decoration: const InputDecoration(labelText: 'نام *')),
            const SizedBox(height: 12),
            TextField(controller: _lastName, decoration: const InputDecoration(labelText: 'نام خانوادگی')),
            if (_error != null) ...[const SizedBox(height: 12), Text(_error!, style: const TextStyle(color: AppColors.danger))],
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: _loading ? null : _completeRegister,
              child: _loading ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2)) : const Text('تکمیل ثبت‌نام'),
            ),
          ],
        );
    }
  }

  Widget _otpStep({required bool sendOtp}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(sendOtp ? 'شماره موبایل' : 'کد تأیید',
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        const SizedBox(height: 12),
        if (sendOtp)
          TextField(
            controller: _mobile,
            keyboardType: TextInputType.phone,
            textDirection: TextDirection.ltr,
            textAlign: TextAlign.center,
            maxLength: 11,
            inputFormatters: [FilteringTextInputFormatter.digitsOnly],
            decoration: const InputDecoration(hintText: '09121234567', counterText: ''),
          )
        else ...[
          Text('ارسال به ${toPersianDigits(normalizeMobile(_mobile.text))}',
              style: const TextStyle(color: AppColors.muted, fontSize: 13)),
          if (_devHint != null) Text(_devHint!, style: const TextStyle(color: AppColors.warning)),
          const SizedBox(height: 8),
          TextField(
            controller: _otp,
            keyboardType: TextInputType.number,
            textDirection: TextDirection.ltr,
            textAlign: TextAlign.center,
            maxLength: 6,
            inputFormatters: [FilteringTextInputFormatter.digitsOnly],
            decoration: const InputDecoration(hintText: '------', counterText: ''),
          ),
        ],
        if (_error != null) ...[const SizedBox(height: 12), Text(_error!, style: const TextStyle(color: AppColors.danger))],
        const SizedBox(height: 16),
        ElevatedButton(
          onPressed: _loading ? null : (sendOtp ? _sendOtp : _verifyOtp),
          child: _loading
              ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
              : Text(sendOtp ? 'دریافت کد' : 'تأیید'),
        ),
        if (!sendOtp && _countdown > 0)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: Text('ارسال مجدد (${toPersianDigits(_countdown.toString())})',
                textAlign: TextAlign.center, style: const TextStyle(color: AppColors.muted)),
          ),
      ],
    );
  }
}
