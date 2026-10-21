<script setup>
import ThemeToggle from './ThemeToggle.vue'
</script>

<template>
  <div class="app-shell">
    <header class="site-header">
      <div class="header-inner">
        <router-link to="/" class="logo">Zaskia.</router-link>
        <div class="header-right">
          <span class="tagline">A Personal Journal</span>
          <ThemeToggle />
        </div>
      </div>
    </header>

    <main class="site-main">
      <slot />
    </main>

    <footer class="site-footer">
      <div class="footer-inner">
        <span class="copyright">© 2026 Zaskia. All words, thoughtfully kept.</span>
        <span class="footer-quote">A little more attention. A little less hurry.</span>
      </div>
    </footer>
  </div>
</template>
