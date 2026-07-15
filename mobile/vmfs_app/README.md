# VMFS USA — Mobile App (Flutter)

Cross-platform Android & iOS app for **VMFS USA** vending machine customers. Connects to the existing Laravel backend at `/api/v1/` — no backend changes required for core flows.

## Features

| Feature | Web equivalent | API endpoint |
|---------|----------------|--------------|
| **Age verification** | `/verify` | `GET/POST /api/v1/age-verification/sessions/{id}` |
| **Lottery code lookup** | `/lottery` | `POST /api/v1/lottery-codes/lookup` |
| **Deep links** | QR on kiosk → `/verify?session=…` | Opens app natively |

Design matches the existing VMFS branding:
- Dark slate theme for age verification (VMFS brand)
- Amber accent for lottery (same as Filament admin + lottery pages)
- Navy gradient home screen (same as admin login panel)

## Project structure

```
mobile/vmfs_app/
├── lib/
│   ├── config/          # API URL, theme, branding
│   ├── core/            # API client, models, services
│   ├── features/        # Screens (home, verify, lottery)
│   └── shared/          # Reusable widgets
├── android/             # Play Store config + permissions
├── ios/                 # App Store config + permissions
└── test/                # Unit tests
```

## Quick start

```bash
cd mobile/vmfs_app
flutter pub get

# Run against production API (default)
flutter run

# Run against local Laravel
flutter run --dart-define=API_BASE_URL=http://localhost:8000/api/v1
```

## Build for stores

### Android (Google Play)

```bash
# 1. Create a keystore (one-time)
keytool -genkey -v -keystore vmfs-release.keystore -alias vmfs -keyalg RSA -keysize 2048 -validity 10000

# 2. Configure signing in android/key.properties (do NOT commit)
storePassword=<password>
keyPassword=<password>
keyAlias=vmfs
storeFile=../vmfs-release.keystore

# 3. Build release AAB (required by Play Store)
flutter build appbundle --release \
  --dart-define=API_BASE_URL=https://vmfs.sm-vending.com/api/v1 \
  --dart-define=PRIVACY_POLICY_URL=https://vmfsusa.com/privacy
```

### iOS (Apple App Store)

```bash
# 1. Open Xcode project
open ios/Runner.xcworkspace

# 2. Set Team, Bundle ID (com.vmfsusa.vmfs_app), signing

# 3. Add Associated Domains capability:
#    applinks:vmfs.sm-vending.com

# 4. Build IPA
flutter build ipa --release \
  --dart-define=API_BASE_URL=https://vmfs.sm-vending.com/api/v1 \
  --dart-define=PRIVACY_POLICY_URL=https://vmfsusa.com/privacy
```

## Store policy compliance checklist

### Google Play Store

- [ ] **Privacy policy URL** — required in Play Console; linked in app (Home → Privacy Policy)
- [ ] **Data safety form** — declare: government ID photos uploaded for age verification; not sold to third parties; retained per server policy (24h document retention)
- [ ] **Permissions justification** — Camera & photo library used only for age verification ID capture
- [ ] **Target API level** — Flutter builds target latest SDK automatically; verify in Play Console
- [ ] **Content rating** — complete IARC questionnaire; app handles age-restricted products
- [ ] **App signing** — use Play App Signing with upload key

### Apple App Store

- [ ] **Privacy Nutrition Labels** — declare: photos/ID data collected for age verification purpose
- [ ] **Camera & Photo Library usage strings** — configured in `ios/Runner/Info.plist`
- [ ] **Associated Domains** — enable `applinks:vmfs.sm-vending.com` for universal links
- [ ] **App Review notes** — explain: customer scans QR at vending kiosk → verifies age → returns to machine
- [ ] **Age rating** — set appropriately (handles regulated/age-restricted products)
- [ ] **Export compliance** — uses only standard HTTPS encryption

### Both stores — general

- [ ] Host `/.well-known/assetlinks.json` (Android) and `/.well-known/apple-app-site-association` (iOS) on `vmfs.sm-vending.com` for universal/app links
- [ ] Publish a privacy policy covering ID document collection, retention, and Veriff/local processing
- [ ] Do not collect data beyond what the API requires
- [ ] Test deep link: `https://vmfs.sm-vending.com/verify?session=<uuid>`

## Deep linking setup (server-side)

Add these files on your production server so QR codes open the native app when installed:

### Android — `public/.well-known/assetlinks.json`

```json
[{
  "relation": ["delegate_permission/common.handle_all_urls"],
  "target": {
    "namespace": "android_app",
    "package_name": "com.vmfsusa.vmfs_app",
    "sha256_cert_fingerprints": ["<YOUR_RELEASE_SHA256>"]
  }
}]
```

### iOS — `public/.well-known/apple-app-site-association`

```json
{
  "applinks": {
    "apps": [],
    "details": [{
      "appID": "<TEAM_ID>.com.vmfsusa.vmfs_app",
      "paths": ["/verify", "/verify/*"]
    }]
  }
}
```

Get SHA256 fingerprint:
```bash
keytool -list -v -keystore vmfs-release.keystore -alias vmfs
```

## Environment variables (dart-define)

| Variable | Default | Description |
|----------|---------|-------------|
| `API_BASE_URL` | `https://vmfs.sm-vending.com/api/v1` | Laravel API base |
| `PRIVACY_POLICY_URL` | `https://vmfsusa.com/privacy` | Play/App Store required |
| `SUPPORT_URL` | `https://vmfsusa.com/contact` | Support page |
| `DEEP_LINK_HOST` | `vmfs.sm-vending.com` | Universal link host |

## Tests

```bash
cd mobile/vmfs_app
flutter test
```

## What this app does NOT include

The **kiosk vending machine app** (slot inventory, dispense hardware, ads, scratch cards) is a separate on-machine Flutter app that talks to the same API. This consumer mobile app covers **customer-facing** flows only:

- Age verification (phone companion to kiosk QR)
- Lottery/coupon code lookup

Operator admin features (`/admin` Filament panel) are web-only and use session auth — not included in this mobile app.

## Next steps

1. Replace default launcher icons with VMFS brand assets
2. Host privacy policy and deep-link verification files
3. Configure signing certificates
4. Submit to Play Console and App Store Connect
5. Update kiosk QR flow to prefer app deep link (`vmfsusa://verify?session=…`) with web fallback
