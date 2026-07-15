import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:posheh/main.dart';

void main() {
  testWidgets('App boots and shows brand name', (WidgetTester tester) async {
    await tester.pumpWidget(const ProviderScope(child: PoshehApp()));
    await tester.pump();

    // The splash and auth screens both render the brand name "پوشه".
    expect(find.text('پوشه'), findsWidgets);
  });
}
