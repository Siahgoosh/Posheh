import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';

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
              value: _type,
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
