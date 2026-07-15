import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:vmfs_app/config/theme.dart';
import 'package:vmfs_app/core/api/api_exception.dart';
import 'package:vmfs_app/core/services/age_verification_service.dart';
import 'package:vmfs_app/features/legal/legal_screens.dart';
import 'package:vmfs_app/shared/widgets/status_banner.dart';
import 'package:vmfs_app/shared/widgets/step_indicator.dart';
import 'package:vmfs_app/shared/widgets/vmfs_card.dart';

class AgeVerificationScreen extends StatefulWidget {
  const AgeVerificationScreen({super.key, required this.sessionId});

  final String sessionId;

  @override
  State<AgeVerificationScreen> createState() => _AgeVerificationScreenState();
}

class _AgeVerificationScreenState extends State<AgeVerificationScreen> {
  final _service = AgeVerificationService();
  final _picker = ImagePicker();

  String _documentType = 'drivers_license';
  File? _documentFile;
  int _currentStep = 0;
  bool _isUploading = false;
  bool _isVerified = false;
  bool _showForm = true;
  bool _consentAccepted = false;
  String? _statusMessage;
  StatusBannerType _statusType = StatusBannerType.info;
  Timer? _pollTimer;

  @override
  void initState() {
    super.initState();
    _pollStatus();
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    super.dispose();
  }

  Future<void> _pollStatus() async {
    try {
      final session = await _service.getSession(widget.sessionId);

      if (!mounted) {
        return;
      }

      if (session.isVerified) {
        _pollTimer?.cancel();
        setState(() {
          _isVerified = true;
          _showForm = false;
          _currentStep = 2;
          _statusMessage =
              session.message ?? 'Age verified. Return to the kiosk to continue.';
          _statusType = StatusBannerType.success;
        });
        return;
      }

      if (session.isRejected || session.isExpired) {
        _pollTimer?.cancel();
        setState(() {
          _showForm = true;
          _currentStep = 0;
          _statusMessage = session.message ??
              'Verification failed. Start again from the kiosk.';
          _statusType = StatusBannerType.error;
        });
        return;
      }

      if (session.status == 'pending') {
        setState(() {
          _statusMessage = 'Upload a photo of your ID to continue.';
          _statusType = StatusBannerType.info;
          _currentStep = 0;
        });
        return;
      }

      setState(() {
        _statusMessage = session.message ?? 'Verification in progress…';
        _statusType = StatusBannerType.info;
        _currentStep = 1;
        _showForm = false;
      });
    } on ApiException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _statusMessage = error.message;
        _statusType = StatusBannerType.error;
        _showForm = false;
      });
    }
  }

  Future<void> _pickImage(ImageSource source) async {
    final picked = await _picker.pickImage(
      source: source,
      maxWidth: 2048,
      maxHeight: 2048,
      imageQuality: 85,
      requestFullMetadata: false,
    );

    if (picked == null) {
      return;
    }

    setState(() {
      _documentFile = File(picked.path);
      _currentStep = 1;
    });
  }

  Future<void> _submit() async {
    final file = _documentFile;
    if (file == null) {
      return;
    }

    setState(() {
      _isUploading = true;
      _statusMessage = 'Uploading your ID…';
      _statusType = StatusBannerType.info;
      _currentStep = 1;
    });

    try {
      final response = await _service.uploadDocument(
        sessionId: widget.sessionId,
        document: file,
        documentType: _documentType,
      );

      if (!mounted) {
        return;
      }

      setState(() {
        _isUploading = false;
        _showForm = false;
        _statusMessage = response['message'] as String? ??
            'Verification in progress…';
        _statusType = StatusBannerType.info;
        _currentStep = 2;
      });

      await _pollStatus();
      _pollTimer?.cancel();
      _pollTimer = Timer.periodic(const Duration(seconds: 3), (_) => _pollStatus());
    } on ApiException catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _isUploading = false;
        _statusMessage = error.message;
        _statusType = StatusBannerType.error;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [VmfsTheme.bgDark, VmfsTheme.bgGradientEnd],
          ),
        ),
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 420),
                child: VmfsCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      StepIndicator(totalSteps: 3, currentStep: _currentStep),
                      const SizedBox(height: 20),
                      Text(
                        _isVerified ? 'Verified' : 'Verify your age',
                        style: const TextStyle(
                          fontSize: 22,
                          fontWeight: FontWeight.w700,
                          color: VmfsTheme.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        _isVerified
                            ? 'You can return to the vending machine to complete your purchase.'
                            : 'Take a clear photo of your government-issued ID. This session is linked to the kiosk and expires in 15 minutes.',
                        style: const TextStyle(
                          color: VmfsTheme.textMuted,
                          height: 1.5,
                        ),
                      ),
                      const SizedBox(height: 20),
                      if (_showForm) ...[
                        const Text(
                          'Document type',
                          style: TextStyle(
                            color: VmfsTheme.textMuted,
                            fontSize: 14,
                          ),
                        ),
                        const SizedBox(height: 6),
                        DropdownButtonFormField<String>(
                          initialValue: _documentType,
                          dropdownColor: VmfsTheme.inputBg,
                          style: const TextStyle(color: VmfsTheme.textPrimary),
                          decoration: const InputDecoration(
                            contentPadding: EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 12,
                            ),
                          ),
                          items: const [
                            DropdownMenuItem(
                              value: 'drivers_license',
                              child: Text("Driver's license"),
                            ),
                            DropdownMenuItem(
                              value: 'id_card',
                              child: Text('ID card'),
                            ),
                            DropdownMenuItem(
                              value: 'passport',
                              child: Text('Passport'),
                            ),
                          ],
                          onChanged: _isUploading
                              ? null
                              : (value) {
                                  if (value != null) {
                                    setState(() => _documentType = value);
                                  }
                                },
                        ),
                        const SizedBox(height: 16),
                        if (_documentFile != null) ...[
                          ClipRRect(
                            borderRadius: BorderRadius.circular(12),
                            child: AspectRatio(
                              aspectRatio: 4 / 3,
                              child: Image.file(
                                _documentFile!,
                                fit: BoxFit.cover,
                              ),
                            ),
                          ),
                          const SizedBox(height: 16),
                        ],
                        OutlinedButton(
                          onPressed: _isUploading || !_consentAccepted
                              ? null
                              : () => _showImageSourcePicker(),
                          child: const Text('Choose or capture photo'),
                        ),
                        const SizedBox(height: 16),
                        ConsentCheckbox(
                          value: _consentAccepted,
                          onChanged: _isUploading
                              ? (_) {}
                              : (value) {
                                  setState(() => _consentAccepted = value ?? false);
                                },
                        ),
                        const PrivacyLinkText(),
                        const SizedBox(height: 12),
                        ElevatedButton(
                          onPressed: _documentFile != null &&
                                  _consentAccepted &&
                                  !_isUploading
                              ? _submit
                              : null,
                          child: Text(
                            _isUploading
                                ? 'Uploading…'
                                : 'Submit for verification',
                          ),
                        ),
                      ],
                      if (_statusMessage != null) ...[
                        const SizedBox(height: 16),
                        StatusBanner(
                          message: _statusMessage!,
                          type: _statusType,
                        ),
                      ],
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _showImageSourcePicker() async {
    await showModalBottomSheet<void>(
      context: context,
      backgroundColor: VmfsTheme.cardDark,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (context) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                ListTile(
                  leading: const Icon(Icons.camera_alt, color: VmfsTheme.accentSky),
                  title: const Text('Take photo', style: TextStyle(color: VmfsTheme.textPrimary)),
                  onTap: () {
                    Navigator.pop(context);
                    _pickImage(ImageSource.camera);
                  },
                ),
                ListTile(
                  leading: const Icon(Icons.photo_library, color: VmfsTheme.accentSky),
                  title: const Text('Choose from gallery', style: TextStyle(color: VmfsTheme.textPrimary)),
                  onTap: () {
                    Navigator.pop(context);
                    _pickImage(ImageSource.gallery);
                  },
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
