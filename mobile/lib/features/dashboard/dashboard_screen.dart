import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/api/api_client.dart';

class DashboardScreen extends ConsumerStatefulWidget {
  const DashboardScreen({super.key});

  @override
  ConsumerState<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends ConsumerState<DashboardScreen> {
  Map<String, dynamic>? _data;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadDashboard();
  }

  Future<void> _loadDashboard() async {
    try {
      final api = ref.read(apiClientProvider);
      final data = await api.getDashboard();
      setState(() { _data = data; _loading = false; });
    } catch (e) {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('داشبورد')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadDashboard,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  _buildStatsGrid(),
                  const SizedBox(height: 24),
                  Text('املاک اخیر', style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 12),
                  ...?_data?['recent_properties']?.map<Widget>((p) => Card(
                    child: ListTile(
                      title: Text(p['code'] ?? ''),
                      subtitle: Text('${p['type_label']} · ${p['city'] ?? ''}'),
                      trailing: Text(p['status_label'] ?? ''),
                    ),
                  )),
                ],
              ),
            ),
    );
  }

  Widget _buildStatsGrid() {
    final stats = _data?['stats'] as Map<String, dynamic>? ?? {};
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 12,
      crossAxisSpacing: 12,
      childAspectRatio: 1.5,
      children: [
        _StatCard(title: 'کل املاک', value: '${stats['total_properties'] ?? 0}'),
        _StatCard(title: 'فعال', value: '${stats['active_properties'] ?? 0}'),
        _StatCard(title: 'امروز', value: '${stats['today_properties'] ?? 0}'),
        _StatCard(title: 'در حال انقضا', value: '${stats['expiring_soon'] ?? 0}'),
      ],
    );
  }
}

class _StatCard extends StatelessWidget {
  final String title;
  final String value;

  const _StatCard({required this.title, required this.value});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(value, style: Theme.of(context).textTheme.headlineSmall?.copyWith(
              fontWeight: FontWeight.bold,
            )),
            Text(title, style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: Colors.grey,
            )),
          ],
        ),
      ),
    );
  }
}
