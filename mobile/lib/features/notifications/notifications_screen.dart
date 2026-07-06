import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/api/api_client.dart';

class NotificationsScreen extends ConsumerStatefulWidget {
  const NotificationsScreen({super.key});

  @override
  ConsumerState<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends ConsumerState<NotificationsScreen> {
  List<dynamic> _items = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final api = ref.read(apiClientProvider);
      final data = await api.getNotifications();
      setState(() {
        _items = data['data'] ?? [];
        _loading = false;
      });
    } catch (_) {
      setState(() => _loading = false);
    }
  }

  Future<void> _markRead(String id) async {
    final api = ref.read(apiClientProvider);
    await api.markNotificationRead(id);
    _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('اعلان‌ها')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: _items.isEmpty
                  ? const Center(child: Text('اعلانی وجود ندارد'))
                  : ListView.separated(
                      padding: const EdgeInsets.all(16),
                      itemCount: _items.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 8),
                      itemBuilder: (context, index) {
                        final n = _items[index];
                        final read = n['read_at'] != null;
                        return Card(
                          color: read ? null : Theme.of(context).colorScheme.primaryContainer.withValues(alpha: 0.3),
                          child: ListTile(
                            title: Text(n['title'] ?? 'اعلان'),
                            subtitle: Text(n['body'] ?? ''),
                            trailing: read
                                ? null
                                : IconButton(
                                    icon: const Icon(Icons.done),
                                    onPressed: () => _markRead(n['id'].toString()),
                                  ),
                          ),
                        );
                      },
                    ),
            ),
    );
  }
}
