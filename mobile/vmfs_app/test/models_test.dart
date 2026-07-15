import 'package:flutter_test/flutter_test.dart';
import 'package:vmfs_app/core/models/age_verification_session.dart';
import 'package:vmfs_app/core/models/lottery_lookup_result.dart';

void main() {
  group('AgeVerificationSession', () {
    test('parses verified session', () {
      final session = AgeVerificationSession.fromJson({
        'session_id': 'abc-123',
        'status': 'verified',
        'age_verified': true,
        'message': 'Age verified.',
      });

      expect(session.isVerified, isTrue);
      expect(session.sessionId, 'abc-123');
    });

    test('detects rejected session', () {
      final session = AgeVerificationSession.fromJson({
        'session_id': 'abc-123',
        'status': 'rejected',
        'age_verified': false,
      });

      expect(session.isRejected, isTrue);
    });
  });

  group('LotteryLookupResult', () {
    test('parses successful lookup', () {
      final result = LotteryLookupResult.fromJson({
        'canVend': true,
        'message': 'You got Tier A — \$4.99 off!',
        'code': 'ABC123',
        'price_tier': 'A',
        'prize_amount': '4.99',
      });

      expect(result.canVend, isTrue);
      expect(result.code, 'ABC123');
      expect(result.priceTier, 'A');
    });

    test('parses denied lookup', () {
      final result = LotteryLookupResult.fromJson({
        'canVend': false,
        'idempotent': true,
        'message': 'Already redeemed.',
      });

      expect(result.canVend, isFalse);
      expect(result.idempotent, isTrue);
    });
  });
}
