// resources/js/Composables/useMusicPlayer.js
import { ref } from 'vue';

const audioPlayer = ref(null);
const isPlaying = ref(false);
const originalVolume = ref(1.0); // Simpan volume asli

// Buat satu instance yang bisa di-share
const useMusicPlayer = () => {
    const setAudioElement = (el) => {
        audioPlayer.value = el;
        if(audioPlayer.value) {
            originalVolume.value = audioPlayer.value.volume;
        }
    };

    const toggleMusic = () => {
        if (!audioPlayer.value) return;
        if (audioPlayer.value.paused) {
            audioPlayer.value.play();
            isPlaying.value = true;
        } else {
            audioPlayer.value.pause();
            isPlaying.value = false;
        }
    };

    // FUNGSI BARU
    const lowerVolume = () => {
        if (audioPlayer.value) {
            audioPlayer.value.volume = 0.1; // Kecilkan volume menjadi 10%
        }
    };

    // FUNGSI BARU
    const restoreVolume = () => {
        if (audioPlayer.value) {
            audioPlayer.value.volume = originalVolume.value; // Kembalikan ke volume asli
        }
    };

    return {
        audioPlayer,
        isPlaying,
        setAudioElement,
        toggleMusic,
        lowerVolume,
        restoreVolume
    };
};

export default useMusicPlayer;
