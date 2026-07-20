#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

flutter pub get
dart run flutter_launcher_icons

# Smallest production build: one architecture (~15-20 MB), not the 50-150 MB fat APK.
flutter build apk --release \
  --target-platform android-arm64 \
  --obfuscate \
  --split-debug-info=build/debug-info \
  --tree-shake-icons \
  --dart-define=API_BASE_URL="${API_BASE_URL:-https://cloud.vmfsusa.com/api/mobile/v1}"

echo
echo "Built APK:"
ls -lh build/app/outputs/flutter-apk/app-arm64-v8a-release.apk
