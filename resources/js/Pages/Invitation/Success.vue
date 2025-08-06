<script setup>
import QrCode from 'vue-qrcode-component';

const props = defineProps({
  guest: Object,
});

// Data yang akan di-encode di dalam QR code adalah token uniknya.
const qrCodeValue = props.guest.qr_code_token;
</script>

<template>
  <div>
    <h2>Terima Kasih, {{ guest.name }}!</h2>
    <p>Konfirmasi kehadiran Anda telah kami terima.</p>
    <p>Silakan tunjukkan QR Code di bawah ini di meja resepsionis saat acara.</p>

    <qr-code
      :text="qrCodeValue"
      :size="200"
      error-level="H"
    ></qr-code>

    <p><strong>Penting:</strong> Jangan bagikan QR Code ini kepada orang lain.</p>
  </div>
</template>
