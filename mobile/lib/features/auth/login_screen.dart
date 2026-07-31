import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';
import '../../core/auth/auth_controller.dart';
import '../../core/theme/app_theme.dart';
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

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _loginController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _loading = false;
  bool _obscure = true;
  String? _error;

  @override
  void dispose() {
    _loginController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  bool _looksLikeMobile(String value) {
    final m = normalizeMobile(value);
    return m.length == 11 && m.startsWith('09');
  }

  Future<void> _submit() async {
    final login = _loginController.text.trim();
    final password = _passwordController.text;

    if (login.isEmpty || password.isEmpty) {
      setState(() => _error = 'ایمیل/نام کاربری و رمز عبور را وارد کنید');
      return;
    }

    if (_looksLikeMobile(login)) {
      setState(() => _error =
          'ورود با شماره موبایل غیرفعال است. از ایمیل یا نام کاربری استفاده کنید.');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final res = await ref.read(apiClientProvider).login(login, password);
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
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'خطا در ورود. دوباره تلاش کنید.');
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
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const Row(
                            children: [
                              Icon(Icons.key_rounded,
                                  size: 20, color: AppColors.primary),
                              SizedBox(width: 8),
                              Text('ورود به حساب',
                                  style: TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.w700)),
                            ],
                          ),
                          const SizedBox(height: 18),
                          const Text('ایمیل یا نام کاربری',
                              style:
                                  TextStyle(color: AppColors.muted, fontSize: 13)),
                          const SizedBox(height: 8),
                          TextField(
                            controller: _loginController,
                            keyboardType: TextInputType.emailAddress,
                            textDirection: TextDirection.ltr,
                            autocorrect: false,
                            decoration: const InputDecoration(
                              hintText: 'email@example.com یا username',
                            ),
                            onSubmitted: (_) => _loading ? null : _submit(),
                          ),
                          const SizedBox(height: 14),
                          const Text('رمز عبور',
                              style:
                                  TextStyle(color: AppColors.muted, fontSize: 13)),
                          const SizedBox(height: 8),
                          TextField(
                            controller: _passwordController,
                            obscureText: _obscure,
                            decoration: InputDecoration(
                              hintText: 'رمز عبور',
                              suffixIcon: IconButton(
                                icon: Icon(_obscure
                                    ? Icons.visibility_off_outlined
                                    : Icons.visibility_outlined),
                                onPressed: () =>
                                    setState(() => _obscure = !_obscure),
                              ),
                            ),
                            onSubmitted: (_) => _loading ? null : _submit(),
                          ),
                          if (_error != null) ...[
                            const SizedBox(height: 12),
                            _ErrorText(_error!),
                          ],
                          const SizedBox(height: 16),
                          ElevatedButton(
                            onPressed: _loading ? null : _submit,
                            child: _loading
                                ? const _BtnSpinner()
                                : const Text('ورود'),
                          ),
                          const SizedBox(height: 14),
                          Center(
                            child: TextButton(
                              onPressed: _loading ? null : () => context.go('/register'),
                              child: const Text('حساب ندارید؟ ثبت‌نام'),
                            ),
                          ),
                        ],
                      ),
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
