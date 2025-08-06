<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  event: Object,
  guests: Array,
});

let intervalId = null;

// Fungsi untuk refresh data tamu
const refreshGuests = () => {
  router.reload({
    only: ['guests'], // Hanya minta prop 'guests' dari server, lebih efisien!
    preserveState: true,
    preserveScroll: true,
  });
};

onMounted(() => {
  // Set interval untuk refresh data setiap 5 detik
  intervalId = setInterval(refreshGuests, 5000);
});

onUnmounted(() => {
  // Hentikan interval saat komponen dihancurkan
  clearInterval(intervalId);
});
</script>

<template>
  <div>
    <h1>Daftar Tamu untuk: {{ event.groom_name }} & {{ event.bride_name }}</h1>
    <table>
      <thead>
        <tr>
          <th>Nama</th>
          <th>Status Konfirmasi</th>
          <th>Status Kehadiran</th>
          <th>Waktu Check-in</th>
          <th>Foto</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="guest in guests" :key="guest.id">
          <td>{{ guest.name }}</td>
          <td>{{ guest.confirmation_status }}</td>
          <td>{{ guest.attendance_status }}</td>
          <td>{{ guest.check_in_time }}</td>
          <td>
            <img
              v-if="guest.photo_path"
              :src="`/storage/${guest.photo_path}`"
              alt="Foto Tamu"
              width="100"
            />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
