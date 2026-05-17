/**
 * Aeterna ERP - Capacitor Native Bridge
 * ========================================
 * هذا الملف يستبدل browser geolocation بـ native GPS من Capacitor
 * لأدق نتائج وأسرع استجابة + طلب صلاحيات صح على Android
 *
 * يتم تحميله تلقائياً من layout/header.php
 */

(function () {
  'use strict';

  // تشغل فقط جوه Capacitor
  if (!window.Capacitor) return;

  const { Geolocation } = window.Capacitor.Plugins;
  if (!Geolocation) return;

  // ─── طلب صلاحيات GPS ───────────────────────────────────────────
  async function requestLocationPermission() {
    try {
      const status = await Geolocation.checkPermissions();
      if (status.location === 'granted' || status.coarseLocation === 'granted') {
        return true;
      }
      const request = await Geolocation.requestPermissions({
        permissions: ['location', 'coarseLocation']
      });
      return request.location === 'granted' || request.coarseLocation === 'granted';
    } catch (e) {
      console.warn('[Aeterna GPS] Permission error:', e);
      return false;
    }
  }

  // ─── استبدال navigator.geolocation بـ Native ───────────────────
  const nativeGeolocation = {
    getCurrentPosition: async function (successCb, errorCb, options) {
      try {
        const hasPermission = await requestLocationPermission();
        if (!hasPermission) {
          if (errorCb) errorCb({ code: 1, message: 'تم رفض صلاحية الموقع' });
          return;
        }

        const pos = await Geolocation.getCurrentPosition({
          enableHighAccuracy: options?.enableHighAccuracy !== false,
          timeout: options?.timeout || 15000,
          maximumAge: options?.maximumAge || 0
        });

        // تحويل للـ format المتوقع من browser API
        successCb({
          coords: {
            latitude: pos.coords.latitude,
            longitude: pos.coords.longitude,
            accuracy: pos.coords.accuracy,
            altitude: pos.coords.altitude,
            altitudeAccuracy: pos.coords.altitudeAccuracy,
            heading: pos.coords.heading,
            speed: pos.coords.speed
          },
          timestamp: pos.timestamp
        });
      } catch (e) {
        console.warn('[Aeterna GPS] getCurrentPosition error:', e);
        if (errorCb) {
          errorCb({
            code: e.code || 2,
            message: e.message || 'خطأ في الحصول على الموقع'
          });
        }
      }
    },

    watchPosition: function (successCb, errorCb, options) {
      let watchId = null;
      let active = true;

      (async () => {
        try {
          const hasPermission = await requestLocationPermission();
          if (!hasPermission) {
            if (errorCb) errorCb({ code: 1, message: 'تم رفض صلاحية الموقع' });
            return;
          }

          watchId = await Geolocation.watchPosition(
            {
              enableHighAccuracy: options?.enableHighAccuracy !== false,
              timeout: options?.timeout || 15000,
              maximumAge: options?.maximumAge || 0
            },
            function (pos, err) {
              if (!active) return;
              if (err) {
                if (errorCb) errorCb({ code: 2, message: err.message });
                return;
              }
              successCb({
                coords: {
                  latitude: pos.coords.latitude,
                  longitude: pos.coords.longitude,
                  accuracy: pos.coords.accuracy,
                  altitude: pos.coords.altitude,
                  altitudeAccuracy: pos.coords.altitudeAccuracy,
                  heading: pos.coords.heading,
                  speed: pos.coords.speed
                },
                timestamp: pos.timestamp
              });
            }
          );
        } catch (e) {
          if (errorCb) errorCb({ code: 2, message: e.message });
        }
      })();

      // إرجاع ID للـ clearWatch
      const id = Date.now();
      window._aeternaWatchers = window._aeternaWatchers || {};
      window._aeternaWatchers[id] = { nativeId: watchId, active };
      return id;
    },

    clearWatch: function (id) {
      if (window._aeternaWatchers && window._aeternaWatchers[id]) {
        const w = window._aeternaWatchers[id];
        w.active = false;
        if (w.nativeId !== null) {
          Geolocation.clearWatch({ id: w.nativeId }).catch(() => {});
        }
        delete window._aeternaWatchers[id];
      }
    }
  };

  // استبدال browser geolocation بـ native
  try {
    Object.defineProperty(navigator, 'geolocation', {
      get: function () { return nativeGeolocation; },
      configurable: true
    });
    console.log('[Aeterna] Native GPS bridge active ✓');
  } catch (e) {
    console.warn('[Aeterna] Could not override geolocation:', e);
  }

  // ─── Camera Bridge ─────────────────────────────────────────────
  // تمكين رفع الصور بالكاميرا native بدلاً من <input type="file">
  const { Camera } = window.Capacitor.Plugins;
  if (Camera) {
    window.AeternaNativeCamera = {
      takePicture: async function () {
        try {
          const image = await Camera.getPhoto({
            quality: 85,
            allowEditing: false,
            resultType: 'base64',
            source: 'CAMERA'
          });
          return 'data:image/jpeg;base64,' + image.base64String;
        } catch (e) {
          console.warn('[Aeterna Camera]', e);
          return null;
        }
      },
      pickFromGallery: async function () {
        try {
          const image = await Camera.getPhoto({
            quality: 85,
            allowEditing: false,
            resultType: 'base64',
            source: 'PHOTOS'
          });
          return 'data:image/jpeg;base64,' + image.base64String;
        } catch (e) {
          console.warn('[Aeterna Camera]', e);
          return null;
        }
      }
    };
  }

  // ─── Network Status ────────────────────────────────────────────
  const { Network } = window.Capacitor.Plugins;
  if (Network) {
    Network.addListener('networkStatusChange', function (status) {
      if (!status.connected) {
        // إشعار المستخدم بانقطاع الاتصال
        const event = new CustomEvent('aeterna:offline', { detail: status });
        window.dispatchEvent(event);
      }
    });
  }

  // ─── App State (Security) ──────────────────────────────────────
  const { App } = window.Capacitor.Plugins;
  if (App) {
    App.addListener('appStateChange', function (state) {
      if (!state.isActive) {
        // عند إخفاء التطبيق - مسح sensitive data من DOM إذا لزم
        const event = new CustomEvent('aeterna:background');
        window.dispatchEvent(event);
      }
    });
  }

})();
