<template>
  <div class="home-view">
    <!-- Hero Section -->
    <section class="hero">
      <div class="hero-content">
        <div class="badge">
          <v-icon size="14">mdi-chart-line-variant</v-icon>
          Track. Save. Repeat.
        </div>
        
        <h1 class="title">
          Never Miss a <span class="gradient">Price Drop</span> Again
        </h1>
        
        <p class="subtitle">
          Track prices across thousands of stores. Get instant alerts when products go on sale.
        </p>
        
        <div class="actions">
          <v-btn color="primary" size="large" rounded="xl" prepend-icon="mdi-plus">
            Start Tracking Free
          </v-btn>
          <v-btn variant="outlined" size="large" rounded="xl" append-icon="mdi-play">
            See Demo
          </v-btn>
        </div>

        <div class="stats">
          <div class="stat">
            <div class="stat-value">...</div>
            <div class="stat-label">Products</div>
          </div>
          <div class="stat">
            <div class="stat-value">...</div>
            <div class="stat-label">Saved</div>
          </div>
          <div class="stat">
            <div class="stat-value">...</div>
            <div class="stat-label">Users</div>
          </div>
        </div>
      </div>

      <div class="bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
      </div>
    </section>

    <!-- Features -->
    <section class="features">
      <div class="section-header">
        <div class="badge secondary">Features</div>
        <h2>Everything You Need</h2>
      </div>

      <div class="grid">
        <div class="card" v-for="f in features" :key="f.title">
          <v-icon :color="f.color" size="32">{{ f.icon }}</v-icon>
          <h3>{{ f.title }}</h3>
          <p>{{ f.desc }}</p>
        </div>
      </div>
    </section>

    <!-- Steps -->
    <section class="steps">
      <div class="section-header">
        <div class="badge secondary">How It Works</div>
        <h2>Start Saving in 3 Steps</h2>
      </div>

      <div class="steps-list">
        <div class="step" v-for="(s, i) in steps" :key="i">
          <div class="step-num">{{ i + 1 }}</div>
          <div>
            <h3>{{ s.title }}</h3>
            <p>{{ s.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="cta">
      <h2>Ready to Start Saving?</h2>
      <p>Join thousands tracking prices and saving money daily.</p>
      <v-btn color="white" size="x-large" rounded="xl" prepend-icon="mdi-rocket">
        Get Started Free
      </v-btn>
    </section>
  </div>
</template>

<script setup>
const features = [
  { icon: 'mdi-bell-ring', color: 'primary', title: 'Instant Alerts', desc: 'Get notified when prices drop' },
  { icon: 'mdi-chart-timeline-variant', color: 'success', title: 'Price History', desc: 'View detailed trends' },
  { icon: 'mdi-lightning-bolt', color: 'warning', title: 'Smart Tracking', desc: 'AI predicts best time to buy' },
  { icon: 'mdi-store', color: 'info', title: 'Multi-Store', desc: 'Track across 2 stores' },
  { icon: 'mdi-shield-check', color: 'error', title: 'Privacy First', desc: 'Your data is encrypted' },
  { icon: 'mdi-devices', color: 'secondary', title: 'Cross-Platform', desc: 'Access from any device' }
]

const steps = [
  { title: 'Add Product URL', desc: 'Paste the link of any product' },
  { title: 'Set Target Price', desc: 'Tell us your ideal price' },
  { title: 'Get Notified', desc: 'Receive alerts on price drops' }
]
</script>

<style scoped>
.home-view { width: 100%; overflow-x: hidden; }

/* Hero */
.hero {
  position: relative;
  min-height: 85vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3rem 1.5rem;
  overflow: hidden;
}

.hero-content {
  position: relative;
  z-index: 2;
  max-width: 800px;
  text-align: center;
  animation: fadeUp 0.8s ease-out;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: rgba(var(--v-theme-primary), 0.1);
  border: 1px solid rgba(var(--v-theme-primary), 0.2);
  border-radius: 50px;
  font-size: 0.875rem;
  font-weight: 600;
  color: rgb(var(--v-theme-primary));
  margin-bottom: 2rem;
}

.badge.secondary {
  background: rgba(var(--v-theme-secondary), 0.1);
  border-color: rgba(var(--v-theme-secondary), 0.2);
  color: rgb(var(--v-theme-secondary));
}

.title {
  font-size: clamp(2.5rem, 5vw, 4rem);
  font-weight: 800;
  line-height: 1.1;
  margin-bottom: 1.5rem;
  color: rgb(var(--v-theme-on-surface));
}

.gradient {
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)), rgb(var(--v-theme-secondary)));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.subtitle {
  font-size: 1.25rem;
  line-height: 1.6;
  color: rgba(var(--v-theme-on-surface), 0.7);
  margin-bottom: 2.5rem;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

.actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 3rem;
}

.actions .v-btn {
  text-transform: none;
  font-weight: 600;
  letter-spacing: 0;
  transition: transform 0.2s;
}

.actions .v-btn:hover { transform: translateY(-2px); }

.stats {
  display: flex;
  gap: 2rem;
  justify-content: center;
  flex-wrap: wrap;
}

.stat { text-align: center; }

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: rgb(var(--v-theme-primary));
}

.stat-label {
  font-size: 0.875rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
}

/* Background */
.bg {
  position: absolute;
  inset: 0;
  z-index: 1;
  opacity: 0.5;
  overflow: hidden;
}

.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  animation: float 15s ease-in-out infinite;
}

.orb-1 {
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgb(var(--v-theme-primary)), transparent);
  top: -10%;
  right: 10%;
}

.orb-2 {
  width: 350px;
  height: 350px;
  background: radial-gradient(circle, rgb(var(--v-theme-secondary)), transparent);
  bottom: -10%;
  left: 5%;
  animation-delay: -7s;
}

/* Features */
.features {
  padding: 5rem 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}

.section-header {
  text-align: center;
  margin-bottom: 3rem;
}

.section-header h2 {
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: 700;
  margin-top: 0.5rem;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.card {
  padding: 2rem;
  background: rgba(var(--v-theme-surface), 1);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 16px;
  transition: all 0.3s;
}

.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.card h3 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 1rem 0 0.5rem;
}

.card p {
  color: rgba(var(--v-theme-on-surface), 0.7);
  line-height: 1.5;
}

/* Steps */
.steps {
  padding: 5rem 1.5rem;
  background: rgba(var(--v-theme-primary), 0.03);
}

.steps-list {
  max-width: 800px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.step {
  display: flex;
  gap: 1.5rem;
  align-items: center;
  padding: 2rem;
  background: rgba(var(--v-theme-surface), 1);
  border-radius: 16px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  transition: transform 0.3s;
}

.step:hover { transform: translateX(8px); }

.step-num {
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: 800;
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)), rgb(var(--v-theme-secondary)));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  flex-shrink: 0;
}

.step h3 {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.step p {
  color: rgba(var(--v-theme-on-surface), 0.7);
}

/* CTA */
.cta {
  padding: 5rem 1.5rem;
  text-align: center;
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)), rgb(var(--v-theme-secondary)));
  color: white;
}

.cta h2 {
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: 700;
  margin-bottom: 1rem;
}

.cta p {
  font-size: 1.25rem;
  opacity: 0.9;
  margin-bottom: 2rem;
}

.cta .v-btn {
  text-transform: none;
  font-weight: 600;
  letter-spacing: 0;
  color: rgb(var(--v-theme-primary)) !important;
}

/* Animations */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes float {
  0%, 100% { transform: translate(0, 0); }
  50% { transform: translate(20px, -20px); }
}

/* Responsive */
@media (max-width: 600px) {
  .actions { flex-direction: column; width: 100%; }
  .actions .v-btn { width: 100%; }
  .step { flex-direction: column; text-align: center; }
}
</style>