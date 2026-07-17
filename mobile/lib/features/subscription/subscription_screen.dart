import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/api/api_client.dart';
import '../../core/auth/auth_controller.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/page_shell.dart';

class SubscriptionScreen extends ConsumerStatefulWidget {
  final bool renewMode;
  const SubscriptionScreen({super.key, this.renewMode = false});

  @override
  ConsumerState<SubscriptionScreen> createState() => _SubscriptionScreenState();
}

class _SubscriptionScreenState extends ConsumerState<SubscriptionScreen> {
  Map<String, dynamic>? _current;
  List<dynamic> _plans = [];
  bool _loading = true;
  int? _payingPlanId;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = _current == null);
    try {
      final api = ref.read(apiClientProvider);
      final current = await api.getSubscription();
      final plans = await api.getPlans();
      if (mounted) setState(() { _current = current; _plans = plans; _loading = false; });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _subscribe(int planId) async {
    setState(() => _payingPlanId = planId);
    try {
      final res = await ref.read(apiClientProvider).subscribe(planId);
      final redirect = res['redirect_url'] as String?;
      if (redirect != null && redirect.isNotEmpty) {
        final uri = Uri.parse(redirect);
        if (await canLaunchUrl(uri)) {
          await launchUrl(uri, mode: LaunchMode.externalApplication);
        }
      }
      await ref.read(authControllerProvider.notifier).refreshUser();
      await _load();
      if (mounted && widget.renewMode) {
        final user = ref.read(authControllerProvider).user;
        if (user?.hasAccess == true && !user!.subscriptionExpired) {
          context.go('/dashboard');
        }
      }
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _payingPlanId = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(authControllerProvider).user;
    final expired = user?.subscriptionExpired == true || user?.hasAccess == false;

    return PageShell(
      title: widget.renewMode ? 'تمدید اشتراک' : 'اشتراک',
      actions: [IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load)],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (widget.renewMode || expired)
                    GlassCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('دوره آزمایشی یا اشتراک شما به پایان رسیده',
                              style: TextStyle(color: AppColors.warning, fontWeight: FontWeight.bold)),
                          const SizedBox(height: 6),
                          const Text(
                            'برای ادامه استفاده از پوشه، یکی از پلن‌ها را انتخاب و پرداخت کنید.',
                            style: TextStyle(color: AppColors.muted, fontSize: 13, height: 1.5),
                          ),
                        ],
                      ),
                    ),
                  if (user?.onTrial == true && user?.trialLabel != null) ...[
                    const SizedBox(height: 12),
                    GlassCard(
                      child: Text(user!.trialLabel!,
                          style: const TextStyle(color: AppColors.warning, fontWeight: FontWeight.bold)),
                    ),
                  ],
                  if (_current != null && _current!.isNotEmpty && !expired) ...[
                    const SizedBox(height: 12),
                    GlassCard(
                      child: Text(
                        'اشتراک فعال: ${_current!['plan']?['name'] ?? '—'}',
                        style: const TextStyle(fontWeight: FontWeight.bold, color: AppColors.success),
                      ),
                    ),
                  ],
                  const SizedBox(height: 16),
                  for (final p in _plans)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: GlassCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Text('${p['name']}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                            const SizedBox(height: 4),
                            Text(formatPrice(p['monthly_price'] as num? ?? 0),
                                style: const TextStyle(color: AppColors.primary, fontWeight: FontWeight.w700, fontSize: 18)),
                            const Text('ماهانه', style: TextStyle(color: AppColors.muted, fontSize: 12)),
                            if (p['slug'] == 'solo')
                              const Padding(
                                padding: EdgeInsets.only(top: 6),
                                child: Text('۳ روز رایگان — فقط پنل فردی',
                                    style: TextStyle(color: AppColors.warning, fontSize: 12)),
                              ),
                            const SizedBox(height: 12),
                            FilledButton(
                              onPressed: _payingPlanId != null ? null : () => _subscribe(p['id'] as int),
                              child: Text(
                                _payingPlanId == p['id']
                                    ? 'در حال انتقال به درگاه…'
                                    : (p['monthly_price'] as num? ?? 0) > 0
                                        ? 'پرداخت با زیبال'
                                        : 'فعال‌سازی',
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                ],
              ),
            ),
    );
  }
}
