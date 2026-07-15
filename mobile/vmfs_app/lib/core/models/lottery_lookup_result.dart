class LotteryLookupResult {
  const LotteryLookupResult({
    required this.canVend,
    required this.message,
    this.code,
    this.priceTier,
    this.prizeAmount,
    this.prizeName,
    this.productName,
    this.productSku,
    this.isRedeemed = false,
    this.idempotent = false,
  });

  factory LotteryLookupResult.fromJson(Map<String, dynamic> json) {
    return LotteryLookupResult(
      canVend: json['canVend'] as bool? ?? false,
      message: json['message'] as String? ?? '',
      code: json['code'] as String?,
      priceTier: json['price_tier'] as String? ?? json['tier'] as String?,
      prizeAmount: json['prize_amount']?.toString(),
      prizeName: json['prize_name'] as String? ?? json['name'] as String?,
      productName: json['product_name'] as String?,
      productSku: json['product_sku'] as String?,
      isRedeemed: json['idempotent'] as bool? ?? false,
      idempotent: json['idempotent'] as bool? ?? false,
    );
  }

  final bool canVend;
  final String message;
  final String? code;
  final String? priceTier;
  final String? prizeAmount;
  final String? prizeName;
  final String? productName;
  final String? productSku;
  final bool isRedeemed;
  final bool idempotent;
}
