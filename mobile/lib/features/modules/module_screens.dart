import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';
import '../../core/utils/formatters.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/page_shell.dart';
import '../common/data_list_screen.dart';

class OwnersScreen extends StatelessWidget {
  const OwnersScreen({super.key});
  @override
  Widget build(BuildContext context) => DataListScreen(
        title: 'مالکین',
        loader: (api) => api.getOwners(),
        emptyText: 'مالکی ثبت نشده',
        itemBuilder: (_, o) => simpleListTile(
          title: '${o['name'] ?? '—'}',
          subtitle: [o['mobile'], o['properties_count'] != null ? '${formatNumber(o['properties_count'] as num)} ملک' : null]
              .where((e) => e != null && '$e'.isNotEmpty)
              .join(' · '),
        ),
      );
}

class CustomersScreen extends StatelessWidget {
  const CustomersScreen({super.key});
  @override
  Widget build(BuildContext context) => DataListScreen(
        title: 'مشتریان',
        loader: (api) => api.getCustomers(),
        emptyText: 'مشتری ثبت نشده',
        itemBuilder: (_, c) => simpleListTile(
          title: '${c['name'] ?? '—'}',
          subtitle: [c['mobile'], c['need_label']].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
        ),
      );
}

class VisitsScreen extends StatelessWidget {
  const VisitsScreen({super.key});
  @override
  Widget build(BuildContext context) => DataListScreen(
        title: 'بازدیدها',
        loader: (api) => api.getVisits(),
        emptyText: 'بازدیدی ثبت نشده',
        itemBuilder: (_, v) => simpleListTile(
          title: '${v['property']?['code'] ?? v['title'] ?? 'بازدید'}',
          subtitle: [v['visit_at_jalali'], v['status_label']].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
        ),
      );
}

class FavoritesScreen extends StatelessWidget {
  const FavoritesScreen({super.key});
  @override
  Widget build(BuildContext context) => DataListScreen(
        title: 'علاقه‌مندی‌ها',
        loader: (api) => api.getFavorites(),
        emptyText: 'علاقه‌مندی ندارید',
        itemBuilder: (_, p) => simpleListTile(
          title: '${p['code'] ?? ''}',
          subtitle: '${p['type_label'] ?? ''} · ${p['city'] ?? ''}',
          onTap: () => context.push('/properties/${p['id']}'),
        ),
      );
}

class CommissionsScreen extends StatelessWidget {
  const CommissionsScreen({super.key});
  @override
  Widget build(BuildContext context) => DataListScreen(
        title: 'کمیسیون',
        loader: (api) => api.getCommissions(),
        emptyText: 'کمیسیونی ثبت نشده',
        itemBuilder: (_, c) => simpleListTile(
          title: '${c['user']?['name'] ?? c['title'] ?? 'کمیسیون'}',
          subtitle: '${c['status'] == 'paid' ? 'پرداخت‌شده' : 'در انتظار'}',
          trailing: c['commission_amount'] != null ? formatPrice(c['commission_amount'] as num) : null,
        ),
      );
}

class ContractsScreen extends StatelessWidget {
  const ContractsScreen({super.key});
  @override
  Widget build(BuildContext context) => DataListScreen(
        title: 'قراردادها',
        loader: (api) => api.getContracts(),
        emptyText: 'قراردادی صادر نشده',
        itemBuilder: (_, c) => simpleListTile(
          title: '${c['title'] ?? 'قرارداد'}',
          subtitle: '${c['status'] ?? ''}',
        ),
      );
}

class TeamScreen extends StatelessWidget {
  const TeamScreen({super.key});
  @override
  Widget build(BuildContext context) => DataListScreen(
        title: 'تیم',
        loader: (api) => api.getTeam(),
        emptyText: 'عضوی در تیم نیست',
        itemBuilder: (_, m) => simpleListTile(
          title: '${m['name'] ?? ''}',
          subtitle: '${m['role_label'] ?? ''} · ${toPersianDigits('${m['mobile'] ?? ''}')}',
        ),
      );
}

class TicketsScreen extends StatelessWidget {
  const TicketsScreen({super.key});
  @override
  Widget build(BuildContext context) => DataListScreen(
        title: 'پشتیبانی',
        loader: (api) => api.getTickets(),
        emptyText: 'تیکتی ثبت نشده',
        itemBuilder: (_, t) => simpleListTile(
          title: '${t['subject'] ?? 'تیکت'}',
          subtitle: '${t['status'] ?? ''}',
        ),
      );
}

class AccountingScreen extends StatelessWidget {
  const AccountingScreen({super.key});
  @override
  Widget build(BuildContext context) => DataListScreen(
        title: 'حسابداری',
        loader: (api) => api.getAccounting(),
        emptyText: 'تراکنشی ثبت نشده',
        itemBuilder: (_, t) => simpleListTile(
          title: '${t['title'] ?? ''}',
          subtitle: '${t['transaction_date_jalali'] ?? ''} · ${t['type_label'] ?? ''}',
          trailing: t['amount'] != null ? formatPrice(t['amount'] as num) : null,
        ),
      );
}

class CrmScreen extends ConsumerStatefulWidget {
  const CrmScreen({super.key});
  @override
  ConsumerState<CrmScreen> createState() => _CrmScreenState();
}

class _CrmScreenState extends ConsumerState<CrmScreen> {
  List<dynamic> _deals = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final deals = await ref.read(apiClientProvider).getCrmDeals();
      if (mounted) setState(() { _deals = deals; _loading = false; });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return PageShell(
      title: 'مدیریت فروش',
      actions: [IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load)],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: _deals.isEmpty
                  ? ListView(children: const [
                      SizedBox(height: 200, child: Center(child: Text('معامله‌ای ثبت نشده', style: TextStyle(color: AppColors.muted)))),
                    ])
                  : ListView.separated(
                      padding: const EdgeInsets.all(16),
                      itemCount: _deals.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 10),
                      itemBuilder: (_, i) {
                        final d = Map<String, dynamic>.from(_deals[i] as Map);
                        return GlassCard(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('${d['title'] ?? 'معامله'}',
                                  style: const TextStyle(fontWeight: FontWeight.bold)),
                              const SizedBox(height: 6),
                              Text('${d['stage_label'] ?? d['stage'] ?? ''}',
                                  style: const TextStyle(color: AppColors.muted, fontSize: 12)),
                              if (d['value'] != null) ...[
                                const SizedBox(height: 6),
                                Text(formatPrice(d['value'] as num),
                                    style: const TextStyle(color: AppColors.primary)),
                              ],
                            ],
                          ),
                        );
                      },
                    ),
            ),
    );
  }
}

class ReportsScreen extends ConsumerStatefulWidget {
  const ReportsScreen({super.key});
  @override
  ConsumerState<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends ConsumerState<ReportsScreen> {
  Map<String, dynamic>? _data;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final data = await ref.read(apiClientProvider).getReports();
      if (mounted) setState(() { _data = data; _loading = false; });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final props = _data?['properties'] as Map<String, dynamic>? ?? {};
    final crm = _data?['crm'] as Map<String, dynamic>? ?? {};
    final accounting = _data?['accounting'] as Map<String, dynamic>? ?? {};
    return PageShell(
      title: 'گزارش‌ها',
      actions: [IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load)],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                _statCard('ملک فعال', formatNumber(props['active'] as num? ?? 0)),
                _statCard('معامله باز', formatNumber(crm['open_deals'] as num? ?? 0)),
                _statCard('نرخ تبدیل', '${crm['conversion_rate'] ?? 0}%'),
                _statCard('درآمد ماه', formatPrice(accounting['month_income'] as num? ?? 0)),
              ],
            ),
    );
  }

  Widget _statCard(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: GlassCard(
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label, style: const TextStyle(color: AppColors.muted)),
            Text(value, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
          ],
        ),
      ),
    );
  }
}

class SubscriptionScreen extends ConsumerStatefulWidget {
  const SubscriptionScreen({super.key});
  @override
  ConsumerState<SubscriptionScreen> createState() => _SubscriptionScreenState();
}

class _SubscriptionScreenState extends ConsumerState<SubscriptionScreen> {
  Map<String, dynamic>? _current;
  List<dynamic> _plans = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final api = ref.read(apiClientProvider);
      final current = await api.getSubscription();
      final plans = await api.getPlans();
      if (mounted) setState(() { _current = current; _plans = plans; _loading = false; });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return PageShell(
      title: 'اشتراک',
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                if (_current != null && _current!.isNotEmpty)
                  GlassCard(
                    child: Text(
                      'اشتراک فعال: ${_current!['plan']?['name'] ?? '—'}',
                      style: const TextStyle(fontWeight: FontWeight.bold),
                    ),
                  ),
                const SizedBox(height: 12),
                for (final p in _plans)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: GlassCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('${p['name']}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                          const SizedBox(height: 4),
                          Text(formatPrice(p['monthly_price'] as num? ?? 0),
                              style: const TextStyle(color: AppColors.primary, fontWeight: FontWeight.w700)),
                          if (p['slug'] == 'solo')
                            const Padding(
                              padding: EdgeInsets.only(top: 6),
                              child: Text('۴۸ ساعت رایگان', style: TextStyle(color: AppColors.warning, fontSize: 12)),
                            ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
    );
  }
}

class SearchScreen extends ConsumerStatefulWidget {
  const SearchScreen({super.key});
  @override
  ConsumerState<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends ConsumerState<SearchScreen> {
  final _q = TextEditingController();
  List<dynamic> _results = [];
  bool _loading = false;

  Future<void> _search() async {
    setState(() => _loading = true);
    try {
      final data = await ref.read(apiClientProvider).getProperties(
        params: {'q': _q.text.trim()},
      );
      if (mounted) {
        setState(() {
          _results = (data['data'] as List?) ?? const [];
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  void dispose() {
    _q.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return PageShell(
      title: 'جستجو',
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              controller: _q,
              decoration: InputDecoration(
                hintText: 'کد ملک، محله، شهر...',
                prefixIcon: const Icon(Icons.search_rounded),
                suffixIcon: IconButton(icon: const Icon(Icons.search_rounded), onPressed: _search),
              ),
              onSubmitted: (_) => _search(),
            ),
          ),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : ListView.separated(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    itemCount: _results.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 10),
                    itemBuilder: (_, i) {
                      final p = Map<String, dynamic>.from(_results[i] as Map);
                      return simpleListTile(
                        title: '${p['code']}',
                        subtitle: '${p['type_label']} · ${p['city'] ?? ''}',
                        trailing: p['price'] != null ? formatPrice(p['price'] as num) : null,
                        onTap: () => context.push('/properties/${p['id']}'),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }
}
