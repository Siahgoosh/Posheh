import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';
import '../../core/auth/auth_controller.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/glass_card.dart';

class SettingsScreen extends ConsumerStatefulWidget {
  const SettingsScreen({super.key});

  @override
  ConsumerState<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends ConsumerState<SettingsScreen> {
  List<dynamic> _devices = [];
  bool _loadingDevices = true;

  @override
  void initState() {
    super.initState();
    _loadDevices();
  }

  Future<void> _loadDevices() async {
    try {
      final devices = await ref.read(apiClientProvider).devices();
      if (mounted) setState(() { _devices = devices; _loadingDevices = false; });
    } catch (_) {
      if (mounted) setState(() => _loadingDevices = false);
    }
  }

  Future<void> _logout({bool all = false}) async {
    await ref.read(authControllerProvider.notifier).logout(allDevices: all);
    if (mounted) context.go('/login');
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(authControllerProvider).user;
    final isDark = ref.watch(themeModeProvider) == ThemeMode.dark;

    return Scaffold(
      appBar: AppBar(title: const Text('تنظیمات')),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
        children: [
          GlassCard(
            padding: EdgeInsets.zero,
            child: Column(
              children: [
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: const BoxDecoration(
                    gradient: AppColors.brandGradient,
                    borderRadius:
                        BorderRadius.vertical(top: Radius.circular(16)),
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 58,
                        height: 58,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.2),
                          shape: BoxShape.circle,
                        ),
                        child: Text(
                          user?.initial ?? '؟',
                          style: const TextStyle(
                              color: Colors.white,
                              fontSize: 24,
                              fontWeight: FontWeight.bold),
                        ),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(user?.name ?? '—',
                                style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 17,
                                    fontWeight: FontWeight.bold)),
                            if (user?.officeName != null)
                              Text(user!.officeName!,
                                  style: TextStyle(
                                      color:
                                          Colors.white.withValues(alpha: 0.85),
                                      fontSize: 13)),
                            if (user?.mobile != null)
                              Padding(
                                padding: const EdgeInsets.only(top: 2),
                                child: Text(
                                  toPersianDigits(user!.mobile),
                                  textDirection: TextDirection.ltr,
                                  style: TextStyle(
                                      color: Colors.white
                                          .withValues(alpha: 0.7),
                                      fontSize: 12),
                                ),
                              ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                if (user?.roleLabel != null)
                  Padding(
                    padding: const EdgeInsets.all(14),
                    child: Row(
                      children: [
                        Text('نقش: ${user!.roleLabel}',
                            style: const TextStyle(
                                color: AppColors.muted, fontSize: 13)),
                      ],
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          GlassCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Row(
                  children: [
                    Icon(Icons.palette_outlined,
                        size: 18, color: AppColors.primary),
                    SizedBox(width: 8),
                    Text('ظاهر',
                        style: TextStyle(
                            fontWeight: FontWeight.w700, fontSize: 15)),
                  ],
                ),
                const SizedBox(height: 8),
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(isDark ? 'حالت تاریک' : 'حالت روشن'),
                  secondary: Icon(
                      isDark ? Icons.dark_mode_rounded : Icons.light_mode_rounded,
                      color: AppColors.primary),
                  value: isDark,
                  activeThumbColor: AppColors.primary,
                  onChanged: (v) => ref
                      .read(themeModeProvider.notifier)
                      .state = v ? ThemeMode.dark : ThemeMode.light,
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          GlassCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Row(
                  children: [
                    Icon(Icons.devices_rounded,
                        size: 18, color: AppColors.primary),
                    SizedBox(width: 8),
                    Text('دستگاه‌های فعال',
                        style: TextStyle(
                            fontWeight: FontWeight.w700, fontSize: 15)),
                  ],
                ),
                const SizedBox(height: 10),
                if (_loadingDevices)
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 12),
                    child: Center(
                        child: SizedBox(
                            height: 20,
                            width: 20,
                            child:
                                CircularProgressIndicator(strokeWidth: 2))),
                  )
                else if (_devices.isEmpty)
                  const Text('دستگاهی ثبت نشده',
                      style: TextStyle(color: AppColors.muted, fontSize: 13))
                else
                  for (final d in _devices)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 6),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('${d['device_name'] ?? d['platform'] ?? '—'}',
                              style: const TextStyle(fontSize: 14)),
                          Text('${d['platform'] ?? ''}',
                              style: const TextStyle(
                                  color: AppColors.muted, fontSize: 12)),
                        ],
                      ),
                    ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          OutlinedButton.icon(
            onPressed: () => _logout(),
            icon: const Icon(Icons.logout_rounded,
                size: 18, color: AppColors.danger),
            label: const Text('خروج از این دستگاه',
                style: TextStyle(color: AppColors.danger)),
            style: OutlinedButton.styleFrom(
              side: BorderSide(color: AppColors.danger.withValues(alpha: 0.4)),
              padding: const EdgeInsets.symmetric(vertical: 14),
            ),
          ),
          const SizedBox(height: 8),
          TextButton(
            onPressed: () => _logout(all: true),
            style: TextButton.styleFrom(foregroundColor: AppColors.danger),
            child: const Text('خروج از همه دستگاه‌ها'),
          ),
        ],
      ),
    );
  }
}
