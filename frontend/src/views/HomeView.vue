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
              <v-btn to="/register" color="primary" size="large" prepend-icon="mdi-rocket-launch">
                {{ $t('home.cta_start') }}
              </v-btn>
              <v-btn to="/about" variant="outlined" size="large" append-icon="mdi-arrow-right">
                {{ $t('nav.about') }}
              </v-btn>
            </div>
          </v-col>

          <v-col cols="12" md="6" class="hero-visual">
            <div class="visual-cards">
              <div class="card-mock card-1">
                <div class="card-dot"></div>
                <div class="card-text">
                  <div class="card-price">BTC $82,410 <span class="card-old-price">$83,220</span> <v-icon icon="mdi-arrow-down" class="price-down"></v-icon></div>
                </div>
              </div>
              <div class="card-mock card-2">
                <div class="card-dot alert"></div>
                <div class="card-text">
                  <div class="card-price">ETH $1,920 <span class="card-old-price">$2,050</span> <v-icon icon="mdi-arrow-down" class="price-down"></v-icon></div>
                </div>
              </div>
              <div class="card-mock card-3">
                <div class="card-dot"></div>
                <div class="card-text">
                  <div class="card-price">SOL $168 <span class="card-old-price">$161</span> <v-icon icon="mdi-arrow-up" class="price-up"></v-icon></div>
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
        <div class="feature-item">
          <v-icon color="primary" size="30">mdi-bell-ring</v-icon>
          <h3>{{ $t('home.feature1_title') }}</h3>
          <p>{{ $t('home.feature1_desc') }}</p>
        </div>

        <div class="feature-item">
          <v-icon color="success" size="30">mdi-chart-timeline-variant</v-icon>
          <h3>{{ $t('home.feature2_title') }}</h3>
          <p>{{ $t('home.feature2_desc') }}</p>
        </div>

        <div class="feature-item">
          <v-icon color="warning" size="30">mdi-lightning-bolt</v-icon>
          <h3>{{ $t('home.feature3_title') }}</h3>
          <p>{{ $t('home.feature3_desc') }}</p>
        </div>
      </div>
    </section>

    <!-- third section -->
    <section class="steps">
      <div class="section-header">
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
      <v-btn to="/register" color="primary" size="large" prepend-icon="mdi-rocket">
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
  background: rgb(var(--v-theme-background));
}

/* Hero Section */
.hero {
  background: rgb(var(--v-theme-background));
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.hero-container {
  max-width: 1280px;
  position: relative;
  z-index: 2;
}

.hero-text {
  animation: none;
}

.hero-visual {
  animation: none;
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
  display: inline;
  background: none;
  line-height: inherit;
  padding-bottom: 0;
  -webkit-text-fill-color: currentColor;
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
  gap: 1rem;
}

.feature-item {
  padding: 4px 2px;
}

.feature-item h3 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0.7rem 0 0.45rem;
  color: rgb(var(--v-theme-on-surface));
}

.feature-item p {
  color: rgba(var(--v-theme-on-surface), 0.72);
  line-height: 1.5;
  font-size: 0.95rem;
}

/* steps */
.steps {
  padding: 5rem 1.5rem;
  background: transparent;
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
  grid-template-columns: 40px 1fr;
  gap: 1rem;
  padding: 1rem 0;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.1);
}

.step:last-child {
  border-bottom: 0;
}

.step-num {
  width: auto;
  height: auto;
  font-weight: 700;
  font-size: 1.2rem;
  color: rgba(var(--v-theme-on-surface), 0.72);
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
  padding: 3rem 1.5rem 4rem;
  text-align: center;
  background: transparent;
  border-top: 1px solid rgba(var(--v-theme-on-surface), 0.1);
}

.cta h2 {
  font-size: clamp(1.6rem, 3.2vw, 2.2rem);
  font-weight: 700;
  color: rgb(var(--v-theme-on-surface));
  margin-bottom: 0.5rem;
}

.cta p {
  font-size: 1rem;
  color: rgba(var(--v-theme-on-surface), 0.7);
  margin-bottom: 1.25rem;
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
