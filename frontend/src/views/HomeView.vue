<template>
  <div class="home-view">
    <!-- hero -->
    <section class="hero">
      <v-container class="hero-container py-12 py-md-16">
        <v-row class="align-center">
          <v-col cols="12" md="6" class="hero-text">

            <h1 class="hero-title">
              {{ $t('home.hero_title') }}
            </h1>
            <p class="hero-desc">
              {{ $t('home.hero_subtitle') }}
            </p>

            <div class="hero-actions">
              <v-btn to="/register" color="primary" rounded="xl" size="large" prepend-icon="mdi-rocket-launch">
                {{ $t('home.cta_start') }}
              </v-btn>
              <v-btn to="/about" variant="outlined" rounded="xl" size="large" append-icon="mdi-arrow-right">
                {{ $t('nav.about') }}
              </v-btn>
            </div>

            <div class="hero-trust">
              <span class="trust-icon">✓</span>
              <span>{{ $t('home.trust_line') }}</span>
            </div>
          </v-col>

          <v-col cols="12" md="6" class="hero-visual">
            <div class="visual-cards">
              <div class="card-mock card-1">
                <div class="card-dot"></div>
                <div class="card-text">
                  <div class="card-price">€45,99 <span class="card-old-price">€62,00</span> <v-icon icon="mdi-arrow-down" class="price-down"></v-icon></div>
                </div>
              </div>
              <div class="card-mock card-2">
                <div class="card-dot alert"></div>
                <div class="card-text">
                  <div class="card-price">€38,50 <span class="card-old-price">€49,99</span> <v-icon icon="mdi-arrow-down" class="price-down"></v-icon></div>
                </div>
              </div>
              <div class="card-mock card-3">
                <div class="card-dot"></div>
                <div class="card-text">
                  <div class="card-price">€41,20 <span class="card-old-price">€38,99</span> <v-icon icon="mdi-arrow-up" class="price-up"></v-icon></div>
                </div>
              </div>
            </div>
          </v-col>
        </v-row>
      </v-container>
    </section>

    <!-- second section -->
    <section class="features">
      <div class="section-header">
        <h2>{{ $t('home.powerful_features') }}</h2>
      </div>

      <div class="grid">
        <v-card class="feature-card" rounded="xl" variant="outlined">
          <v-card-text class="pa-4">
            <v-icon color="primary" size="32">mdi-bell-ring</v-icon>
            <h3>{{ $t('home.feature1_title') }}</h3>
            <p>{{ $t('home.feature1_desc') }}</p>
          </v-card-text>
        </v-card>

        <v-card class="feature-card" rounded="xl" variant="outlined">
          <v-card-text class="pa-4">
            <v-icon color="success" size="32">mdi-chart-timeline-variant</v-icon>
            <h3>{{ $t('home.feature2_title') }}</h3>
            <p>{{ $t('home.feature2_desc') }}</p>
          </v-card-text>
        </v-card>

        <v-card class="feature-card" rounded="xl" variant="outlined">
          <v-card-text class="pa-4">
            <v-icon color="warning" size="32">mdi-lightning-bolt</v-icon>
            <h3>{{ $t('home.feature3_title') }}</h3>
            <p>{{ $t('home.feature3_desc') }}</p>
          </v-card-text>
        </v-card>

        <v-card class="feature-card" rounded="xl" variant="outlined">
          <v-card-text class="pa-4">
            <v-icon color="info" size="32">mdi-store</v-icon>
            <h3>{{ $t('home.feature4_title') }}</h3>
            <p>{{ $t('home.feature4_desc') }}</p>
          </v-card-text>
        </v-card>

        <v-card class="feature-card" rounded="xl" variant="outlined">
          <v-card-text class="pa-4">
            <v-icon color="error" size="32">mdi-shield-check</v-icon>
            <h3>{{ $t('home.feature5_title') }}</h3>
            <p>{{ $t('home.feature5_desc') }}</p>
          </v-card-text>
        </v-card>

        <v-card class="feature-card" rounded="xl" variant="outlined">
          <v-card-text class="pa-4">
            <v-icon color="secondary" size="32">mdi-devices</v-icon>
            <h3>{{ $t('home.feature6_title') }}</h3>
            <p>{{ $t('home.feature6_desc') }}</p>
          </v-card-text>
        </v-card>
      </div>
    </section>

    <!-- third section -->
    <section class="steps">
      <div class="section-header">
        <div class="eyebrow">{{ $t('home.how_it_works') }}</div>
        <h2>{{ $t('home.steps_title') }}</h2>
      </div>

      <div class="steps-list">
        <div class="step">
          <div class="step-num">1</div>
          <div>
            <h3>{{ $t('home.step1_title') }}</h3>
            <p>{{ $t('home.step1_desc') }}</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div>
            <h3>{{ $t('home.step2_title') }}</h3>
            <p>{{ $t('home.step2_desc') }}</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div>
            <h3>{{ $t('home.step3_title') }}</h3>
            <p>{{ $t('home.step3_desc') }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- last section -->
    <section class="cta">
      <h2>{{ $t('home.ready_to_save') }}</h2>
      <p>{{ $t('home.cta_desc') }}</p>
      <v-btn to="/register" color="white" size="x-large" rounded="xl" prepend-icon="mdi-rocket">
        {{ $t('home.getStartedFree') }}
      </v-btn>
    </section>
  </div>
</template>

<script setup>

</script>

<style scoped>
.home-view {
  width: 100%;
  overflow-x: hidden;
}

/* Hero Section */
.hero {
  background:
    radial-gradient(800px 380px at 90% -10%, rgba(var(--v-theme-primary), 0.16), transparent 70%),
    radial-gradient(650px 300px at -10% 8%, rgba(var(--v-theme-secondary), 0.12), transparent 70%),
    linear-gradient(180deg, rgba(var(--v-theme-background), 1) 0%, rgba(var(--v-theme-surface), 1) 45%, rgba(var(--v-theme-background), 1) 100%);
  position: relative;
  overflow: hidden;
}

.hero-container {
  max-width: 1280px;
  position: relative;
  z-index: 2;
}

.hero-text {
  animation: slideInLeft 0.6s ease-out;
}

.hero-visual {
  animation: slideInRight 0.6s ease-out 0.2s both;
}

.eyebrow {
  display: inline-block;
  font-size: 0.75rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  padding: 0.4rem 0.8rem;
  border-radius: 999px;
  background: rgba(var(--v-theme-primary), 0.12);
  color: rgb(var(--v-theme-primary));
  border: 1px solid rgba(var(--v-theme-primary), 0.22);
  margin-bottom: 1rem;
  font-weight: 600;
}

.hero-title {
  font-size: clamp(2rem, 4.8vw, 3.2rem);
  line-height: 1.2;
  font-weight: 800;
  margin-bottom: 1rem;
  color: rgb(var(--v-theme-on-surface));
  padding-bottom: 0.5rem;
}

.hero-title span {
  display: inline-block;
  background: linear-gradient(120deg, rgb(var(--v-theme-primary)), #1e3a8a);
  line-height: 1.2;
  padding-bottom: 0.08em;
  background-clip: text;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.hero-desc {
  font-size: 1.05rem;
  line-height: 1.7;
  color: rgba(var(--v-theme-on-surface), 0.72);
  margin-bottom: 1.5rem;
  max-width: 520px;
}

.hero-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}

.hero-trust {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: rgba(var(--v-theme-on-surface), 0.62);
}

.trust-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: rgba(var(--v-theme-primary), 0.12);
  color: rgb(var(--v-theme-primary));
  font-weight: 700;
  font-size: 0.8rem;
}

.visual-cards {
  position: relative;
  height: 420px;
}

.card-mock {
  position: absolute;
  padding: 1rem;
  border-radius: 12px;
  background: rgba(var(--v-theme-surface), 0.9);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  backdrop-filter: blur(8px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
  display: flex;
  align-items: center;
  gap: 0.8rem;
  min-width: 220px;
}

.card-1 {
  top: 20px;
  right: 30px;
  animation: floatCard 4s ease-in-out infinite;
  z-index: 3;
}

.card-2 {
  top: 150px;
  left: 10px;
  animation: floatCard 4s ease-in-out infinite 0.6s;
  z-index: 2;
}

.card-3 {
  bottom: 30px;
  right: 50px;
  animation: floatCard 4s ease-in-out infinite 1.2s;
}

.card-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: rgba(var(--v-theme-primary), 0.8);
  flex-shrink: 0;
}

.card-dot.alert {
  background: #ff6b6b;
}

.card-label {
  font-size: 0.8rem;
  color: rgba(var(--v-theme-on-surface), 0.62);
  text-transform: uppercase;
  font-weight: 600;
}

.card-price {
  font-size: 1.1rem;
  font-weight: 700;
  color: rgb(var(--v-theme-on-surface));
}

.price-down {
  font-size: 0.9rem;
  color: #22c55e;
  margin-left: 0.4rem;
  vertical-align: middle;
}

.price-up {
  font-size: 0.9rem;
  color: #ef4444;
  margin-left: 0.4rem;
  vertical-align: middle;
}

.card-old-price {
  opacity: 0.35;
  text-decoration: line-through;
  font-size: 0.85rem;
  margin: 0 0.3rem;
  color: rgba(var(--v-theme-on-surface), 0.55);
}

/* features */
.features {
  padding: 4rem 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}

.section-header {
  text-align: center;
  margin-bottom: 3rem;
}

.section-header h2 {
  font-size: clamp(2rem, 4vw, 2.8rem);
  font-weight: 700;
  margin-top: 0.5rem;
  color: rgb(var(--v-theme-on-surface));
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.feature-card {
  background: rgba(var(--v-theme-surface), 0.9);
  transition: all 0.3s;
  border-color: rgba(var(--v-theme-on-surface), 0.12) !important;
}

.feature-card :deep(.v-card-text) {
  border-radius: inherit;
  background: rgba(var(--v-theme-surface), 0.95);
}

.feature-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 14px 28px rgba(20, 28, 44, 0.12);
}

.feature-card h3 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 1rem 0 0.5rem;
  color: rgb(var(--v-theme-on-surface));
}

.feature-card p {
  color: rgba(var(--v-theme-on-surface), 0.72);
  line-height: 1.5;
  font-size: 0.95rem;
}

/* steps */
.steps {
  padding: 5rem 1.5rem;
  background: rgba(var(--v-theme-surface), 0.55);
}

.steps-list {
  max-width: 800px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.step {
  display: grid;
  grid-template-columns: 64px 1fr;
  gap: 1rem;
  padding: 1.5rem;
  border-radius: 14px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  background: rgba(var(--v-theme-surface), 0.9);
  margin-bottom: 1rem;
}

.step-num {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.5rem;
  background: rgba(var(--v-theme-primary), 0.12);
  color: rgb(var(--v-theme-primary));
}

.step h3 {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 0.3rem;
  color: rgb(var(--v-theme-on-surface));
}

.step p {
  color: rgba(var(--v-theme-on-surface), 0.72);
  font-size: 0.95rem;
}

/* cta */
.cta {
  padding: 5rem 1.5rem;
  text-align: center;
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)), #1e3a8a);
}

.cta h2 {
  font-size: clamp(1.8rem, 3.5vw, 2.6rem);
  font-weight: 700;
  color: white;
  margin-bottom: 0.5rem;
}

.cta p {
  font-size: 1.1rem;
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 2rem;
}

@keyframes slideInLeft {
  from {
    opacity: 0;
    transform: translateX(-40px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(40px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes floatCard {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-12px);
  }
}

@media (max-width: 960px) {
  .hero-text {
    margin-bottom: 2rem;
  }
  .visual-cards {
    height: 300px;
  }
  .card-mock {
    min-width: 200px;
    font-size: 0.9rem;
  }
  .grid {
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
  }
}

@media (max-width: 600px) {
  .hero-title {
    font-size: 1.8rem;
  }
  .visual-cards {
    height: 280px;
  }
  .card-1, .card-2, .card-3 {
    position: relative !important;
    top: auto !important;
    bottom: auto !important;
    left: auto !important;
    right: auto !important;
    margin-bottom: 0.8rem;
    animation: none !important;
  }
}
</style>