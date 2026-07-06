import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';

class PropertiesScreen extends ConsumerStatefulWidget {
  const PropertiesScreen({super.key});

  @override
  ConsumerState<PropertiesScreen> createState() => _PropertiesScreenState();
}

class _PropertiesScreenState extends ConsumerState<PropertiesScreen> {
  List<dynamic> _properties = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadProperties();
  }

  Future<void> _loadProperties() async {
    try {
      final api = ref.read(apiClientProvider);
      final data = await api.getProperties();
      setState(() {
        _properties = data['data'] ?? [];
        _loading = false;
      });
    } catch (e) {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('املاک')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadProperties,
              child: _properties.isEmpty
                  ? const Center(child: Text('ملکی ثبت نشده'))
                  : ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: _properties.length,
                      itemBuilder: (context, index) {
                        final p = _properties[index];
                        return Card(
                          margin: const EdgeInsets.only(bottom: 12),
                          child: ListTile(
                            title: Text(p['code'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                            subtitle: Text('${p['type_label']} · ${p['city'] ?? ''}'),
                            trailing: Chip(label: Text(p['status_label'] ?? '')),
                            onTap: () => context.push('/properties/${p['id']}'),
                          ),
                        );
                      },
                    ),
            ),
    );
  }
}
