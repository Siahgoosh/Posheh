import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';

class SearchScreen extends ConsumerStatefulWidget {
  const SearchScreen({super.key});

  @override
  ConsumerState<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends ConsumerState<SearchScreen> {
  final _queryController = TextEditingController();
  List<dynamic> _results = [];
  bool _searched = false;
  bool _loading = false;

  Future<void> _search() async {
    setState(() { _loading = true; _searched = true; });
    try {
      final api = ref.read(apiClientProvider);
      final data = await api.getProperties(params: {'q': _queryController.text});
      setState(() { _results = data['data'] ?? []; _loading = false; });
    } catch (_) {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('جستجو')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _queryController,
                    decoration: const InputDecoration(hintText: 'کد، آدرس، نام مالک...'),
                    onSubmitted: (_) => _search(),
                  ),
                ),
                IconButton(onPressed: _search, icon: const Icon(Icons.search)),
              ],
            ),
          ),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : !_searched
                    ? const Center(child: Text('عبارت جستجو را وارد کنید'))
                    : _results.isEmpty
                        ? const Center(child: Text('نتیجه‌ای یافت نشد'))
                        : ListView.builder(
                            itemCount: _results.length,
                            itemBuilder: (context, i) {
                              final p = _results[i];
                              return ListTile(
                                title: Text(p['code'] ?? ''),
                                subtitle: Text(p['type_label'] ?? ''),
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
