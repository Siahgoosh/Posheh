import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/api/api_client.dart';

class SubscriptionScreen extends ConsumerStatefulWidget {
  const SubscriptionScreen({super.key});

  @override
  ConsumerState<SubscriptionScreen> createState() => _SubscriptionScreenState();
}

class _SubscriptionScreenState extends ConsumerState<SubscriptionScreen> {
  List<dynamic> _plans = [];
  Map<String, dynamic>? _current;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final api = ref.read(apiClientProvider);
      final plans = await api.getPlans();
      final current = await api.getCurrentSubscription();
      setState(() {
        _plans = plans;
        _current = current['data'];
        _loading = false;
      });
    } catch (_) {
      setState(() => _loading = false);
    }
  }

  Future<void> _subscribe(int planId) async {
    try {
      final api = ref.read(apiClientProvider);
      final result = await api.subscribe(planId, 'wallet');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result['message'] ?? 'انجام شد')),
        );
        _load();
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('خطا در خرید اشتراک')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('اشتراک')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                if (_current != null)
                  Card(
                    color: Theme.of(context).colorScheme.primaryContainer,
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Text('اشتراک فعال: ${_current!['plan']?['name'] ?? 'فعال'}'),
                    ),
                  ),
                const SizedBox(height: 16),
                ..._plans.map((plan) => Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(plan['name'] ?? '', style: Theme.of(context).textTheme.titleLarge),
                        const SizedBox(height: 4),
                        Text('${plan['monthly_price']} تومان / ماه'),
                        Text('تا ${plan['max_properties']} ملک'),
                        const SizedBox(height: 12),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: () => _subscribe(plan['id']),
                            child: const Text('خرید با کیف پول'),
                          ),
                        ),
                      ],
                    ),
                  ),
                )),
              ],
            ),
    );
  }
}
