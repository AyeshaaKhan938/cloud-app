import 'dart:async';

import 'package:app_links/app_links.dart';
import 'package:flutter/material.dart';
import 'package:vmfs_app/app_router.dart';
import 'package:vmfs_app/config/app_config.dart';
import 'package:vmfs_app/config/theme.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const VmfsApp());
}

class VmfsApp extends StatefulWidget {
  const VmfsApp({super.key});

  @override
  State<VmfsApp> createState() => _VmfsAppState();
}

class _VmfsAppState extends State<VmfsApp> {
  late final AppLinks _appLinks;
  StreamSubscription<Uri>? _linkSubscription;

  @override
  void initState() {
    super.initState();
    _appLinks = AppLinks();
    _handleInitialLink();
    _linkSubscription = _appLinks.uriLinkStream.listen(_navigateFromUri);
  }

  Future<void> _handleInitialLink() async {
    final initial = await _appLinks.getInitialLink();
    if (initial != null) {
      _navigateFromUri(initial);
    }
  }

  void _navigateFromUri(Uri uri) {
    final sessionId = uri.queryParameters['session'];
    if (sessionId != null && sessionId.isNotEmpty) {
      appRouter.go('/verify?session=$sessionId');
      return;
    }

    if (uri.path == '/verify' || uri.path.endsWith('/verify')) {
      appRouter.go(uri.path + (uri.query.isNotEmpty ? '?${uri.query}' : ''));
    }
  }

  @override
  void dispose() {
    _linkSubscription?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp.router(
      title: AppConfig.appName,
      debugShowCheckedModeBanner: false,
      theme: VmfsTheme.darkTheme,
      routerConfig: appRouter,
    );
  }
}
