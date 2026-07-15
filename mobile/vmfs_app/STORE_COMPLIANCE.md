# VMFS USA Mobile App — Store Submission Guide

Use this document when filling Play Console and App Store Connect forms.

## App metadata

| Field | Value |
|-------|-------|
| App name | VMFS USA |
| Package (Android) | `com.vmfsusa.vmfs_app` |
| Bundle ID (iOS) | `com.vmfsusa.vmfs_app` |
| Category | Utilities or Business |
| Age rating | 17+ / 18+ (age-restricted products) |
| Privacy Policy URL | `https://vmfs.sm-vending.com/privacy` |
| Terms URL | `https://vmfs.sm-vending.com/terms` |
| Support URL | `https://vmfsusa.com/contact` |

## Google Play — Data Safety form

| Question | Answer |
|----------|--------|
| Does your app collect data? | **Yes** |
| Is data encrypted in transit? | **Yes** (HTTPS) |
| Can users request data deletion? | **Yes** (email support) |

### Data types collected

| Data type | Collected | Shared | Purpose | Required |
|-----------|-----------|--------|---------|----------|
| Photos (government ID) | Yes | With verification provider only | Age verification | Yes, for regulated products |
| User IDs (session UUID) | Yes | No | Link kiosk session | Yes |
| App interactions (lottery code) | Yes | No | Coupon validation | Optional |

**Not collected:** Location, contacts, financial info, browsing history, ad ID.

### Permissions justification (Play Console)

- **CAMERA** — Capture government ID for age verification at vending kiosk
- **READ_MEDIA_IMAGES** — Select existing ID photo from gallery

## Google Play — Content rating (IARC)

- Violence: None
- Sexuality: None
- Language: None
- Controlled substances: **Reference to age-restricted products** (user must verify age)
- Gambling: **No** — promotional vending coupons only, not real-money gambling
- User interaction: Users can submit ID photos

Recommended rating: **Teen or Mature** depending on region.

## Apple App Store — Privacy Nutrition Labels

| Data type | Linked to user | Used for tracking | Purpose |
|-----------|----------------|-------------------|---------|
| Photos | Yes | No | Age verification |
| User ID | Yes | No | App functionality |
| Other data (lottery code) | No | No | App functionality |

## Apple App Store — Review notes

```
VMFS USA is a companion app for VMFS vending machines.

Flow:
1. Customer approaches a VMFS vending machine selling age-restricted products.
2. Machine displays a QR code.
3. Customer scans QR → app opens → uploads government ID photo.
4. Age is verified → customer returns to machine to complete purchase.

Lottery feature: promotional coupon code lookup for vending machine prizes only. Not gambling.

Test: Use any valid session UUID from POST /api/v1/age-verification/sessions with machine_no.
```

## Deep link verification files

Before submitting, update placeholders on production server:

### `public/.well-known/assetlinks.json`
Replace `REPLACE_WITH_RELEASE_KEY_SHA256_FINGERPRINT` with:
```bash
keytool -list -v -keystore vmfs-release.keystore -alias vmfs | grep SHA256
```

### `public/.well-known/apple-app-site-association`
Replace `REPLACE_WITH_TEAM_ID` with your Apple Developer Team ID.

## Android release build

```bash
# 1. Create keystore (one-time)
keytool -genkey -v -keystore mobile/vmfs-release.keystore -alias vmfs \
  -keyalg RSA -keysize 2048 -validity 10000

# 2. Copy and fill signing config
cp mobile/vmfs_app/android/key.properties.example mobile/vmfs_app/android/key.properties

# 3. Generate icons + build
cd mobile/vmfs_app
dart run flutter_launcher_icons
flutter build appbundle --release \
  --dart-define=API_BASE_URL=https://vmfs.sm-vending.com/api/v1 \
  --dart-define=PRIVACY_POLICY_URL=https://vmfs.sm-vending.com/privacy \
  --dart-define=TERMS_URL=https://vmfs.sm-vending.com/terms
```

## iOS release build

```bash
# Requires Xcode from App Store
cd mobile/vmfs_app
dart run flutter_launcher_icons
flutter build ipa --release \
  --dart-define=API_BASE_URL=https://vmfs.sm-vending.com/api/v1 \
  --dart-define=PRIVACY_POLICY_URL=https://vmfs.sm-vending.com/privacy \
  --dart-define=TERMS_URL=https://vmfs.sm-vending.com/terms
```

In Xcode: enable **Associated Domains** → `applinks:vmfs.sm-vending.com`

## Pre-submission checklist

- [ ] Privacy policy live at `/privacy`
- [ ] Terms live at `/terms`
- [ ] App icons generated (`dart run flutter_launcher_icons`)
- [ ] Release keystore created and `key.properties` configured
- [ ] `assetlinks.json` updated with real SHA256
- [ ] `apple-app-site-association` updated with Team ID
- [ ] Tested age verification consent flow
- [ ] Tested lottery disclaimer visible
- [ ] Apple Developer + Google Play Console accounts active
