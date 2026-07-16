import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/page_shell.dart';

const _propertyTypes = [
  ('', 'همه'),
  ('sale', 'فروش'),
  ('rent', 'اجاره'),
  ('mortgage', 'رهن'),
  ('pre_sale', 'پیش‌فروش'),
  ('land', 'زمین'),
  ('commercial', 'تجاری'),
];

class PropertiesScreen extends ConsumerStatefulWidget {
  const PropertiesScreen({super.key});

  @override
  ConsumerState<PropertiesScreen> createState() => _PropertiesScreenState();
}

class _PropertiesScreenState extends ConsumerState<PropertiesScreen> {
  final _searchController = TextEditingController();
  List<dynamic> _properties = [];
  int _total = 0;
  bool _loading = true;
  String? _error;
  String _type = '';
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _searchController.dispose();
    super.dispose();
  }

  void _onSearchChanged(String _) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), _load);
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final params = <String, dynamic>{};
      if (_searchController.text.trim().isNotEmpty) {
        params['q'] = _searchController.text.trim();
      }
      if (_type.isNotEmpty) params['type'] = _type;
      final data =
          await ref.read(apiClientProvider).getProperties(params: params);
      if (mounted) {
        setState(() {
          _properties = (data['data'] as List?) ?? const [];
          _total = (data['meta']?['total'] as num?)?.toInt() ??
              _properties.length;
          _loading = false;
        });
      }
    } on ApiException catch (e) {
      if (mounted) setState(() { _error = e.message; _loading = false; });
    } catch (_) {
      if (mounted) setState(() { _error = 'خطا در دریافت املاک'; _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return PageShell(
      title: 'املاک',
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          await context.push('/properties/new');
          _load();
        },
        backgroundColor: AppColors.primary,
        foregroundColor: AppColors.primaryFg,
        icon: const Icon(Icons.add_rounded),
        label: const Text('ثبت ملک'),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
            child: Column(
              children: [
                TextField(
                  controller: _searchController,
                  onChanged: _onSearchChanged,
                  decoration: const InputDecoration(
                    hintText: 'جستجوی سریع...',
                    prefixIcon: Icon(Icons.search_rounded, size: 20),
                  ),
                ),
                const SizedBox(height: 10),
                SizedBox(
                  height: 36,
                  child: ListView.separated(
                    scrollDirection: Axis.horizontal,
                    itemCount: _propertyTypes.length,
                    separatorBuilder: (_, __) => const SizedBox(width: 8),
                    itemBuilder: (context, i) {
                      final t = _propertyTypes[i];
                      final selected = _type == t.$1;
                      return ChoiceChip(
                        label: Text(t.$2),
                        selected: selected,
                        showCheckmark: false,
                        onSelected: (_) {
                          setState(() => _type = t.$1);
                          _load();
                        },
                        labelStyle: TextStyle(
                          color: selected
                              ? AppColors.primaryFg
                              : AppColors.foreground,
                          fontWeight: FontWeight.w600,
                          fontSize: 13,
                        ),
                        selectedColor: AppColors.primary,
                        backgroundColor: Colors.white.withValues(alpha: 0.05),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(999),
                          side: BorderSide(color: AppColors.cardBorder(true)),
                        ),
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off_rounded,
                size: 48, color: AppColors.muted),
            const SizedBox(height: 12),
            Text(_error!, style: const TextStyle(color: AppColors.muted)),
            const SizedBox(height: 12),
            OutlinedButton(onPressed: _load, child: const Text('تلاش مجدد')),
          ],
        ),
      );
    }
    if (_properties.isEmpty) {
      return const Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.filter_alt_off_outlined,
                size: 48, color: AppColors.muted),
            SizedBox(height: 12),
            Text('ملکی یافت نشد', style: TextStyle(color: AppColors.muted)),
          ],
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.builder(
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 96),
        itemCount: _properties.length + 1,
        itemBuilder: (context, i) {
          if (i == 0) {
            return Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Text('${formatNumber(_total)} ملک ثبت شده',
                  style:
                      const TextStyle(color: AppColors.muted, fontSize: 13)),
            );
          }
          final p = _properties[i - 1] as Map<String, dynamic>;
          return Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: _PropertyCard(p),
          );
        },
      ),
    );
  }
}

class _PropertyCard extends StatelessWidget {
  final Map<String, dynamic> p;
  const _PropertyCard(this.p);

  String? get _priceText {
    final price = p['price'] as num?;
    final rent = p['rent'] as num?;
    final deposit = p['deposit'] as num?;
    if (price != null && price > 0) return formatPrice(price);
    if (rent != null && rent > 0) return 'اجاره ${formatPrice(rent)}';
    if (deposit != null && deposit > 0) return 'رهن ${formatPrice(deposit)}';
    return null;
  }

  @override
  Widget build(BuildContext context) {
    return GlassCard(
      padding: EdgeInsets.zero,
      onTap: () => context.push('/properties/${p['id']}'),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(
            height: 120,
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  AppColors.primary.withValues(alpha: 0.18),
                  AppColors.accent.withValues(alpha: 0.18),
                ],
              ),
              borderRadius:
                  const BorderRadius.vertical(top: Radius.circular(16)),
            ),
            child: Icon(Icons.apartment_rounded,
                size: 46, color: AppColors.primary.withValues(alpha: 0.5)),
          ),
          Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text('${p['code'] ?? ''}',
                          style: const TextStyle(
                              fontSize: 17, fontWeight: FontWeight.bold)),
                    ),
                    if (p['type_label'] != null)
                      AppBadge('${p['type_label']}'),
                  ],
                ),
                if (_priceText != null) ...[
                  const SizedBox(height: 8),
                  Text(_priceText!,
                      style: const TextStyle(
                          color: AppColors.primary,
                          fontWeight: FontWeight.w700)),
                ],
                const SizedBox(height: 8),
                Row(
                  children: [
                    if (p['area'] != null) ...[
                      const Icon(Icons.straighten_rounded,
                          size: 15, color: AppColors.muted),
                      const SizedBox(width: 4),
                      Text('${formatNumber(p['area'] as num?)} متر',
                          style: const TextStyle(
                              color: AppColors.muted, fontSize: 13)),
                      const SizedBox(width: 14),
                    ],
                    if (p['rooms'] != null) ...[
                      const Icon(Icons.bed_outlined,
                          size: 15, color: AppColors.muted),
                      const SizedBox(width: 4),
                      Text('${formatNumber(p['rooms'] as num?)} خواب',
                          style: const TextStyle(
                              color: AppColors.muted, fontSize: 13)),
                    ],
                  ],
                ),
                if (p['city'] != null) ...[
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      const Icon(Icons.location_on_outlined,
                          size: 15, color: AppColors.muted),
                      const SizedBox(width: 4),
                      Expanded(
                        child: Text(
                          [p['city'], p['district']]
                              .where((e) => e != null && '$e'.isNotEmpty)
                              .join('، '),
                          style: const TextStyle(
                              color: AppColors.muted, fontSize: 13),
                        ),
                      ),
                    ],
                  ),
                ],
                const SizedBox(height: 10),
                Divider(color: AppColors.cardBorder(true), height: 1),
                const SizedBox(height: 10),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    if (p['permission_label'] != null)
                      AppBadge('${p['permission_label']}', outline: true)
                    else
                      const SizedBox.shrink(),
                    Text('${p['created_at_jalali'] ?? ''}',
                        style: const TextStyle(
                            color: AppColors.muted, fontSize: 11)),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
