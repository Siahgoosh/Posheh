import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';

class PropertyDetailScreen extends ConsumerStatefulWidget {
  final int propertyId;
  const PropertyDetailScreen({super.key, required this.propertyId});

  @override
  ConsumerState<PropertyDetailScreen> createState() => _PropertyDetailScreenState();
}

class _PropertyDetailScreenState extends ConsumerState<PropertyDetailScreen> {
  Map<String, dynamic>? _property;
  List<dynamic> _similar = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final api = ref.read(apiClientProvider);
      final property = await api.getProperty(widget.propertyId);
      final similar = await api.getSimilarProperties(widget.propertyId);
      setState(() {
        _property = property['data'];
        _similar = similar['data'] ?? [];
        _loading = false;
      });
    } catch (_) {
      setState(() => _loading = false);
    }
  }

  Future<void> _toggleFavorite() async {
    final api = ref.read(apiClientProvider);
    await api.toggleFavorite(widget.propertyId);
    _load();
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return Scaffold(
        appBar: AppBar(title: const Text('جزئیات ملک')),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    final p = _property;
    if (p == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('جزئیات ملک')),
        body: const Center(child: Text('ملک یافت نشد')),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: Text(p['code'] ?? ''),
        actions: [
          IconButton(
            icon: Icon(
              p['is_favorite'] == true ? Icons.star : Icons.star_border,
              color: Colors.amber,
            ),
            onPressed: _toggleFavorite,
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Container(
            height: 160,
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF6366F1), Color(0xFF8B5CF6)],
              ),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Center(
              child: Text(
                p['code'] ?? '',
                style: const TextStyle(fontSize: 36, fontWeight: FontWeight.bold, color: Colors.white),
              ),
            ),
          ),
          const SizedBox(height: 16),
          Wrap(
            spacing: 8,
            children: [
              Chip(label: Text(p['type_label'] ?? '')),
              Chip(label: Text(p['status_label'] ?? '')),
            ],
          ),
          if (p['price'] != null) ...[
            const SizedBox(height: 12),
            Text(
              '${p['price']} تومان',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                color: Theme.of(context).colorScheme.primary,
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
          const SizedBox(height: 16),
          if (p['city'] != null) ListTile(
            leading: const Icon(Icons.location_on),
            title: Text('${p['city']}${p['district'] != null ? '، ${p['district']}' : ''}'),
          ),
          if (p['area'] != null) ListTile(
            leading: const Icon(Icons.square_foot),
            title: Text('${p['area']} متر مربع'),
          ),
          if (p['rooms'] != null) ListTile(
            leading: const Icon(Icons.bed),
            title: Text('${p['rooms']} خواب'),
          ),
          if (p['owner_name'] != null) ListTile(
            leading: const Icon(Icons.person),
            title: Text(p['owner_name']),
            subtitle: p['owner_mobile'] != null ? Text(p['owner_mobile'], textDirection: TextDirection.ltr) : null,
          ),
          if (p['description'] != null) ...[
            const SizedBox(height: 8),
            Text('توضیحات', style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 4),
            Text(p['description'], style: TextStyle(color: Colors.grey[600])),
          ],
          if (_similar.isNotEmpty) ...[
            const SizedBox(height: 24),
            Text('املاک مشابه', style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 8),
            ..._similar.map((s) => Card(
              child: ListTile(
                title: Text(s['code'] ?? ''),
                subtitle: Text(s['type_label'] ?? ''),
                onTap: () => context.push('/properties/${s['id']}'),
              ),
            )),
          ],
        ],
      ),
    );
  }
}
