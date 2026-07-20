import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/config/app_config.dart';
import 'core/router/app_router.dart';
import 'core/theme/vmfs_theme.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const ProviderScope(child: VmfsCloudApp()));
}

class VmfsCloudApp extends ConsumerWidget {
  const VmfsCloudApp({super.key});

  static final _theme = VmfsTheme.light();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return MaterialApp.router(
      title: AppConfig.appName,
      debugShowCheckedModeBanner: false,
      theme: _theme,
      routerConfig: ref.watch(appRouterProvider),
      scrollBehavior: const MaterialScrollBehavior().copyWith(
        physics: const ClampingScrollPhysics(),
      ),
    );
  }
}
