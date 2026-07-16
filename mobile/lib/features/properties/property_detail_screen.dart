import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/api/api_client.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/page_shell.dart';

class PropertyDetailScreen extends ConsumerStatefulWidget {
  final int id;
  const PropertyDetailScreen({super.key, required this.id});

  @override
  ConsumerState<PropertyDetailScreen> createState() => _PropertyDetailScreenState();
}

class _PropertyDetailScreenState extends ConsumerState<PropertyDetailScreen> {
  Map<String, dynamic>? _property;
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
      final res = await ref.read(apiClientProvider).getProperty(widget.id);
      final data = res['data'] ?? res;
      if (mounted) setState(() { _property = Map<String, dynamic>.from(data as Map); _loading = false; });
    } on ApiException catch (e) {
      if (mounted) setState(() { _error = e.message; _loading = false; });
    } catch (_) {
      if (mounted) setState(() { _error = 'خطا در دریافت ملک'; _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = _property;
    return PageShell(
      title: p?['code']?.toString() ?? 'جزئیات ملک',
      actions: [IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load)],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!, style: const TextStyle(color: AppColors.muted)))
              : ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
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
