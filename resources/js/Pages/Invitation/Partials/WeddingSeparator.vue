<template>
  <div
    class="wedding-separator"
    :class="[`variant-${variant}`, compact ? 'is-compact' : '']"
    :style="{
      '--sep-color': color,
      '--sep-accent': accentColor,
      '--sep-width': thickness,
      '--sep-spacing': spacing,
      '--sep-maxw': maxWidth
    }"
    role="separator"
    aria-hidden="true"
  >
    <!-- VARIANT: FLOURISH (ornamental) -->
    <div v-if="variant === 'flourish'" class="flourish">
      <span class="flourish-line"></span>
      <span class="flourish-center">
        <svg viewBox="0 0 200 60" xmlns="http://www.w3.org/2000/svg" class="flourish-svg">
          <path
            d="M10,30 C30,5 60,5 80,30 C60,55 30,55 10,30 Z"
            fill="none" stroke="var(--sep-color)" stroke-width="1.5"/>
          <path
            d="M190,30 C170,5 140,5 120,30 C140,55 170,55 190,30 Z"
            fill="none" stroke="var(--sep-color)" stroke-width="1.5"/>
          <circle cx="100" cy="30" r="4.5" fill="var(--sep-accent)"/>
          <path d="M90 30 H110" stroke="var(--sep-color)" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </span>
      <span class="flourish-line"></span>
    </div>

    <!-- VARIANT: WAVE (gelombang lembut) -->
    <div v-else-if="variant === 'wave'" class="wave">
      <svg viewBox="0 0 1200 100" preserveAspectRatio="none">
        <path
          d="M0,50 C150,0 300,0 450,50 C600,100 750,100 900,50 C1050,0 1200,0 1200,0 L1200,100 L0,100 Z"
          :fill="accentColor" opacity="0.15"/>
        <path
          d="M0,60 C150,10 300,10 450,60 C600,110 750,110 900,60 C1050,10 1200,10 1200,10"
          :stroke="color" stroke-width="2" fill="none"/>
      </svg>
    </div>

    <!-- VARIANT: FLORAL (bunga di sudut) -->
    <div v-else-if="variant === 'floral'" class="floral">
      <div class="floral-corner left">
        <svg viewBox="0 0 80 80">
          <g stroke="var(--sep-color)" fill="none" stroke-linecap="round">
            <path d="M10,70 C15,40 35,30 50,20" stroke-width="1.5"/>
            <path d="M28,45 q4,-8 12,-8" stroke-width="1.2"/>
            <circle cx="55" cy="18" r="5" :fill="accentColor" stroke="none"/>
          </g>
        </svg>
      </div>
      <div class="floral-center">
        <div v-if="names || date" class="floral-text">
          <div class="names" v-if="names">{{ names }}</div>
          <div class="date" v-if="date">{{ date }}</div>
        </div>
        <div class="floral-line"></div>
      </div>
      <div class="floral-corner right">
        <svg viewBox="0 0 80 80" style="transform: scaleX(-1);">
          <g stroke="var(--sep-color)" fill="none" stroke-linecap="round">
            <path d="M10,70 C15,40 35,30 50,20" stroke-width="1.5"/>
            <path d="M28,45 q4,-8 12,-8" stroke-width="1.2"/>
            <circle cx="55" cy="18" r="5" :fill="accentColor" stroke="none"/>
          </g>
        </svg>
      </div>
    </div>

    <!-- VARIANT: DOTS (titik gradasi) -->
    <div v-else-if="variant === 'dots'" class="dots">
      <div class="dot-row">
        <span v-for="i in 11" :key="i" class="dot" :style="dotStyle(i)"></span>
      </div>
      <div class="dot-row reverse">
        <span v-for="i in 11" :key="'r'+i" class="dot" :style="dotStyle(i)"></span>
      </div>
    </div>

    <!-- VARIANT: LINE (garis tipis + monogram opsional) -->
    <div v-else class="line">
      <span class="line-el"></span>
      <span v-if="monogram" class="monogram">{{ monogram }}</span>
      <span class="line-el"></span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'WeddingSeparator',
  props: {
    variant: {
      type: String,
      default: 'flourish', // 'flourish' | 'wave' | 'floral' | 'dots' | 'line'
    },
    color: {
      type: String,
      default: '#4b5563', // teks/garis utama
    },
    accentColor: {
      type: String,
      default: '#c084fc', // aksen (bunga/titik/center)
    },
    thickness: {
      type: String,
      default: '1.5px',
    },
    spacing: {
      type: String,
      default: '28px', // jarak vertikal
    },
    maxWidth: {
      type: String,
      default: '820px',
    },
    names: {
      type: String,
      default: '', // "A & B"
    },
    date: {
      type: String,
      default: '', // "12.12.2025"
    },
    monogram: {
      type: String,
      default: '', // "A♥B" atau inisial
    },
    compact: {
      type: Boolean,
      default: false, // versi lebih hemat ruang
    }
  },
  methods: {
    dotStyle(i) {
      // Fade ke tengah: kecil di sisi, besar di tengah
      const idx = i - 1;
      const center = 5;
      const dist = Math.abs(center - idx);
      const size = 3 + (3 - dist) * 0.7; // 3px s/d ~6px
      const opacity = 0.25 + (1 - dist / center) * 0.6;
      return {
        width: `${size}px`,
        height: `${size}px`,
        opacity: opacity.toFixed(2),
        background: `radial-gradient(circle, ${this.accentColor}, ${this.accentColor})`
      };
    }
  }
}
</script>

<style scoped>
.wedding-separator {
  --sep-color: #4b5563;
  --sep-accent: #c084fc;
  --sep-width: 1.5px;
  --sep-spacing: 28px;
  --sep-maxw: 820px;

  display: grid;
  place-items: center;
  margin: var(--sep-spacing) auto;
  max-width: var(--sep-maxw);
}

/* General compact spacing */
.is-compact {
  --sep-spacing: 18px;
}

/* -------- Flourish -------- */
.flourish {
  width: 100%;
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 12px;
}
.flourish-line {
  height: 1px;
  background: linear-gradient(to right, transparent, var(--sep-color), transparent);
  opacity: 0.8;
}
.flourish-center {
  display: grid;
  place-items: center;
}
.flourish-svg {
  width: clamp(160px, 38vw, 320px);
  height: auto;
}

/* -------- Wave -------- */
.wave {
  width: 100%;
}
.wave svg {
  width: 100%;
  height: 64px;
  display: block;
}

/* -------- Floral -------- */
.floral {
  width: 100%;
  position: relative;
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 8px;
}
.floral-corner {
  width: 64px;
  height: 64px;
  opacity: 0.9;
}
.floral-center {
  display: grid;
  gap: 8px;
}
.floral-line {
  height: 1px;
  background: linear-gradient(to right, transparent, var(--sep-color), transparent);
  opacity: 0.6;
}
.floral-text {
  text-align: center;
  line-height: 1.25;
}
.names {
  font-family: "Cormorant Garamond", "Times New Roman", serif;
  font-size: clamp(18px, 3vw, 24px);
  letter-spacing: 0.5px;
  color: var(--sep-color);
}
.date {
  font-size: 12px;
  opacity: 0.8;
  color: var(--sep-color);
}

/* -------- Dots -------- */
.dots {
  display: grid;
  gap: 6px;
  width: 100%;
}
.dot-row {
  display: flex;
  justify-content: center;
  gap: 8px;
}
.dot-row.reverse {
  transform: scaleX(-1);
}
.dot {
  display: inline-block;
  border-radius: 999px;
  filter: drop-shadow(0 1px 0 rgba(0,0,0,0.08));
}

/* -------- Line -------- */
.line {
  width: 100%;
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 10px;
  align-items: center;
}
.line-el {
  height: var(--sep-width);
  background: linear-gradient(to right, transparent, var(--sep-color), transparent);
  opacity: 0.85;
  border-radius: 1px;
}
.monogram {
  font-family: "Cinzel Decorative", "Playfair Display", serif;
  color: var(--sep-accent);
  letter-spacing: 1px;
  font-size: clamp(14px, 2.6vw, 18px);
  line-height: 1;
  padding: 4px 10px;
  border: 1px solid color-mix(in srgb, var(--sep-accent) 55%, transparent);
  border-radius: 999px;
  background: color-mix(in srgb, var(--sep-accent) 12%, transparent);
  backdrop-filter: blur(2px);
}
</style>
