<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue';
import { QrcodeStream } from 'vue-qrcode-reader';
import axios from 'axios';

const props = defineProps({
  event: Object,
});

const scanResult = ref(null);
const errorMessage = ref('');
const isLoading = ref(false);
const isTakingPhoto = ref(false);
const capturedImage = ref(null);
const guestIdForPhoto = ref(null);
const videoRef = ref(null);
const canvasRef = ref(null);
const cameraStream = ref(null);
const isScanning = ref(true);
const scannerKey = ref(0);
const cameraError = ref('');
const isPortrait = ref(window.innerWidth < window.innerHeight);

// Check device orientation
const checkOrientation = () => {
  isPortrait.value = window.innerWidth < window.innerHeight;
};

onMounted(() => {
  window.addEventListener('resize', checkOrientation);
  window.addEventListener('orientationchange', () => {
    setTimeout(checkOrientation, 100);
  });

  // Force reload scanner after orientation change
  window.addEventListener('orientationchange', () => {
    setTimeout(() => {
      scannerKey.value++;
    }, 500);
  });
});

onUnmounted(() => {
  window.removeEventListener('resize', checkOrientation);
  window.removeEventListener('orientationchange', checkOrientation);
  stopCameraStream();
});

const stopCameraStream = () => {
  if (cameraStream.value) {
    const tracks = cameraStream.value.getTracks();
    tracks.forEach(track => track.stop());
    cameraStream.value = null;
  }
};

const getCameraConstraints = () => {
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

  return {
    video: {
      facingMode: { ideal: 'environment' }, // Gunakan kamera belakang
      width: { ideal: isMobile ? 720 : 1280 },
      height: { ideal: isMobile ? 480 : 720 },
      frameRate: { ideal: 15, max: 30 }, // Batasi frame rate untuk performa
      focusMode: 'continuous', // Auto focus
      whiteBalanceMode: 'continuous', // Auto white balance
      exposureMode: 'continuous' // Auto exposure
    }
  };
};

const onInit = async (promise) => {
  try {
    const { capabilities } = await promise;
    cameraError.value = '';
    // Log device info untuk debugging
    console.log('Device info:', {
      userAgent: navigator.userAgent,
      isMobile: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
      screen: { width: screen.width, height: screen.height },
      window: { width: window.innerWidth, height: window.innerHeight }
    });
    console.log('Camera initialized successfully', capabilities);
  } catch (error) {
    console.error('Camera initialization error:', error);
    if (error.name === 'NotAllowedError') {
      cameraError.value = 'Akses kamera ditolak. Silakan izinkan akses kamera dan refresh halaman.';
    } else if (error.name === 'NotFoundError') {
      cameraError.value = 'Kamera tidak ditemukan di perangkat ini.';
    } else if (error.name === 'NotSupportedError') {
      cameraError.value = 'Browser tidak mendukung akses kamera.';
    } else {
      cameraError.value = 'Gagal mengakses kamera: ' + error.message;
    }
  }
};

const onDecode = async (decodedString) => {
  if (isLoading.value || isTakingPhoto.value || !isScanning.value) return;

  // Stop scanning temporarily
  isScanning.value = false;
  isLoading.value = true;
  errorMessage.value = '';
  scanResult.value = null;
  capturedImage.value = null;
  guestIdForPhoto.value = null;

  try {
    const response = await axios.post('/api/check-in/scan', {
      qr_code_token: decodedString,
    });

    scanResult.value = response.data;
    if (response.data.guest?.photo_required) {
      guestIdForPhoto.value = response.data.guest.guest_id;
      isTakingPhoto.value = true;
      await initPhotoCamera();
    } else {
      // Auto restart scanning after success feedback
      setTimeout(() => {
        resetScanner();
      }, 3000);
    }

  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan.';
    if (error.response?.data?.guest) {
      scanResult.value = error.response.data;
    }

    // Restart scanning after error
    setTimeout(() => {
      resetScanner();
    }, 2000);
  } finally {
    isLoading.value = false;
  }
};

const initPhotoCamera = async () => {
  try {
    const constraints = getCameraConstraints();
    const stream = await navigator.mediaDevices.getUserMedia(constraints);

    if (videoRef.value) {
        videoRef.value.srcObject = stream;
        cameraStream.value = stream;

        // Wait for video to be ready
        videoRef.value.onloadedmetadata = () => {
            console.log('Video metadata loaded:', {
            videoWidth: videoRef.value.videoWidth,
            videoHeight: videoRef.value.videoHeight
            });
        };
        }
    } catch (error) {
        errorMessage.value = 'Gagal mengakses kamera untuk foto: ' + error.message;
        console.error('Error accessing camera for photo:', error);
    }
};

const capturePhoto = () => {
  if (videoRef.value && canvasRef.value) {
    const video = videoRef.value;
    const canvas = canvasRef.value;
    const context = canvas.getContext('2d');

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    capturedImage.value = canvas.toDataURL('image/jpeg', 0.8);
  }
};

const uploadPhoto = async () => {
  if (!capturedImage.value || !guestIdForPhoto.value) {
    errorMessage.value = 'Silakan ambil foto terlebih dahulu.';
    return;
  }

  isLoading.value = true;
  errorMessage.value = '';

  try {
    const formData = new FormData();
    formData.append('guest_id', guestIdForPhoto.value);
    formData.append('photo', dataURLtoFile(capturedImage.value, `guest_${guestIdForPhoto.value}_${Date.now()}.jpg`));

    const response = await axios.post('/api/check-in/upload-photo', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    scanResult.value = response.data;

    // Reset and restart scanning
    setTimeout(() => {
      resetCameraAndFeedback();
    }, 3000);

  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gagal mengunggah foto.';
  } finally {
    isLoading.value = false;
  }
};

// Helper function to convert data URL to File object
function dataURLtoFile(dataurl, filename) {
  const arr = dataurl.split(',');
  const mime = arr.at(0).match(/:(.*?);/)?.[1];
  const bstr = atob(arr.at(1));
  let n = bstr.length;
  const u8arr = new Uint8Array(n);
  while (n--) {
    u8arr[n] = bstr.charCodeAt(n);
  }
  return new File([u8arr], filename, { type: mime });
}

const resetCameraAndFeedback = () => {
  stopCameraStream();
  isTakingPhoto.value = false;
  capturedImage.value = null;
  scanResult.value = null;
  errorMessage.value = '';
  guestIdForPhoto.value = null;
  resetScanner();
};

const resetScanner = () => {
  isScanning.value = true;
  scannerKey.value++;
};

const retakePhoto = () => {
  capturedImage.value = null;
  errorMessage.value = '';
};
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-4">
    <div class="max-w-md mx-auto">
      <!-- Header -->
      <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 text-center">
        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
          </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-800 mb-2">Digital Guest Book</h1>
        <p class="text-gray-600 text-sm">{{ event.groom_name }} & {{ event.bride_name }}</p>
      </div>

      <!-- Scanner Section -->
      <div v-if="!isTakingPhoto" class="bg-white rounded-2xl shadow-lg overflow-hidden mb-6">
        <div class="p-6">
          <div class="flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-2 0v1m0-1h-1m1 0h-1m-1-1V9a7 7 0 10-14 0v5.036a2 2 0 00.586 1.414L8 17v1a2 2 0 002 2h4a2 2 0 002-2v-1l2.586-2.586A2 2 0 0019 14.036V9a7 7 0 00-2-4.93" />
            </svg>
            <h2 class="text-lg font-semibold text-gray-800">Scan QR Code Undangan</h2>
          </div>

          <!-- Camera Error -->
          <div v-if="cameraError" class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-red-700 text-sm">{{ cameraError }}</span>
            </div>
          </div>

          <!-- Scanner Container -->
          <div v-if="!cameraError" class="scanner-container relative bg-black rounded-xl overflow-hidden">
            <qrcode-stream
              :key="scannerKey"
              @decode="onDecode"
              @init="onInit"
              :paused="!isScanning"
              :track="false"
              class="w-full h-64 object-cover"
            >
              <!-- Scanner Overlay -->
              <div class="scanner-overlay">
                <div class="scanner-box">
                  <div class="corner corner-tl"></div>
                  <div class="corner corner-tr"></div>
                  <div class="corner corner-bl"></div>
                  <div class="corner corner-br"></div>
                </div>
              </div>
            </qrcode-stream>

            <!-- Loading Overlay -->
            <div v-if="isLoading" class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
              <div class="bg-white rounded-lg p-4 flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-gray-800">Memproses...</span>
              </div>
            </div>
          </div>

          <p class="text-gray-500 text-sm text-center mt-4">
            Arahkan kamera ke QR code pada undangan Anda
          </p>
        </div>
      </div>

      <!-- Photo Capture Section -->
      <div v-if="isTakingPhoto" class="bg-white rounded-2xl shadow-lg overflow-hidden mb-6">
        <div class="p-6">
          <div class="flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <h2 class="text-lg font-semibold text-gray-800">Foto Kehadiran</h2>
          </div>

          <div v-if="!capturedImage" class="camera-preview relative bg-black rounded-xl overflow-hidden mb-4">
            <video ref="videoRef" autoplay playsinline muted class="w-full h-64 object-cover"></video>
            <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2">
              <button
                @click="capturePhoto"
                class="w-16 h-16 bg-white rounded-full border-4 border-indigo-600 flex items-center justify-center shadow-lg hover:bg-gray-50 transition-colors"
              >
                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </button>
            </div>
            <canvas ref="canvasRef" class="hidden"></canvas>
          </div>

          <div v-if="capturedImage" class="captured-image text-center mb-4">
            <img :src="capturedImage" alt="Captured Photo" class="w-full h-64 object-cover rounded-xl mb-4">
            <div class="flex space-x-3">
              <button
                @click="retakePhoto"
                class="flex-1 bg-gray-100 text-gray-700 py-3 px-4 rounded-xl font-medium hover:bg-gray-200 transition-colors"
              >
                Foto Ulang
              </button>
              <button
                @click="uploadPhoto"
                :disabled="isLoading"
                class="flex-1 bg-indigo-600 text-white py-3 px-4 rounded-xl font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                <span v-if="isLoading" class="flex items-center justify-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Mengunggah...
                </span>
                <span v-else>Simpan Foto</span>
              </button>
            </div>
          </div>

          <button
            @click="resetCameraAndFeedback"
            class="w-full bg-red-100 text-red-700 py-3 px-4 rounded-xl font-medium hover:bg-red-200 transition-colors"
          >
            Batal & Scan Ulang
          </button>
        </div>
      </div>

      <!-- Success Feedback -->
      <div v-if="scanResult && scanResult.status === 'success'" class="bg-green-50 border border-green-200 rounded-2xl p-6 mb-6">
        <div class="text-center">
          <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-green-800 mb-2">{{ scanResult.message }}</h3>
          <p v-if="scanResult.guest" class="text-green-700 font-medium mb-1">{{ scanResult.guest.name }}</p>
          <p v-if="scanResult.guest?.check_in_time" class="text-green-600 text-sm">
            Check-in: {{ scanResult.guest.check_in_time }}
          </p>
        </div>
      </div>

      <!-- Warning Feedback -->
      <div v-if="scanResult && scanResult.status === 'warning'" class="bg-yellow-50 border border-yellow-200 rounded-2xl p-6 mb-6">
        <div class="text-center">
          <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-yellow-800 mb-2">{{ scanResult.message }}</h3>
          <p v-if="scanResult.guest" class="text-yellow-700 font-medium mb-1">{{ scanResult.guest.name }}</p>
          <p v-if="scanResult.guest?.check_in_time" class="text-yellow-600 text-sm">
            Sudah check-in: {{ scanResult.guest.check_in_time }}
          </p>
        </div>
      </div>

      <!-- Error Feedback -->
      <div v-if="(scanResult && scanResult.status === 'error') || errorMessage" class="bg-red-50 border border-red-200 rounded-2xl p-6 mb-6">
        <div class="text-center">
          <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-red-800 mb-2">
            {{ scanResult?.message || 'Gagal!' }}
          </h3>
          <p class="text-red-700">{{ errorMessage }}</p>
        </div>
      </div>

      <!-- Tips Section -->
      <div class="bg-white rounded-2xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">Tips Scanning</h3>
        <ul class="space-y-2 text-sm text-gray-600">
          <li class="flex items-center">
            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Pastikan pencahayaan cukup terang
          </li>
          <li class="flex items-center">
            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Tahan perangkat dengan stabil
          </li>
          <li class="flex items-center">
            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            QR code harus berada dalam kotak scanner
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<style scoped>
.scanner-container {
  position: relative;
  background: #000;
}

.scanner-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1;
}

.scanner-box {
  position: relative;
  width: 200px;
  height: 200px;
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.corner {
  position: absolute;
  width: 20px;
  height: 20px;
  border: 3px solid #fff;
}

.corner-tl {
  top: -3px;
  left: -3px;
  border-right: none;
  border-bottom: none;
}

.corner-tr {
  top: -3px;
  right: -3px;
  border-left: none;
  border-bottom: none;
}

.corner-bl {
  bottom: -3px;
  left: -3px;
  border-right: none;
  border-top: none;
}

.corner-br {
  bottom: -3px;
  right: -3px;
  border-left: none;
  border-top: none;
}

.camera-preview video {
  transform: scaleX(-1); /* Mirror effect untuk selfie */
}

@media (max-width: 640px) {
  .scanner-box {
    width: 150px;
    height: 150px;
  }
}
</style>
