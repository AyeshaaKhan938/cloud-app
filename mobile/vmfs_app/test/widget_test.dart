import 'package:flutter_test/flutter_test.dart';
import 'package:vmfs_app/main.dart';

void main() {
  testWidgets('Home screen renders app name', (WidgetTester tester) async {
    await tester.pumpWidget(const VmfsApp());
    await tester.pumpAndSettle();

    expect(find.text('VMFS USA'), findsOneWidget);
    expect(find.text('Lottery code lookup'), findsOneWidget);
  });
}
