import 'package:flutter/material.dart';
import 'app_drawer.dart';
import 'notification_bell.dart';

class PageShell extends StatelessWidget {
  final String title;
  final Widget body;
  final List<Widget>? actions;
  final Widget? floatingActionButton;

  const PageShell({
    super.key,
    required this.title,
    required this.body,
    this.actions,
    this.floatingActionButton,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(title),
        actions: [
          const NotificationBell(),
          ...?actions,
        ],
      ),
      drawer: const AppDrawer(),
      floatingActionButton: floatingActionButton,
      body: body,
    );
  }
}
