# Flutter wrapper
-keep class io.flutter.app.** { *; }
-keep class io.flutter.plugin.** { *; }
-keep class io.flutter.util.** { *; }
-keep class io.flutter.view.** { *; }
-keep class io.flutter.** { *; }
-keep class io.flutter.plugins.** { *; }

# Dio / OkHttp (used by Flutter Android engine networking)
-dontwarn okhttp3.**
-dontwarn okio.**

# Optional Play Core references from Flutter deferred components (not used in this app)
-dontwarn com.google.android.play.core.**
