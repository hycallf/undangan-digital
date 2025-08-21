<script setup>
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import { ref } from 'vue'

const events = ref([
  { title: 'Akad Nikah', start: '2025-08-20T09:00:00' },
  { title: 'Resepsi', start: '2025-08-20T13:00:00' },
  { title: 'Family Dinner', start: '2025-08-21T19:00:00' }
])

const calendarOptions = ref({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  initialDate: new Date(),
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay'
  },
  events: events.value,
  eventClick(info) {
    alert(`Event: ${info.event.title}\nStart: ${info.event.start}`)
    // di sini nanti bisa kamu ganti buka modal
  }
})
</script>

<template>
  <FullCalendar :options="calendarOptions" />
</template>
