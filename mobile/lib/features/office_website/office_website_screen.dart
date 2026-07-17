import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/api/api_client.dart';
import '../../core/auth/auth_controller.dart';
import '../../core/constants/app_urls.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/page_shell.dart';
import '../common/data_list_screen.dart';

class OfficeWebsiteScreen extends ConsumerStatefulWidget {
  const OfficeWebsiteScreen({super.key});

  @override
  ConsumerState<OfficeWebsiteScreen> createState() => _OfficeWebsiteScreenState();
}

class _OfficeWebsiteScreenState extends ConsumerState<OfficeWebsiteScreen> {
  Map<String, dynamic>? _status;
  List<dynamic> _visitRequests = [];
  List<dynamic> _pendingProps = [];
  bool _loading = true;
  final _subdomainCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  final _postTitleCtrl = TextEditingController();
  final _postBodyCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _subdomainCtrl.dispose();
    _descCtrl.dispose();
    _postTitleCtrl.dispose();
    _postBodyCtrl.dispose();
    super.dispose();
  }

  bool get _hasFeature {
    final user = ref.read(authControllerProvider).user;
    return user?.hasFeature('website_listing') ?? false;
  }

  Future<void> _load() async {
    if (!_hasFeature) {
      setState(() => _loading = false);
      return;
    }
    setState(() => _loading = _status == null);
    try {
      final api = ref.read(apiClientProvider);
      final status = await api.getOfficeWebsiteStatus();
      List<dynamic> visits = const [];
      List<dynamic> pending = const [];
      if (status['website_status'] == 'published') {
        try {
          visits = await api.getOfficeVisitRequests();
          pending = await api.getOfficePendingProperties();
        } catch (_) {}
      }
      if (mounted) {
        setState(() {
          _status = status;
          _visitRequests = visits;
          _pendingProps = pending;
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _requestWebsite() async {
    try {
      await ref.read(apiClientProvider).requestOfficeWebsite(
            subdomain: _subdomainCtrl.text.trim(),
            description: _descCtrl.text.trim(),
          );
      await _load();
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  Future<void> _publishPost() async {
    try {
      await ref.read(apiClientProvider).createOfficeWebsitePost(
            title: _postTitleCtrl.text.trim(),
            body: _postBodyCtrl.text.trim(),
          );
      _postTitleCtrl.clear();
      _postBodyCtrl.clear();
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('پست منتشر شد')));
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!_hasFeature) {
      return const PageShell(
        title: 'وبسایت دفتر',
        body: Center(child: Text('وبسایت اختصاصی در پلن حرفه‌ای فعال است.', style: TextStyle(color: AppColors.muted))),
      );
    }

    final websiteStatus = _status?['website_status'] as String? ?? 'none';
    final published = websiteStatus == 'published';
    final canRequest = websiteStatus == 'none' || websiteStatus == 'rejected' || _status == null;

    return PageShell(
      title: 'وبسایت دفتر',
      actions: [IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load)],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  GlassCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('وضعیت', style: TextStyle(fontWeight: FontWeight.w700)),
                        const SizedBox(height: 8),
                        Text(_statusLabel(websiteStatus), style: TextStyle(color: _statusColor(websiteStatus))),
                        if (_status?['subdomain'] != null)
                          Text('${_status!['subdomain']}.posheapp.ir', textDirection: TextDirection.ltr),
                        if (published && _status?['subdomain'] != null) ...[
                          const SizedBox(height: 8),
                          OutlinedButton.icon(
                            onPressed: () {
                              final uri = Uri.parse('${AppUrls.site}/site/${_status!['subdomain']}');
                              launchUrl(uri, mode: LaunchMode.externalApplication);
                            },
                            icon: const Icon(Icons.open_in_new_rounded, size: 16),
                            label: const Text('مشاهده وبسایت'),
                          ),
                        ],
                      ],
                    ),
                  ),
                  if (canRequest) ...[
                    const SizedBox(height: 12),
                    GlassCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const Text('درخواست وبسایت', style: TextStyle(fontWeight: FontWeight.w700)),
                          const SizedBox(height: 10),
                          TextField(
                            controller: _subdomainCtrl,
                            decoration: const InputDecoration(labelText: 'زیردامنه (انگلیسی)'),
                            textDirection: TextDirection.ltr,
                          ),
                          const SizedBox(height: 8),
                          TextField(controller: _descCtrl, decoration: const InputDecoration(labelText: 'معرفی دفتر'), maxLines: 3),
                          const SizedBox(height: 10),
                          FilledButton(onPressed: _requestWebsite, child: const Text('ارسال برای تأیید')),
                        ],
                      ),
                    ),
                  ],
                  if (published) ...[
                    const SizedBox(height: 12),
                    GlassCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const Text('پست معرفی فایل', style: TextStyle(fontWeight: FontWeight.w700)),
                          TextField(controller: _postTitleCtrl, decoration: const InputDecoration(labelText: 'عنوان')),
                          TextField(controller: _postBodyCtrl, decoration: const InputDecoration(labelText: 'متن'), maxLines: 4),
                          FilledButton(onPressed: _publishPost, child: const Text('انتشار در وبسایت')),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    const Text('فایل‌های در انتظار تأیید', style: TextStyle(fontWeight: FontWeight.w700)),
                    const SizedBox(height: 8),
                    if (_pendingProps.isEmpty)
                      const Text('فایلی در انتظار تأیید نیست', style: TextStyle(color: AppColors.muted))
                    else
                      for (final p in _pendingProps)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: GlassCard(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('کد ${p['code']}', style: const TextStyle(fontWeight: FontWeight.w600)),
                                Text('${p['city'] ?? ''} · ${p['type_label'] ?? ''}', style: const TextStyle(fontSize: 12, color: AppColors.muted)),
                                const SizedBox(height: 8),
                                Row(
                                  children: [
                                    FilledButton(
                                      onPressed: () async {
                                        await ref.read(apiClientProvider).approvePropertyWebsite(p['id'] as int, true);
                                        await _load();
                                      },
                                      child: const Text('تأیید'),
                                    ),
                                    const SizedBox(width: 8),
                                    OutlinedButton(
                                      onPressed: () async {
                                        await ref.read(apiClientProvider).approvePropertyWebsite(p['id'] as int, false);
                                        await _load();
                                      },
                                      child: const Text('رد'),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ),
                    const SizedBox(height: 16),
                    const Text('درخواست‌های بازدید', style: TextStyle(fontWeight: FontWeight.w700)),
                    const SizedBox(height: 8),
                    if (_visitRequests.isEmpty)
                      const Text('درخواستی نیست', style: TextStyle(color: AppColors.muted))
                    else
                      for (final v in _visitRequests)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: simpleListTile(
                            title: '${v['name'] ?? ''}',
                            subtitle: '${v['mobile'] ?? ''} · ${v['property_code'] ?? ''}',
                          ),
                        ),
                  ],
                ],
              ),
            ),
    );
  }

  String _statusLabel(String s) => switch (s) {
        'pending' => 'در انتظار تأیید مدیر کل',
        'approved' => 'تأیید شده — آماده انتشار',
        'published' => 'منتشر شده',
        'rejected' => 'رد شده',
        _ => 'ایجاد نشده',
      };

  Color _statusColor(String s) => switch (s) {
        'published' => AppColors.success,
        'pending' => AppColors.warning,
        'rejected' => AppColors.danger,
        _ => AppColors.muted,
      };
}
