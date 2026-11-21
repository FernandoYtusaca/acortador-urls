<template>
  <main class="auth-shell">
    <section class="auth-hero">
      <div class="brand brand-light">
        <span class="brand-mark">
          <svg
            viewBox="0 0 24 24"
            width="20"
            height="20"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
          </svg>
        </span>
        <span class="brand-name">Acortador de URLs</span>
      </div>

      <h2 class="hero-title">Acorta, comparte y mide tus enlaces</h2>
      <p class="hero-text">
        Crea enlaces cortos, ponles fecha de caducidad y consulta cuántas personas los visitan, día
        a día.
      </p>

      <ul class="hero-points">
        <li>Alias personalizados</li>
        <li>Estadísticas por día</li>
        <li>Enlaces con caducidad</li>
      </ul>
    </section>

    <section class="auth-panel">
      <slot />
    </section>
  </main>
</template>
