import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/api/api_client.dart';

class TasksScreen extends ConsumerStatefulWidget {
  const TasksScreen({super.key});

  @override
  ConsumerState<TasksScreen> createState() => _TasksScreenState();
}

class _TasksScreenState extends ConsumerState<TasksScreen> {
  List<dynamic> _tasks = [];
  bool _loading = true;
  final _titleController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final api = ref.read(apiClientProvider);
      final data = await api.getTasks();
      setState(() { _tasks = data['data'] ?? []; _loading = false; });
    } catch (_) {
      setState(() => _loading = false);
    }
  }

  Future<void> _create() async {
    if (_titleController.text.isEmpty) return;
    final api = ref.read(apiClientProvider);
    await api.createTask(_titleController.text);
    _titleController.clear();
    _load();
  }

  Future<void> _toggle(int id, String status) async {
    final api = ref.read(apiClientProvider);
    await api.updateTask(id, {'status': status == 'completed' ? 'pending' : 'completed'});
    _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('وظایف')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _titleController,
                    decoration: const InputDecoration(hintText: 'وظیفه جدید...'),
                  ),
                ),
                IconButton(onPressed: _create, icon: const Icon(Icons.add)),
              ],
            ),
          ),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : RefreshIndicator(
                    onRefresh: _load,
                    child: ListView.builder(
                      itemCount: _tasks.length,
                      itemBuilder: (context, i) {
                        final t = _tasks[i];
                        final done = t['status'] == 'completed';
                        return CheckboxListTile(
                          value: done,
                          onChanged: (_) => _toggle(t['id'], t['status']),
                          title: Text(
                            t['title'] ?? '',
                            style: done ? const TextStyle(decoration: TextDecoration.lineThrough) : null,
                          ),
                          subtitle: t['due_at_jalali'] != null ? Text('سررسید: ${t['due_at_jalali']}') : null,
                        );
                      },
                    ),
                  ),
          ),
        ],
      ),
    );
  }
}
