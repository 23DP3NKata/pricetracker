<template>
  <div class="about-view">
    <section class="about-intro">
      <v-container class="py-8 py-md-12">
        <div class="intro-content">
          <h1 class="intro-title">{{ $t('about.title') }}</h1>
          <p class="intro-text">
            {{ $t('about.intro') }}
          </p>
        </div>
      </v-container>
    </section>

    <section class="our-story">
      <v-container>
        <div class="section-header">
          <h2>{{ $t('about.ourStory') }}</h2>
        </div>

        <div class="vertical-timeline">
          <div class="timeline-block">
            <div class="timeline-year">2025</div>
            <div class="timeline-card">
              <h3>{{ $t('about.timeline2025Title') }}</h3>
              <p>{{ $t('about.timeline2025Desc') }}</p>
              <ul class="timeline-features">
                <li><v-icon size="16">mdi-check</v-icon> {{ $t('about.timeline2025Feature1') }}</li>
                <li><v-icon size="16">mdi-check</v-icon> {{ $t('about.timeline2025Feature2') }}</li>
              </ul>
            </div>
          </div>

          <div class="timeline-block">
            <div class="timeline-year">2026</div>
            <div class="timeline-card">
              <h3>{{ $t('about.timeline2026Title') }}</h3>
              <p>{{ $t('about.timeline2026Desc') }}</p>
              <ul class="timeline-features">
                <li><v-icon size="16">mdi-check</v-icon> {{ $t('about.timeline2026Feature1') }}</li>
                <li><v-icon size="16">mdi-check</v-icon> {{ $t('about.timeline2026Feature2') }}</li>
              </ul>
            </div>
          </div>

          <div class="timeline-block">
            <div class="timeline-year">{{ $t('about.timelineFutureYear') }}</div>
            <div class="timeline-card">
              <h3>{{ $t('about.timelineFutureTitle') }}</h3>
              <p>{{ $t('about.timelineFutureDesc') }}</p>
              <ul class="timeline-features">
                <li><v-icon size="16">mdi-check</v-icon> {{ $t('about.timelineFutureFeature1') }}</li>
                <li><v-icon size="16">mdi-check</v-icon> {{ $t('about.timelineFutureFeature2') }}</li>
              </ul>
            </div>
          </div>
        </div>
      </v-container>
    </section>

    <section class="story-section">
      <v-container class="py-12 py-md-16">
        <div class="story-content">
          <h2>{{ $t('about.howItStarted') }}</h2>
          <p>{{ $t('about.storyPart1') }}</p>
          <p>{{ $t('about.storyPart2') }}</p>
          <p>{{ $t('about.storyPart3') }}</p>
        </div>
      </v-container>
    </section>

    <section class="cta-final">
      <h2>{{ $t('about.readyToSave') }}</h2>
      <p>{{ $t('about.joinUsers') }}</p>
      <v-btn to="/register" color="primary" size="large" prepend-icon="mdi-rocket">
        {{ $t('about.startTracking') }}
      </v-btn>
    </section>
  </div>
</template>

<style scoped>
.about-view {
  width: 100%;
  overflow-x: hidden;
  background: rgb(var(--v-theme-background));
}

.about-intro {
  background: rgb(var(--v-theme-background));
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  padding: 3rem 1.5rem;
}

.intro-content {
  max-width: 800px;
  margin: 0 auto;
}

.intro-title {
  font-size: clamp(2rem, 4vw, 2.8rem);
  font-weight: 800;
  color: rgb(var(--v-theme-on-surface));
  margin-bottom: 1rem;
}

.intro-text {
  font-size: 1.05rem;
  line-height: 1.7;
  color: rgba(var(--v-theme-on-surface), 0.72);
  max-width: 680px;
}

.our-story {
  padding: 5rem 1.5rem;
  background: rgba(var(--v-theme-surface), 0.3);
}

.section-header {
  text-align: center;
  margin-bottom: 2.2rem;
}

.section-header h2 {
  font-size: clamp(1.8rem, 3.8vw, 2.4rem);
  font-weight: 700;
  color: rgb(var(--v-theme-on-surface));
}

.vertical-timeline {
  max-width: 860px;
  margin: 0 auto;
  position: relative;
}

.vertical-timeline::before {
  content: '';
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  width: 2px;
  height: 100%;
  background: linear-gradient(180deg, rgba(var(--v-theme-primary), 0), rgba(var(--v-theme-primary), 0.3), rgba(var(--v-theme-primary), 0));
}

.timeline-block {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  align-items: start;
  margin-bottom: 3rem;
  position: relative;
}

.timeline-block:nth-child(even) {
  direction: rtl;
}

.timeline-block:nth-child(even) .timeline-card {
  direction: ltr;
}

.timeline-year {
  text-align: center;
  font-size: 1.5rem;
  font-weight: 700;
  color: rgb(var(--v-theme-primary));
  padding-top: 1rem;
}

.timeline-card {
  padding: 1.5rem;
  border-radius: 12px;
  background: rgba(var(--v-theme-surface), 0.9);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  backdrop-filter: blur(8px);
  transition: all 0.3s ease;
}

.timeline-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(20, 28, 44, 0.12);
}

.timeline-card h3 {
  font-size: 1.2rem;
  font-weight: 600;
  color: rgb(var(--v-theme-on-surface));
  margin-bottom: 0.5rem;
}

.timeline-card p {
  font-size: 0.95rem;
  color: rgba(var(--v-theme-on-surface), 0.72);
  margin-bottom: 1rem;
  line-height: 1.6;
}

.timeline-features {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

.timeline-features li {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.85rem;
  color: rgba(var(--v-theme-on-surface), 0.68);
}

.timeline-features .v-icon {
  color: rgb(var(--v-theme-primary));
}

.story-section {
  padding: 4rem 1.5rem;
  border-top: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.story-content {
  max-width: 760px;
  margin: 0 auto;
}

.story-content h2 {
  font-size: clamp(1.7rem, 3vw, 2rem);
  font-weight: 700;
  color: rgb(var(--v-theme-on-surface));
  margin-bottom: 0.9rem;
}

.story-content p {
  font-size: 1rem;
  line-height: 1.7;
  color: rgba(var(--v-theme-on-surface), 0.72);
  margin-bottom: 0.8rem;
}

.cta-final {
  padding: 3rem 1.5rem 4rem;
  text-align: center;
  background: transparent;
  border-top: 1px solid rgba(var(--v-theme-on-surface), 0.1);
}

.cta-final h2 {
  font-size: clamp(1.6rem, 3.2vw, 2.2rem);
  font-weight: 700;
  color: rgb(var(--v-theme-on-surface));
  margin-bottom: 0.5rem;
}

.cta-final p {
  font-size: 1rem;
  color: rgba(var(--v-theme-on-surface), 0.7);
  margin-bottom: 1.25rem;
}

@media (max-width: 960px) {
  .vertical-timeline::before {
    display: none;
  }

  .timeline-block {
    grid-template-columns: 1fr;
    gap: 1rem;
    direction: ltr !important;
  }

  .timeline-block:nth-child(even) {
    direction: ltr;
  }

  .timeline-year {
    margin-bottom: 1rem;
  }
}

@media (max-width: 600px) {
  .intro-title {
    font-size: 1.8rem;
  }

  .intro-text {
    font-size: 0.95rem;
  }

  .cta-final h2 {
    font-size: 1.5rem;
  }
}
</style>
