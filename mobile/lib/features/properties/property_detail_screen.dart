import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter/services.dart';
import '../../core/api/api_client.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/page_shell.dart';
import '../common/data_list_screen.dart';

class PropertyDetailScreen extends ConsumerStatefulWidget {
  final int id;
  const PropertyDetailScreen({super.key, required this.id});

  @override
  ConsumerState<PropertyDetailScreen> createState() => _PropertyDetailScreenState();
}

class _PropertyDetailScreenState extends ConsumerState<PropertyDetailScreen> {
  Map<String, dynamic>? _property;
  List<dynamic> _similar = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final api = ref.read(apiClientProvider);
      final res = await api.getProperty(widget.id);
      final data = res['data'] ?? res;
      List<dynamic> similar = const [];
      try {
        similar = await api.getSimilarProperties(widget.id);
      } catch (_) {}
      if (mounted) {
        setState(() {
          _property = Map<String, dynamic>.from(data as Map);
          _similar = similar;
          _loading = false;
        });
      }
    } on ApiException catch (e) {
      if (mounted) setState(() { _error = e.message; _loading = false; });
    } catch (_) {
      if (mounted) setState(() { _error = 'خطا در دریافت ملک'; _loading = false; });
    }
  }

  Future<void> _copyAdText() async {
    try {
      final res = await ref.read(apiClientProvider).getPropertyShareMessage(widget.id);
      final text = res['message'] as String? ?? '';
      if (text.isNotEmpty) {
        await Clipboard.setData(ClipboardData(text: text));
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('متن آگهی کپی شد')));
        }
      }
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  void _shareSheet() {
    showModalBottomSheet(
      context: context,
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.content_copy_rounded),
              title: const Text('کپی متن آگهی'),
              onTap: () { Navigator.pop(ctx); _copyAdText(); },
            ),
            ListTile(
              leading: const Icon(Icons.share_rounded),
              title: const Text('ثبت اشتراک در سیستم'),
              onTap: () async {
                Navigator.pop(ctx);
                try {
                  await ref.read(apiClientProvider).shareProperty(widget.id, channel: 'whatsapp');
                  if (mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('اشتراک ثبت شد')));
                  }
                } on ApiException catch (e) {
                  if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
                }
              },
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final p = _property;
    final media = (p?['media'] as List?) ?? const [];
    return PageShell(
      title: p?['code']?.toString() ?? 'جزئیات ملک',
      actions: [
        if (p != null) ...[
          IconButton(icon: const Icon(Icons.ios_share_rounded), onPressed: _shareSheet),
          IconButton(
            icon: Icon(p['is_favorite'] == true ? Icons.star_rounded : Icons.star_outline_rounded),
            color: p['is_favorite'] == true ? AppColors.warning : null,
            onPressed: () async {
              try {
                await ref.read(apiClientProvider).togglePropertyFavorite(widget.id);
                await _load();
              } on ApiException catch (e) {
                if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
              }
            },
          ),
          IconButton(
            icon: const Icon(Icons.edit_outlined),
            onPressed: () => context.push('/properties/${widget.id}/edit'),
          ),
        ],
        IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load),
      ],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!, style: const TextStyle(color: AppColors.muted)))
              : ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    if (media.isNotEmpty) ...[
                      SizedBox(
                        height: 180,
                        child: ListView.separated(
                          scrollDirection: Axis.horizontal,
                          itemCount: media.length,
                          separatorBuilder: (_, __) => const SizedBox(width: 8),
                          itemBuilder: (_, i) {
                            final m = media[i] as Map;
                            final url = m['url'] as String?;
                            if (url == null) return const SizedBox.shrink();
                            return ClipRRect(
                              borderRadius: BorderRadius.circular(12),
                              child: Image.network(url, width: 240, height: 180, fit: BoxFit.cover),
                            );
                          },
                        ),
                      ),
                      const SizedBox(height: 12),
                    ],
                    GlassCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text('${p!['code']}',
                                    style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                              ),
                              if (p['type_label'] != null) AppBadge('${p['type_label']}'),
                            ],
                          ),
                          if (p['price'] != null) ...[
                            const SizedBox(height: 12),
                            Text(formatPrice(p['price'] as num),
                                style: const TextStyle(color: AppColors.primary, fontSize: 20, fontWeight: FontWeight.bold)),
                          ],
                          const SizedBox(height: 12),
                          if (p['status_label'] != null) AppBadge('${p['status_label']}', outline: true),
                          if (p['quality_score'] != null) ...[
                            const SizedBox(height: 8),
                            Text('امتیاز کیفیت: ${formatNumber(p['quality_score'] as num)}',
                                style: const TextStyle(color: AppColors.muted, fontSize: 12)),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),
                    GlassCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('مشخصات', style: TextStyle(fontWeight: FontWeight.bold)),
                          const SizedBox(height: 10),
                          if (p['area'] != null) _row('متراژ', '${formatNumber(p['area'] as num)} متر'),
                          if (p['rooms'] != null) _row('خواب', formatNumber(p['rooms'] as num)),
                          if (p['city'] != null) _row('شهر', '${p['city']}'),
                          if (p['district'] != null) _row('منطقه', '${p['district']}'),
                          if (p['address'] != null) _row('آدرس', '${p['address']}'),
                          if (p['created_at_jalali'] != null) _row('ثبت', '${p['created_at_jalali']}'),
                          if (p['expires_at_jalali'] != null) _row('انقضا', '${p['expires_at_jalali']}'),
                        ],
                      ),
                    ),
                    if (p['description'] != null && '$p[description]'.isNotEmpty) ...[
                      const SizedBox(height: 12),
                      GlassCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('توضیحات', style: TextStyle(fontWeight: FontWeight.bold)),
                            const SizedBox(height: 8),
                            Text('${p['description']}'),
                          ],
                        ),
                      ),
                    ],
                    if (_similar.isNotEmpty) ...[
                      const SizedBox(height: 16),
                      const Text('املاک مشابه', style: TextStyle(fontWeight: FontWeight.w700)),
                      const SizedBox(height: 8),
                      for (final s in _similar)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: simpleListTile(
                            title: '${s['code'] ?? s['property']?['code'] ?? ''}',
                            subtitle: '${s['type_label'] ?? s['property']?['type_label'] ?? ''}',
                            onTap: () {
                              final pid = s['id'] ?? s['property']?['id'];
                              if (pid != null) context.push('/properties/$pid');
                            },
                          ),
                        ),
                    ],
                  ],
                ),
    );
  }

  Widget _row(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(width: 72, child: Text(label, style: const TextStyle(color: AppColors.muted, fontSize: 13))),
          Expanded(child: Text(value, style: const TextStyle(fontSize: 13))),
        ],
      ),
    );
  }
}
