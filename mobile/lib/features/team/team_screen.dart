import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/api/api_client.dart';

class TeamScreen extends ConsumerStatefulWidget {
  const TeamScreen({super.key});

  @override
  ConsumerState<TeamScreen> createState() => _TeamScreenState();
}

class _TeamScreenState extends ConsumerState<TeamScreen> {
  List<dynamic> _members = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final api = ref.read(apiClientProvider);
      final members = await api.getTeam();
      setState(() { _members = members; _loading = false; });
    } catch (_) {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('تیم')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: _members.length,
              itemBuilder: (context, i) {
                final m = _members[i];
                return Card(
                  child: ListTile(
                    leading: CircleAvatar(child: Text((m['name'] ?? '?')[0])),
                    title: Text(m['name'] ?? ''),
                    subtitle: Text(m['mobile'] ?? '', textDirection: TextDirection.ltr),
                    trailing: Chip(label: Text(m['role_label'] ?? '')),
                  ),
                );
              },
            ),
    );
  }
}
