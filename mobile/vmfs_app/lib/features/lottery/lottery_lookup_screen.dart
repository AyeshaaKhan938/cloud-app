import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:vmfs_app/config/legal_content.dart';
import 'package:vmfs_app/config/theme.dart';
import 'package:vmfs_app/core/api/api_exception.dart';
import 'package:vmfs_app/core/services/lottery_service.dart';
import 'package:vmfs_app/shared/widgets/status_banner.dart';
import 'package:vmfs_app/shared/widgets/vmfs_card.dart';

class LotteryLookupScreen extends StatefulWidget {
  const LotteryLookupScreen({super.key});

  @override
  State<LotteryLookupScreen> createState() => _LotteryLookupScreenState();
}

class _LotteryLookupScreenState extends State<LotteryLookupScreen> {
  final _service = LotteryService();
  final _codeController = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  bool _isLoading = false;
  String? _errorMessage;

  @override
  void dispose() {
    _codeController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final result = await _service.lookupCode(_codeController.text.trim());

      if (!mounted) {
        return;
      }

      context.push('/lottery/result', extra: result);
    } on ApiException catch (error) {
      setState(() => _errorMessage = error.message);
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Lottery code lookup')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 420),
            child: VmfsCard(
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const Text(
                      'Lottery code lookup',
                      style: TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.w700,
                        color: VmfsTheme.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 8),
                    const Text(
                      'Enter or scan your code to see your prize tier and amount.',
                      style: TextStyle(color: VmfsTheme.textMuted, height: 1.5),
                    ),
                    const SizedBox(height: 12),
                    StatusBanner(
                      message: LegalContent.lotteryDisclaimer,
                      type: StatusBannerType.info,
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _codeController,
                      decoration: const InputDecoration(
                        labelText: 'Code',
                        hintText: 'Enter your lottery code',
                      ),
                      textCapitalization: TextCapitalization.characters,
                      maxLength: 32,
                      validator: (value) {
                        if (value == null || value.trim().isEmpty) {
                          return 'Please enter a code.';
                        }
                        return null;
                      },
                      onFieldSubmitted: (_) => _submit(),
                    ),
                    if (_errorMessage != null) ...[
                      const SizedBox(height: 12),
                      StatusBanner(
                        message: _errorMessage!,
                        type: StatusBannerType.error,
                      ),
                    ],
                    const SizedBox(height: 16),
                    ElevatedButton(
                      onPressed: _isLoading ? null : _submit,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: VmfsTheme.amberPrimary,
                        foregroundColor: Colors.white,
                      ),
                      child: Text(_isLoading ? 'Checking…' : 'Check code'),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
