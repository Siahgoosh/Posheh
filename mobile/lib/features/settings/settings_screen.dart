import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';
import '../../core/auth/auth_controller.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/app_launcher.dart';
import '../../core/utils/formatters.dart';
import '../../core/constants/app_urls.dart';
import 'package:package_info_plus/package_info_plus.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/page_shell.dart';

class SettingsScreen extends ConsumerStatefulWidget {
  const SettingsScreen({super.key});

  @override
  ConsumerState<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends ConsumerState<SettingsScreen> {
  List<dynamic> _devices = [];
  bool _loadingDevices = true;
  String _appVersion = '';
  final _telegramCtrl = TextEditingController();
  final _whatsappCtrl = TextEditingController();
  final _brandNameCtrl = TextEditingController();
  final _apiKeyNameCtrl = TextEditingController();
  List<dynamic> _apiKeys = [];
  String? _plainKey;
  bool _loadingOffice = false;

  @override
  void initState() {
    super.initState();
    _loadDevices();
    _loadVersion();
    _loadManagerData();
  }

  @override
  void dispose() {
    _telegramCtrl.dispose();
    _whatsappCtrl.dispose();
    _brandNameCtrl.dispose();
    _apiKeyNameCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadManagerData() async {
    final user = ref.read(authControllerProvider).user;
    if (!(user?.canManage ?? false)) return;
    try {
      final keys = await ref.read(apiClientProvider).getApiKeys();
      if (mounted) setState(() => _apiKeys = keys);
    } catch (_) {}
  }

  Future<void> _saveOfficeSettings() async {
    setState(() => _loadingOffice = true);
    try {
      await ref.read(apiClientProvider).updateOfficeSettings({
        if (_telegramCtrl.text.isNotEmpty) 'telegram_bot_token': _telegramCtrl.text.trim(),
        if (_whatsappCtrl.text.isNotEmpty) 'whatsapp_phone': _whatsappCtrl.text.trim(),
        if (_brandNameCtrl.text.isNotEmpty) 'brand_name': _brandNameCtrl.text.trim(),
      });
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('تنظیمات دفتر ذخیره شد')));
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _loadingOffice = false);
    }
  }

  Future<void> _createApiKey() async {
    if (_apiKeyNameCtrl.text.trim().isEmpty) return;
    try {
      final res = await ref.read(apiClientProvider).createApiKey(_apiKeyNameCtrl.text.trim());
      setState(() => _plainKey = res['plain_key'] as String?);
      _apiKeyNameCtrl.clear();
      await _loadManagerData();
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  Future<void> _loadVersion() async {
    try {
      final info = await PackageInfo.fromPlatform();
      if (mounted) setState(() => _appVersion = '${info.version} (${info.buildNumber})');
    } catch (_) {}
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

    return PageShell(
      title: 'تنظیمات',
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
          if (user?.canManage == true) ...[
            const SizedBox(height: 16),
            GlassCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.smart_toy_outlined, size: 18, color: AppColors.primary),
                      SizedBox(width: 8),
                      Text('ربات‌ها و برند دفتر', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                    ],
                  ),
                  const SizedBox(height: 10),
                  TextField(controller: _telegramCtrl, decoration: const InputDecoration(labelText: 'توکن ربات تلگرام'), obscureText: true),
                  const SizedBox(height: 8),
                  TextField(controller: _whatsappCtrl, decoration: const InputDecoration(labelText: 'شماره واتساپ'), keyboardType: TextInputType.phone),
                  const SizedBox(height: 8),
                  TextField(controller: _brandNameCtrl, decoration: const InputDecoration(labelText: 'نام برند')),
                  const SizedBox(height: 10),
                  FilledButton(onPressed: _loadingOffice ? null : _saveOfficeSettings, child: const Text('ذخیره تنظیمات دفتر')),
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
                      Icon(Icons.vpn_key_outlined, size: 18, color: AppColors.primary),
                      SizedBox(width: 8),
                      Text('کلید API عمومی', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                    ],
                  ),
                  const SizedBox(height: 10),
                  TextField(controller: _apiKeyNameCtrl, decoration: const InputDecoration(labelText: 'نام کلید')),
                  FilledButton(onPressed: _createApiKey, child: const Text('ایجاد کلید')),
                  if (_plainKey != null) ...[
                    const SizedBox(height: 8),
                    SelectableText(_plainKey!, style: const TextStyle(fontSize: 12, color: AppColors.warning)),
                    const Text('این کلید فقط یک‌بار نمایش داده می‌شود.', style: TextStyle(fontSize: 11, color: AppColors.muted)),
                  ],
                  for (final k in _apiKeys)
                    Padding(
                      padding: const EdgeInsets.only(top: 8),
                      child: Text('${k['name']} · ${k['key_prefix']}…', style: const TextStyle(fontSize: 13)),
                    ),
                ],
              ),
            ),
          ],
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
          const SizedBox(height: 16),
          GlassCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Row(
                  children: [
                    Icon(Icons.link_rounded, size: 18, color: AppColors.primary),
                    SizedBox(width: 8),
                    Text('لینک‌های مفید',
                        style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                  ],
                ),
                const SizedBox(height: 8),
                _linkTile('وب‌سایت پوشه', AppUrls.site, Icons.language_rounded),
                _linkTile('ثبت‌نام ۳ روز رایگان', AppUrls.register, Icons.person_add_outlined),
                _linkTile('دانلود ویندوز', AppUrls.download, Icons.download_rounded),
                _linkTile('وبلاگ آموزشی', AppUrls.blog, Icons.article_outlined),
                _linkTile('حریم خصوصی', AppUrls.privacy, Icons.privacy_tip_outlined),
              ],
            ),
          ),
          if (_appVersion.isNotEmpty) ...[
            const SizedBox(height: 12),
            Center(
              child: Text('نسخه اپلیکیشن $_appVersion',
                  style: const TextStyle(color: AppColors.muted, fontSize: 12)),
            ),
          ],
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

  Widget _linkTile(String label, String url, IconData icon) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: Icon(icon, size: 20, color: AppColors.muted),
      title: Text(label, style: const TextStyle(fontSize: 14)),
      trailing: const Icon(Icons.open_in_new_rounded, size: 16, color: AppColors.muted),
      onTap: () => openAppUrl(url),
    );
  }
}
