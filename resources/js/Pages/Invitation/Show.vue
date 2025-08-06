<script setup>
import { useForm, Head } from '@inertiajs/vue3';

const props = defineProps({
  event: Object,
});

const form = useForm({
  name: '',
  message: '',
  confirmation_status: 'attending',
});

const submit = () => {
  form.post(route('invitation.rsvp', props.event.slug));
};
</script>

<template>
  <Head :title="'Undangan ' + event.bride_name + ' & ' + event.groom_name" />
  <h1>Undangan Pernikahan</h1>
  <h2>{{ event.bride_name }} & {{ event.groom_name }}</h2>
  <p>{{ event.event_date }} di {{ event.location }}</p>

  <form @submit.prevent="submit">
    <div>
      <label for="name">Nama Anda:</label>
      <input id="name" type="text" v-model="form.name" required>
      <div v-if="form.errors.name">{{ form.errors.name }}</div>
    </div>
    <div>
      <label>Konfirmasi Kehadiran:</label>
      <input type="radio" id="attending" value="attending" v-model="form.confirmation_status">
      <label for="attending">Akan Hadir</label>
      <input type="radio" id="not_attending" value="not_attending" v-model="form.confirmation_status">
      <label for="not_attending">Tidak Bisa Hadir</label>
    </div>
    <div>
      <label for="message">Ucapan & Doa:</label>
      <textarea id="message" v-model="form.message"></textarea>
    </div>
    <button type="submit" :disabled="form.processing">Kirim RSVP</button>
  </form>
</template>
