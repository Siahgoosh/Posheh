import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';

class FavoritesScreen extends ConsumerStatefulWidget {
  const FavoritesScreen({super.key});

  @override
  ConsumerState<FavoritesScreen> createState() => _FavoritesScreenState();
}

class _FavoritesScreenState extends ConsumerState<FavoritesScreen> {
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
      final data = await api.getFavorites();
      setState(() {
        _items = data['data'] ?? [];
        _loading = false;
      });
    } catch (_) {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('علاقه‌مندی‌ها')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: _items.isEmpty
                  ? const Center(child: Text('علاقه‌مندی‌ای ندارید'))
                  : ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: _items.length,
                      itemBuilder: (context, i) {
                        final p = _items[i];
                        return Card(
                          child: ListTile(
                            leading: const Icon(Icons.star, color: Colors.amber),
                            title: Text(p['code'] ?? ''),
                            subtitle: Text('${p['type_label']} · ${p['city'] ?? ''}'),
                            onTap: () => context.push('/properties/${p['id']}'),
                          ),
                        );
                      },
                    ),
            ),
    );
  }
}
