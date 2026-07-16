import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';
import '../../core/auth/auth_controller.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/page_shell.dart';

class DashboardScreen extends ConsumerStatefulWidget {
  const DashboardScreen({super.key});

  @override
  ConsumerState<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends ConsumerState<DashboardScreen> {
  Map<String, dynamic>? _data;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = _data == null;
      _error = null;
    });
    try {
      final data = await ref.read(apiClientProvider).getDashboard();
      if (mounted) setState(() { _data = data; _loading = false; });
    } on ApiException catch (e) {
      if (mounted) setState(() { _error = e.message; _loading = false; });
    } catch (_) {
      if (mounted) setState(() { _error = 'خطا در دریافت اطلاعات'; _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(authControllerProvider).user;
    return PageShell(
      title: 'داشبورد',
      actions: [
        IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load),
      ],
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.go('/properties/new'),
        backgroundColor: AppColors.primary,
        foregroundColor: AppColors.primaryFg,
        icon: const Icon(Icons.add_rounded),
        label: const Text('ثبت ملک'),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 96),
                children: [
                  Text(
                    'سلام${user != null ? '، ${user.name}' : ''} 👋',
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 2),
                  const Text('خلاصه وضعیت دفتر املاک',
                      style: TextStyle(color: AppColors.muted)),
                  const SizedBox(height: 20),
                  if (_error != null) _ErrorBanner(_error!, onRetry: _load),
                  _buildStats(),
                  const SizedBox(height: 24),
                  _buildSection(
                    'املاک اخیر',
                    Icons.home_work_outlined,
                    _data?['recent_properties'],
                    emptyText: 'هنوز ملکی ثبت نشده',
                  ),
                  const SizedBox(height: 20),
                  _buildSection(
                    'در حال انقضا',
                    Icons.warning_amber_rounded,
                    _data?['expiring_properties'],
                    emptyText: 'ملکی در حال انقضا نیست',
                    accent: AppColors.warning,
                  ),
                  const SizedBox(height: 20),
                  _buildTasks(),
                  const SizedBox(height: 20),
                  _buildActivities(),
                ],
              ),
            ),
    );
  }

  Widget _buildStats() {
    final stats = _data?['stats'] as Map<String, dynamic>? ?? const {};
    final items = [
      ('کل املاک', stats['total_properties'], Icons.apartment_rounded, AppColors.primary),
      ('املاک فعال', stats['active_properties'], Icons.trending_up_rounded, AppColors.success),
      ('امروز', stats['today_properties'], Icons.schedule_rounded, AppColors.accent),
      ('در حال انقضا', stats['expiring_soon'], Icons.warning_amber_rounded, AppColors.warning),
      ('اعضای تیم', stats['team_members'], Icons.group_outlined, AppColors.primary),
    ];
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 12,
      crossAxisSpacing: 12,
      childAspectRatio: 1.7,
      children: [
        for (final it in items)
          GlassCard(
            padding: const EdgeInsets.all(14),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(9),
                  decoration: BoxDecoration(
                    color: (it.$4).withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(it.$3, color: it.$4, size: 22),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        formatNumber((it.$2 as num?) ?? 0),
                        style: const TextStyle(
                            fontSize: 22, fontWeight: FontWeight.bold),
                      ),
                      Text(it.$1,
                          style: const TextStyle(
                              color: AppColors.muted, fontSize: 12)),
                    ],
                  ),
                ),
              ],
            ),
          ),
      ],
    );
  }

  Widget _buildSection(
    String title,
    IconData icon,
    dynamic list, {
    required String emptyText,
    Color accent = AppColors.primary,
  }) {
    final items = (list as List?) ?? const [];
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Icon(icon, size: 18, color: accent),
              const SizedBox(width: 8),
              Text(title,
                  style: const TextStyle(
                      fontWeight: FontWeight.w700, fontSize: 15)),
            ],
          ),
          const SizedBox(height: 12),
          if (items.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 20),
              child: Center(
                child: Text(emptyText,
                    style: const TextStyle(color: AppColors.muted)),
              ),
            )
          else
            for (final p in items)
              InkWell(
                onTap: () => context.push('/properties/${p['id']}'),
                borderRadius: BorderRadius.circular(12),
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('${p['code'] ?? ''}',
                                style: const TextStyle(
                                    fontWeight: FontWeight.w600)),
                            const SizedBox(height: 2),
                            Text(
                              [p['type_label'], p['city']]
                                  .where((e) => e != null && '$e'.isNotEmpty)
                                  .join(' · '),
                              style: const TextStyle(
                                  color: AppColors.muted, fontSize: 12),
                            ),
                          ],
                        ),
                      ),
                      AppBadge(
                        '${p['status_label'] ?? '—'}',
                        color: accent,
                        outline: true,
                      ),
                    ],
                  ),
                ),
              ),
        ],
      ),
    );
  }

  Widget _buildTasks() {
    final tasks = (_data?['tasks'] as List?) ?? const [];
    if (tasks.isEmpty) return const SizedBox.shrink();
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Row(
            children: [
              Icon(Icons.check_circle_outline_rounded,
                  size: 18, color: AppColors.primary),
              SizedBox(width: 8),
              Text('وظایف',
                  style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
            ],
          ),
          const SizedBox(height: 12),
          for (final t in tasks)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 6),
              child: Row(
                children: [
                  Container(
                    width: 8,
                    height: 8,
                    decoration: BoxDecoration(
                      color: t['status'] == 'completed'
                          ? AppColors.success
                          : AppColors.muted,
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      '${t['title'] ?? ''}',
                      style: TextStyle(
                        fontSize: 13,
                        decoration: t['status'] == 'completed'
                            ? TextDecoration.lineThrough
                            : null,
                        color: t['status'] == 'completed'
                            ? AppColors.muted
                            : null,
                      ),
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildActivities() {
    final acts = (_data?['activities'] as List?) ?? const [];
    if (acts.isEmpty) return const SizedBox.shrink();
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text('فعالیت‌های اخیر',
              style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
          const SizedBox(height: 12),
          for (final a in acts)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 6),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    margin: const EdgeInsets.only(top: 5),
                    width: 8,
                    height: 8,
                    decoration: const BoxDecoration(
                        color: AppColors.primary, shape: BoxShape.circle),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('${a['description'] ?? ''}',
                            style: const TextStyle(fontSize: 13)),
                        const SizedBox(height: 2),
                        Text(
                          [a['user']?['name'], a['created_at_jalali']]
                              .where((e) => e != null && '$e'.isNotEmpty)
                              .join(' · '),
                          style: const TextStyle(
                              color: AppColors.muted, fontSize: 11),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

class _ErrorBanner extends StatelessWidget {
  final String text;
  final VoidCallback onRetry;
  const _ErrorBanner(this.text, {required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.danger.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.danger.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          const Icon(Icons.cloud_off_rounded, color: AppColors.danger),
          const SizedBox(width: 10),
          Expanded(
              child: Text(text,
                  style: const TextStyle(color: AppColors.danger))),
          TextButton(onPressed: onRetry, child: const Text('تلاش مجدد')),
        ],
      ),
    );
  }
}
