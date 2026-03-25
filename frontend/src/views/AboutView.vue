<template>
  <div class="about-view">
    <!-- Intro Section -->
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

    <!-- Our Story -->
    <section class="our-story">
      <v-container>
        <div class="section-header">
          <h2>{{ $t('about.ourStory') }}</h2>
        </div>

        <div class="vertical-timeline">
          <!-- 2025 -->
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

          <!-- 2026 -->
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

          <!-- Future -->
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

    <!-- Short Story Section -->
    <section class="story-section">
      <v-container class="py-12 py-md-16">
        <v-row>
          <v-col cols="12" md="8" offset-md="2">
            <div class="story-card">
              <h2>{{ $t('about.howItStarted') }}</h2>
              <p>
                {{ $t('about.storyPart1') }}
              </p>
              <p>
                {{ $t('about.storyPart2') }}
              </p>
              <p>
                {{ $t('about.storyPart3') }}
              </p>
            </div>
          </v-col>
        </v-row>
      </v-container>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
      <v-container class="py-12 py-md-16">
        <div class="section-header">
          <h2>{{ $t('about.getInTouch') }}</h2>
          <p class="section-subtitle">{{ $t('about.haveQuestion') }}</p>
        </div>

        <div class="contact-form-wrapper">
          <div class="contact-form">
            <h3>{{ $t('about.sendMessage') }}</h3>
            <p class="section-subtitle">{{ $t('about.haveQuestion') }}</p>
          </div>
        </div>
      </v-container>
    </section>

    <!-- CTA -->
    <section class="cta-final">
      <h2>{{ $t('about.readyToSave') }}</h2>
      <p>{{ $t('about.joinUsers') }}</p>
      <v-btn to="/register" color="white" size="x-large" rounded="xl" prepend-icon="mdi-rocket">
        {{ $t('about.startTracking') }}
      </v-btn>
    </section>
  </div>
</template>

<style scoped>
.about-view {
  width: 100%;
  overflow-x: hidden;
}

/* Intro Section */
.about-intro {
  background:
    radial-gradient(800px 380px at 90% -10%, rgba(var(--v-theme-primary), 0.16), transparent 70%),
    radial-gradient(650px 300px at -10% 8%, rgba(var(--v-theme-secondary), 0.12), transparent 70%),
    linear-gradient(180deg, rgba(var(--v-theme-background), 1) 0%, rgba(var(--v-theme-surface), 1) 45%, rgba(var(--v-theme-background), 1) 100%);
  padding: 3rem 1.5rem;
}

.intro-content {
  max-width: 800px;
  margin: 0 auto;
  animation: slideInLeft 0.6s ease-out;
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
  max-width: 600px;
}

/* Our Story - Vertical Timeline */
.our-story {
  padding: 5rem 1.5rem;
  background: rgba(var(--v-theme-surface), 0.3);
}

.section-header {
  text-align: center;
  margin-bottom: 3rem;
}

.section-header h2 {
  font-size: clamp(2rem, 4vw, 2.8rem);
  font-weight: 700;
  color: rgb(var(--v-theme-on-surface));
  margin-bottom: 0.5rem;
}

.section-subtitle {
  font-size: 1rem;
  color: rgba(var(--v-theme-on-surface), 0.62);
}

.vertical-timeline {
  max-width: 900px;
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
  margin-bottom: 3rem;
  align-items: start;
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
  animation: slideInLeft 0.6s ease-out both;
}

.timeline-block:nth-child(2) .timeline-card {
  animation-delay: 0.2s;
}

.timeline-block:nth-child(3) .timeline-card {
  animation-delay: 0.4s;
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
  color: rgba(var(--v-theme-on-surface), 0.72);
}

.timeline-features .v-icon {
  color: rgb(var(--v-theme-primary));
}

/* Story Section */
.story-section {
  padding: 4rem 1.5rem;
}

.story-card {
  padding: 2.5rem;
  border-radius: 16px;
  background: rgba(var(--v-theme-surface), 0.9);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  animation: slideInLeft 0.6s ease-out;
}

.story-card h2 {
  font-size: 2rem;
  font-weight: 700;
  color: rgb(var(--v-theme-on-surface));
  margin-bottom: 1.5rem;
}

.story-card p {
  font-size: 1rem;
  line-height: 1.8;
  color: rgba(var(--v-theme-on-surface), 0.72);
  margin-bottom: 1.5rem;
}

.story-card strong {
  color: rgb(var(--v-theme-primary));
  font-weight: 600;
}

/* Contact Section */
.contact-section {
  padding: 5rem 1.5rem;
  background: rgba(var(--v-theme-surface), 0.5);
}

.contact-grid {
  max-width: 1200px;
  margin: 0 auto 3rem;
}

.contact-card {
  padding: 1.5rem;
  border-radius: 12px;
  background: rgba(var(--v-theme-surface), 0.9);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  text-align: center;
  transition: all 0.3s;
}

.contact-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(20, 28, 44, 0.12);
}

.contact-card h3 {
  font-size: 1.1rem;
  font-weight: 600;
  color: rgb(var(--v-theme-on-surface));
  margin: 1rem 0 0.5rem;
}

.contact-card p {
  font-size: 0.95rem;
  color: rgba(var(--v-theme-on-surface), 0.72);
}

.contact-card a {
  color: rgb(var(--v-theme-primary));
  text-decoration: none;
  transition: all 0.2s;
}

.contact-card a:hover {
  text-decoration: underline;
}

.contact-form-wrapper {
  max-width: 600px;
  margin: 0 auto;
  padding: 2rem;
  border-radius: 16px;
  background: rgba(var(--v-theme-surface), 0.95);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
}

.contact-form h3 {
  font-size: 1.3rem;
  font-weight: 600;
  color: rgb(var(--v-theme-on-surface));
  margin-bottom: 1.5rem;
  text-align: center;
}

/* CTA Final */
.cta-final {
  padding: 4rem 1.5rem;
  text-align: center;
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)), #1e3a8a);
}

.cta-final h2 {
  font-size: clamp(1.8rem, 3.5vw, 2.4rem);
  font-weight: 700;
  color: white;
  margin-bottom: 0.5rem;
}

.cta-final p {
  font-size: 1.1rem;
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 2rem;
}

/* Animations */
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

/* Responsive */
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

  .contact-grid {
    gap: 1rem;
  }
}

@media (max-width: 600px) {
  .intro-title {
    font-size: 1.8rem;
  }

  .intro-text {
    font-size: 0.95rem;
  }

  .story-card {
    padding: 1.5rem;
  }

  .story-card h2 {
    font-size: 1.5rem;
  }

  .contact-form-wrapper {
    padding: 1.5rem;
  }

  .cta-final h2 {
    font-size: 1.5rem;
  }

  .cta-final p {
    font-size: 1rem;
  }
}
</style>