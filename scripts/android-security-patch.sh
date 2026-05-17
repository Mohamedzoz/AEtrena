#!/bin/bash
# ============================================================
# Aeterna ERP - Android Security Patches
# يُشغَّل بعد npx cap add android في GitHub Actions
# ============================================================
set -e

ANDROID_DIR="android"
MANIFEST="$ANDROID_DIR/app/src/main/AndroidManifest.xml"
MAIN_ACTIVITY="$ANDROID_DIR/app/src/main/java/com/aeterna/erp/MainActivity.java"
STRINGS_XML="$ANDROID_DIR/app/src/main/res/values/strings.xml"

echo "🔒 Applying Aeterna Android Security Patches..."

# ── 1. FLAG_SECURE - منع Screenshots و Screen Recording ─────────
# يضيف window.setFlags(WindowManager.LayoutParams.FLAG_SECURE) في MainActivity
if [ -f "$MAIN_ACTIVITY" ]; then
  # إضافة import لو مش موجود
  if ! grep -q "FLAG_SECURE\|WindowManager" "$MAIN_ACTIVITY"; then
    sed -i '/^import android.os.Bundle;/a import android.view.WindowManager;' "$MAIN_ACTIVITY"
    # إضافة FLAG_SECURE في onCreate بعد super.onCreate
    sed -i '/super\.onCreate(savedInstanceState);/a \        getWindow().setFlags(WindowManager.LayoutParams.FLAG_SECURE, WindowManager.LayoutParams.FLAG_SECURE);' "$MAIN_ACTIVITY"
    echo "  ✓ FLAG_SECURE applied (screenshots blocked)"
  else
    echo "  ↷ FLAG_SECURE already applied"
  fi
fi

# ── 2. AndroidManifest.xml - أمان إضافي ─────────────────────────
if [ -f "$MANIFEST" ]; then
  # منع النسخ الاحتياطي (backup prevention) - يمنع تسريب بيانات
  if ! grep -q "allowBackup=\"false\"" "$MANIFEST"; then
    sed -i 's/android:allowBackup="true"/android:allowBackup="false"/' "$MANIFEST"
    sed -i 's/android:allowBackup="[^"]*"/android:allowBackup="false"/' "$MANIFEST"
    echo "  ✓ allowBackup=false (backup disabled)"
  fi

  # usesCleartextTraffic=false - HTTPS فقط
  if ! grep -q "usesCleartextTraffic=\"false\"" "$MANIFEST"; then
    sed -i 's/android:usesCleartextTraffic="true"/android:usesCleartextTraffic="false"/' "$MANIFEST"
    echo "  ✓ cleartext traffic blocked (HTTPS only)"
  fi

  # largeScreenSupport
  if ! grep -q "resizeableActivity" "$MANIFEST"; then
    sed -i 's/<activity android:configChanges/<activity android:resizeableActivity="true" android:configChanges/' "$MANIFEST"
    echo "  ✓ Resizable activity enabled"
  fi

  echo "  ✓ Manifest security patches applied"
fi

# ── 3. network_security_config.xml - Certificate Pinning هيكل ──
NETWORK_SECURITY_DIR="$ANDROID_DIR/app/src/main/res/xml"
mkdir -p "$NETWORK_SECURITY_DIR"

if [ ! -f "$NETWORK_SECURITY_DIR/network_security_config.xml" ]; then
  cat > "$NETWORK_SECURITY_DIR/network_security_config.xml" << 'XMLEOF'
<?xml version="1.0" encoding="utf-8"?>
<!--
  Aeterna ERP Network Security Config
  - No cleartext traffic
  - Trust only system CAs
  - Debug only: trust user CAs (for local testing)
-->
<network-security-config>
    <base-config cleartextTrafficPermitted="false">
        <trust-anchors>
            <certificates src="system" />
        </trust-anchors>
    </base-config>
    <debug-overrides>
        <trust-anchors>
            <certificates src="system" />
            <certificates src="user" />
        </trust-anchors>
    </debug-overrides>
</network-security-config>
XMLEOF
  echo "  ✓ network_security_config.xml created"

  # ربطه بالـ Manifest
  if [ -f "$MANIFEST" ]; then
    sed -i 's/android:theme="@style\/AppTheme"/android:theme="@style\/AppTheme" android:networkSecurityConfig="@xml\/network_security_config"/' "$MANIFEST"
  fi
fi

# ── 4. Gradle - minSdk و targetSdk ──────────────────────────────
BUILD_GRADLE="$ANDROID_DIR/app/build.gradle"
if [ -f "$BUILD_GRADLE" ]; then
  # رفع minSdk إلى 24 لضمان أمان أفضل
  sed -i 's/minSdkVersion [0-9]*/minSdkVersion 24/' "$BUILD_GRADLE"
  sed -i 's/minSdk [0-9]*/minSdk 24/' "$BUILD_GRADLE"
  echo "  ✓ minSdk set to 24 (Android 7.0+)"
fi

# ── 5. ProGuard Rules (Release Obfuscation) ──────────────────────
PROGUARD="$ANDROID_DIR/app/proguard-rules.pro"
if [ -f "$PROGUARD" ]; then
  cat >> "$PROGUARD" << 'PROGUARDEOF'

# Aeterna ERP - ProGuard Rules
-keep class com.aeterna.erp.** { *; }
-keep class com.getcapacitor.** { *; }
-keepattributes Signature
-keepattributes *Annotation*
-dontwarn com.getcapacitor.**
PROGUARDEOF
  echo "  ✓ ProGuard rules updated"
fi

echo ""
echo "✅ All security patches applied successfully!"
