class AgeVerificationSession {
  const AgeVerificationSession({
    required this.sessionId,
    required this.status,
    required this.ageVerified,
    this.message,
  });

  factory AgeVerificationSession.fromJson(Map<String, dynamic> json) {
    return AgeVerificationSession(
      sessionId: json['session_id'] as String? ?? '',
      status: json['status'] as String? ?? 'pending',
      ageVerified: json['age_verified'] as bool? ?? false,
      message: json['message'] as String?,
    );
  }

  final String sessionId;
  final String status;
  final bool ageVerified;
  final String? message;

  bool get isVerified => status == 'verified' && ageVerified;
  bool get isRejected => status == 'rejected';
  bool get isExpired => status == 'expired';
  bool get isProcessing =>
      status == 'processing' || status == 'submitted' || status == 'pending_review';
}
