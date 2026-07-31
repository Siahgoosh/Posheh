import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';
import '../../core/auth/auth_controller.dart';
import '../../core/constants/privacy_policy.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/input_normalizers.dart';
import '../../core/widgets/app_background.dart';
import '../../core/widgets/app_logo.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/gradient_text.dart';

enum _RegisterStep { privacy, details }

class RegisterScreen extends ConsumerStatefulWidget {
  const RegisterScreen({super.key});

  @override
  ConsumerState<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends ConsumerState<RegisterScreen> {
  _RegisterStep _step = _RegisterStep.privacy;
  final _email = TextEditingController();
  final _username = TextEditingController();
  final _password = TextEditingController();
  final _passwordConfirm = TextEditingController();
  final _mobile = TextEditingController();
  final _firstName = TextEditingController();
  final _lastName = TextEditingController();
  final _officeName = TextEditingController();
  final _officeAddress = TextEditingController();
  final _officeCity = TextEditingController();
  bool _loading = false;
  bool _privacyAccepted = false;
  bool _obscurePass = true;
  String? _error;

  @override
  void dispose() {
    _email.dispose();
    _username.dispose();
    _password.dispose();
    _passwordConfirm.dispose();
    _mobile.dispose();
    _firstName.dispose();
    _lastName.dispose();
    _officeName.dispose();
    _officeAddress.dispose();
    _officeCity.dispose();
    super.dispose();
  }

  Future<void> _completeRegister() async {
    final email = _email.text.trim().toLowerCase();
    final username = _username.text.trim().toLowerCase();
    final mobile = normalizeMobile(_mobile.text);

    if (email.isEmpty || !email.contains('@')) {
      setState(() => _error = 'ایمیل معتبر وارد کنید');
      return;
    }
    if (username.length < 3) {
      setState(() => _error = 'نام کاربری حداقل ۳ کاراکتر');
      return;
    }
    if (_password.text.length < 8) {
      setState(() => _error = 'رمز عبور حداقل ۸ کاراکتر');
      return;
    }
    if (_password.text != _passwordConfirm.text) {
      setState(() => _error = 'تکرار رمز عبور یکسان نیست');
      return;
    }
    if (mobile.length != 11 || !mobile.startsWith('09')) {
      setState(() => _error = 'شماره موبایل معتبر نیست');
      return;
    }
    if (_firstName.text.trim().isEmpty || _lastName.text.trim().isEmpty) {
      setState(() => _error = 'نام و نام خانوادگی الزامی است');
      return;
    }
    if (_officeName.text.trim().isEmpty) {
      setState(() => _error = 'نام دفتر املاک الزامی است');
      return;
    }
    if (_officeAddress.text.trim().isEmpty) {
      setState(() => _error = 'آدرس دفتر الزامی است');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final res = await ref.read(apiClientProvider).register({
        'plan_slug': 'solo',
        'email': email,
        'username': username,
        'password': _password.text,
        'password_confirmation': _passwordConfirm.text,
        'mobile': mobile,
        'first_name': _firstName.text.trim(),
        'last_name': _lastName.text.trim(),
        'office_name': _officeName.text.trim(),
        'office_address': _officeAddress.text.trim(),
        if (_officeCity.text.trim().isNotEmpty) 'office_city': _officeCity.text.trim(),
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
                GradientText(
                  'ثبت‌نام پوشه',
                  style: Theme.of(context)
                      .textTheme
                      .titleLarge
                      ?.copyWith(fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 6),
                const Text(
                  'پنل فردی — بدون پیامک',
                  style: TextStyle(color: AppColors.warning),
                ),
                const SizedBox(height: 24),
                GlassCard(
                  padding: const EdgeInsets.all(20),
                  child: _step == _RegisterStep.privacy
                      ? _privacyStep()
                      : _detailsStep(),
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

  Widget _privacyStep() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(privacyPolicyTitle,
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        const SizedBox(height: 12),
        Container(
          constraints: const BoxConstraints(maxHeight: 320),
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: AppColors.cardFill(true),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: AppColors.cardBorder(true)),
          ),
          child: const SingleChildScrollView(
            child: Text(
              privacyPolicyFullText,
              style: TextStyle(fontSize: 13, height: 1.7, color: AppColors.muted),
            ),
          ),
        ),
        const SizedBox(height: 12),
        CheckboxListTile(
          value: _privacyAccepted,
          onChanged: (v) => setState(() => _privacyAccepted = v ?? false),
          contentPadding: EdgeInsets.zero,
          controlAffinity: ListTileControlAffinity.leading,
          title: const Text(
            'سیاست حریم خصوصی را مطالعه کردم و می‌پذیرم',
            style: TextStyle(fontSize: 13),
          ),
        ),
        if (_error != null) ...[
          const SizedBox(height: 8),
          Text(_error!, style: const TextStyle(color: AppColors.danger)),
        ],
        const SizedBox(height: 12),
        ElevatedButton(
          onPressed: _privacyAccepted
              ? () => setState(() {
                    _step = _RegisterStep.details;
                    _error = null;
                  })
              : null,
          child: const Text('ادامه ثبت‌نام'),
        ),
      ],
    );
  }

  Widget _detailsStep() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const Text('اطلاعات حساب و دفتر',
            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        const SizedBox(height: 4),
        const Text(
          'پلن مشاور مستقل — برای پلن‌های دفتر از وب‌سایت posheapp.ir ثبت‌نام کنید.',
          style: TextStyle(color: AppColors.muted, fontSize: 12, height: 1.5),
        ),
        const SizedBox(height: 14),
        TextField(
          controller: _email,
          keyboardType: TextInputType.emailAddress,
          textDirection: TextDirection.ltr,
          decoration: const InputDecoration(labelText: 'ایمیل *'),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _username,
          textDirection: TextDirection.ltr,
          decoration: const InputDecoration(labelText: 'نام کاربری (انگلیسی) *'),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _password,
          obscureText: _obscurePass,
          decoration: InputDecoration(
            labelText: 'رمز عبور (حداقل ۸ کاراکتر) *',
            suffixIcon: IconButton(
              icon: Icon(_obscurePass
                  ? Icons.visibility_off_outlined
                  : Icons.visibility_outlined),
              onPressed: () => setState(() => _obscurePass = !_obscurePass),
            ),
          ),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _passwordConfirm,
          obscureText: _obscurePass,
          decoration: const InputDecoration(labelText: 'تکرار رمز عبور *'),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _mobile,
          keyboardType: TextInputType.phone,
          textDirection: TextDirection.ltr,
          maxLength: 11,
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          decoration: const InputDecoration(
            labelText: 'شماره موبایل *',
            counterText: '',
            hintText: '09121234567',
          ),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _firstName,
          decoration: const InputDecoration(labelText: 'نام *'),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _lastName,
          decoration: const InputDecoration(labelText: 'نام خانوادگی *'),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _officeName,
          decoration: const InputDecoration(labelText: 'نام دفتر املاک *'),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _officeCity,
          decoration: const InputDecoration(labelText: 'شهر'),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _officeAddress,
          minLines: 2,
          maxLines: 3,
          decoration: const InputDecoration(labelText: 'آدرس دفتر *'),
        ),
        if (_error != null) ...[
          const SizedBox(height: 12),
          Text(_error!, style: const TextStyle(color: AppColors.danger)),
        ],
        const SizedBox(height: 16),
        Row(
          children: [
            TextButton(
              onPressed: _loading
                  ? null
                  : () => setState(() => _step = _RegisterStep.privacy),
              child: const Text('بازگشت'),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: ElevatedButton(
                onPressed: _loading ? null : _completeRegister,
                child: _loading
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('ایجاد حساب'),
              ),
            ),
          ],
        ),
      ],
    );
  }
}
