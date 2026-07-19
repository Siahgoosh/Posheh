import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/page_shell.dart';
import '../common/data_list_screen.dart';

class CustomersScreen extends ConsumerStatefulWidget {
  const CustomersScreen({super.key});

  @override
  ConsumerState<CustomersScreen> createState() => _CustomersScreenState();
}

class _CustomersScreenState extends ConsumerState<CustomersScreen> {
  final _searchCtrl = TextEditingController();
  final _nameCtrl = TextEditingController();
  final _mobileCtrl = TextEditingController();
  final _budgetMinCtrl = TextEditingController();
  final _budgetMaxCtrl = TextEditingController();
  final _cityCtrl = TextEditingController();
  String _priority = 'normal';
  String _preferredType = 'sale';
  List<dynamic> _customers = [];
  bool _loading = true;
  bool _saving = false;
  bool _showForm = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _nameCtrl.dispose();
    _mobileCtrl.dispose();
    _budgetMinCtrl.dispose();
    _budgetMaxCtrl.dispose();
    _cityCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = _customers.isEmpty;
      _error = null;
    });
    try {
      final items = await ref.read(apiClientProvider).getCustomers(q: _searchCtrl.text.trim());
      if (mounted) setState(() { _customers = items; _loading = false; });
    } on ApiException catch (e) {
      if (mounted) setState(() { _error = e.message; _loading = false; });
    } catch (_) {
      if (mounted) setState(() { _error = 'خطا در دریافت مشتریان'; _loading = false; });
    }
  }

  Future<void> _create() async {
    if (_nameCtrl.text.trim().isEmpty) return;
    setState(() => _saving = true);
    try {
      await ref.read(apiClientProvider).createCustomer({
        'name': _nameCtrl.text.trim(),
        if (_mobileCtrl.text.trim().isNotEmpty) 'mobile': _mobileCtrl.text.trim(),
        'priority': _priority,
        'preferred_type': _preferredType,
        if (_budgetMinCtrl.text.trim().isNotEmpty) 'budget_min': int.parse(_budgetMinCtrl.text.trim()),
        if (_budgetMaxCtrl.text.trim().isNotEmpty) 'budget_max': int.parse(_budgetMaxCtrl.text.trim()),
        if (_cityCtrl.text.trim().isNotEmpty) 'preferred_city': _cityCtrl.text.trim(),
      });
      _nameCtrl.clear();
      _mobileCtrl.clear();
      _budgetMinCtrl.clear();
      _budgetMaxCtrl.clear();
      _cityCtrl.clear();
      setState(() => _showForm = false);
      await _load();
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return PageShell(
      title: 'مشتریان',
      actions: [
        IconButton(
          icon: Icon(_showForm ? Icons.close_rounded : Icons.person_add_outlined),
          onPressed: () => setState(() => _showForm = !_showForm),
        ),
        IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load),
      ],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  TextField(
                    controller: _searchCtrl,
                    decoration: InputDecoration(
                      hintText: 'جستجو…',
                      prefixIcon: const Icon(Icons.search_rounded),
                      suffixIcon: IconButton(icon: const Icon(Icons.search), onPressed: _load),
                    ),
                    onSubmitted: (_) => _load(),
                  ),
                  if (_showForm) ...[
                    const SizedBox(height: 12),
                    GlassCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const Text('مشتری جدید', style: TextStyle(fontWeight: FontWeight.w700)),
                          const SizedBox(height: 10),
                          TextField(controller: _nameCtrl, decoration: const InputDecoration(labelText: 'نام *')),
                          const SizedBox(height: 8),
                          TextField(controller: _mobileCtrl, decoration: const InputDecoration(labelText: 'موبایل'), keyboardType: TextInputType.phone, textDirection: TextDirection.ltr),
                          const SizedBox(height: 8),
                          DropdownButtonFormField<String>(
                            initialValue: _priority,
                            decoration: const InputDecoration(labelText: 'اولویت'),
                            items: const [
                              DropdownMenuItem(value: 'normal', child: Text('عادی')),
                              DropdownMenuItem(value: 'vip', child: Text('VIP')),
                            ],
                            onChanged: (v) { if (v != null) setState(() => _priority = v); },
                          ),
                          const SizedBox(height: 8),
                          DropdownButtonFormField<String>(
                            initialValue: _preferredType,
                            decoration: const InputDecoration(labelText: 'نوع نیاز'),
                            items: const [
                              DropdownMenuItem(value: 'sale', child: Text('خرید')),
                              DropdownMenuItem(value: 'rent', child: Text('اجاره')),
                            ],
                            onChanged: (v) { if (v != null) setState(() => _preferredType = v); },
                          ),
                          const SizedBox(height: 8),
                          TextField(controller: _budgetMinCtrl, decoration: const InputDecoration(labelText: 'بودجه از'), keyboardType: TextInputType.number, textDirection: TextDirection.ltr),
                          const SizedBox(height: 8),
                          TextField(controller: _budgetMaxCtrl, decoration: const InputDecoration(labelText: 'بودجه تا'), keyboardType: TextInputType.number, textDirection: TextDirection.ltr),
                          const SizedBox(height: 8),
                          TextField(controller: _cityCtrl, decoration: const InputDecoration(labelText: 'شهر ترجیحی')),
                          const SizedBox(height: 10),
                          FilledButton(onPressed: _saving ? null : _create, child: Text(_saving ? 'در حال ثبت…' : 'ثبت مشتری')),
                        ],
                      ),
                    ),
                  ],
                  const SizedBox(height: 16),
                  if (_error != null)
                    Text(_error!, style: const TextStyle(color: AppColors.danger))
                  else if (_customers.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 40),
                      child: Center(child: Text('مشتری ثبت نشده', style: TextStyle(color: AppColors.muted))),
                    )
                  else
                    for (final c in _customers)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: simpleListTile(
                          title: '${c['name'] ?? '—'}',
                          subtitle: [c['mobile'], c['need_label']].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                          trailing: c['priority'] == 'vip' ? 'VIP' : null,
                          onTap: () => context.push('/customers/${c['id']}'),
                        ),
                      ),
                ],
              ),
            ),
    );
  }
}

class CustomerDetailScreen extends ConsumerStatefulWidget {
  final int id;
  const CustomerDetailScreen({super.key, required this.id});

  @override
  ConsumerState<CustomerDetailScreen> createState() => _CustomerDetailScreenState();
}

class _CustomerDetailScreenState extends ConsumerState<CustomerDetailScreen> {
  Map<String, dynamic>? _customer;
  List<dynamic> _matches = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final api = ref.read(apiClientProvider);
      final customer = await api.getCustomer(widget.id);
      List<dynamic> matches = const [];
      try {
        matches = await api.getCustomerMatches(widget.id);
      } catch (_) {}
      if (mounted) setState(() { _customer = customer; _matches = matches; _loading = false; });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final c = _customer;
    return PageShell(
      title: c?['name']?.toString() ?? 'مشتری',
      actions: [IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load)],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : c == null
              ? const Center(child: Text('مشتری یافت نشد', style: TextStyle(color: AppColors.muted)))
              : ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    GlassCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          if (c['mobile'] != null) Text('موبایل: ${c['mobile']}', textDirection: TextDirection.ltr),
                          if (c['need_label'] != null) Text('نیاز: ${c['need_label']}'),
                          if (c['preferred_city'] != null) Text('شهر: ${c['preferred_city']}'),
                          if (c['budget_min'] != null || c['budget_max'] != null)
                            Text('بودجه: ${formatPrice(c['budget_min'] as num?)} تا ${formatPrice(c['budget_max'] as num?)}'),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    const Text('تطبیق هوشمند', style: TextStyle(fontWeight: FontWeight.w700)),
                    const SizedBox(height: 8),
                    if (_matches.isEmpty)
                      const Text('ملک پیشنهادی یافت نشد', style: TextStyle(color: AppColors.muted))
                    else
                      for (final m in _matches)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: simpleListTile(
                            title: '${m['property']?['code'] ?? 'ملک'}',
                            subtitle: 'امتیاز: ${formatNumber(m['score'] as num? ?? 0)}',
                            onTap: () {
                              final pid = m['property']?['id'];
                              if (pid != null) context.push('/properties/$pid');
                            },
                          ),
                        ),
                  ],
                ),
    );
  }
}
