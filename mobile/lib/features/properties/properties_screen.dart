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
                          ),
                        );
                      },
                    ),
            ),
    );
  }
}

class PropertyFormScreen extends ConsumerStatefulWidget {
  const PropertyFormScreen({super.key});

  @override
  ConsumerState<PropertyFormScreen> createState() => _PropertyFormScreenState();
}

class _PropertyFormScreenState extends ConsumerState<PropertyFormScreen> {
  final _codeController = TextEditingController();
  String _type = 'sale';
  bool _loading = false;

  Future<void> _submit() async {
    setState(() => _loading = true);
    try {
      final api = ref.read(apiClientProvider);
      await api.createProperty({
        'code': _codeController.text,
        'type': _type,
        'permission': 'office',
      });
      if (mounted) context.go('/properties');
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('خطا در ثبت ملک')),
        );
      }
    } finally {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('ثبت ملک جدید')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(
              controller: _codeController,
              decoration: const InputDecoration(labelText: 'کد ملک'),
            ),
            const SizedBox(height: 16),
            DropdownButtonFormField<String>(
              initialValue: _type,
              decoration: const InputDecoration(labelText: 'نوع'),
              items: const [
                DropdownMenuItem(value: 'sale', child: Text('فروش')),
                DropdownMenuItem(value: 'rent', child: Text('اجاره')),
                DropdownMenuItem(value: 'mortgage', child: Text('رهن')),
              ],
              onChanged: (v) => setState(() => _type = v ?? 'sale'),
            ),
            const Spacer(),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _loading ? null : _submit,
                child: Text(_loading ? 'در حال ثبت...' : 'ثبت ملک'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
