import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/api/api_client.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/page_shell.dart';

typedef ItemBuilder = Widget Function(BuildContext context, Map<String, dynamic> item);

class DataListScreen extends ConsumerStatefulWidget {
  final String title;
  final Future<List<dynamic>> Function(ApiClient api) loader;
  final ItemBuilder itemBuilder;
  final String emptyText;

  const DataListScreen({
    super.key,
    required this.title,
    required this.loader,
    required this.itemBuilder,
    this.emptyText = 'موردی یافت نشد',
  });

  @override
  ConsumerState<DataListScreen> createState() => _DataListScreenState();
}

class _DataListScreenState extends ConsumerState<DataListScreen> {
  List<dynamic> _items = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = _items.isEmpty;
      _error = null;
    });
    try {
      final items = await widget.loader(ref.read(apiClientProvider));
      if (mounted) setState(() { _items = items; _loading = false; });
    } on ApiException catch (e) {
      if (mounted) setState(() { _error = e.message; _loading = false; });
    } catch (_) {
      if (mounted) setState(() { _error = 'خطا در دریافت اطلاعات'; _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return PageShell(
      title: widget.title,
      actions: [
        IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load),
      ],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(_error!, style: const TextStyle(color: AppColors.muted)),
                      const SizedBox(height: 12),
                      OutlinedButton(onPressed: _load, child: const Text('تلاش مجدد')),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: _items.isEmpty
                      ? ListView(
                          children: [
                            SizedBox(
                              height: MediaQuery.of(context).size.height * 0.5,
                              child: Center(
                                child: Text(widget.emptyText,
                                    style: const TextStyle(color: AppColors.muted)),
                              ),
                            ),
                          ],
                        )
                      : ListView.separated(
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                          itemCount: _items.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 10),
                          itemBuilder: (context, i) => widget.itemBuilder(
                            context,
                            Map<String, dynamic>.from(_items[i] as Map),
                          ),
                        ),
                ),
    );
  }
}

Widget simpleListTile({
  required String title,
  String? subtitle,
  String? trailing,
  VoidCallback? onTap,
}) {
  return GlassCard(
    onTap: onTap,
    child: Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: const TextStyle(fontWeight: FontWeight.w600)),
              if (subtitle != null) ...[
                const SizedBox(height: 4),
                Text(subtitle, style: const TextStyle(color: AppColors.muted, fontSize: 12)),
              ],
            ],
          ),
        ),
        if (trailing != null)
          Text(trailing, style: const TextStyle(color: AppColors.primary, fontSize: 12)),
      ],
    ),
  );
}
