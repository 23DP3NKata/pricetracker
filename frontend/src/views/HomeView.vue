<template>
  <div class="home-view">
    <!-- hero -->
    <section class="hero">
      <div class="hero-content">
        <div class="badge">
          <v-icon size="14">mdi-chart-line-variant</v-icon>Track. Save. Repeat.
        </div>
        
        <h1 class="title">Never Miss a <span class="gradient">Price Drop</span> Again</h1>
        
        <p class="subtitle">Track prices across thousands of stores. Get instant alerts when products go on sale.</p>
        
        <div class="actions">
          <v-btn color="primary" size="large" rounded="xl" prepend-icon="mdi-plus">Start Tracking Free</v-btn>
          <v-btn variant="outlined" size="large" rounded="xl" append-icon="mdi-play">See Demo</v-btn>
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

    <!-- second section -->
    <section class="features">
      <div class="section-header">
        <div class="badge secondary">Features</div>
        <h2>Everything You Need</h2>
      </div>

      <div class="grid">
        <div class="card">
          <v-icon color="primary" size="32">mdi-bell-ring</v-icon>
          <h3>Instant Alerts</h3>
          <p>Get notified when prices drop</p>
        </div>
        <div class="card">
          <v-icon color="success" size="32">mdi-chart-timeline-variant</v-icon>
          <h3>Price History</h3>
          <p>View detailed trends</p>
        </div>
        <div class="card">
          <v-icon color="warning" size="32">mdi-lightning-bolt</v-icon>
          <h3>Smart Tracking</h3>
          <p>AI predicts best time to buy</p>
        </div>
        <div class="card">
          <v-icon color="info" size="32">mdi-store</v-icon>
          <h3>Multi-Store</h3>
          <p>Track across 2 stores</p>
        </div>
        <div class="card">
          <v-icon color="error" size="32">mdi-shield-check</v-icon>
          <h3>Privacy First</h3>
          <p>Your data is encrypted</p>
        </div>
        <div class="card">
          <v-icon color="secondary" size="32">mdi-devices</v-icon>
          <h3>Cross-Platform</h3>
          <p>Access from any device</p>
        </div>
      </div>
    </section>

    <!-- third section -->
    <section class="steps">
      <div class="section-header">
        <div class="badge secondary">How It Works</div>
        <h2>Start Saving in 3 Steps</h2>
      </div>

      <div class="steps-list">
        <div class="step">
          <div class="step-num">1</div>
          <div>
            <h3>Add Product URL</h3>
            <p>Paste the link of any product</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div>
            <h3>Set Target Price</h3>
            <p>Tell us your ideal price</p>
          </div>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div>
            <h3>Get Notified</h3>
            <p>Receive alerts on price drops</p>
          </div>
        </div>
      </div>
    </section>

    <!-- last section -->
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

</script>

<style scoped>
.home-view { width: 100%; overflow-x: hidden; }

/* hero */
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

/* background */
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

/* features */
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

/* steps */
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

/* ready to start */
.cta {
  padding: 5rem 1.5rem;
  text-align: center;
  /* background: linear-gradient(135deg, rgb(var(--v-theme-primary)), rgb(var(--v-theme-secondary)));
  color: white; */
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

/* animations */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes float {
  0%, 100% { transform: translate(0, 0); }
  50% { transform: translate(20px, -20px); }
}

/* responsive */
@media (max-width: 600px) {
  .actions { flex-direction: column; width: 100%; }
  .actions .v-btn { width: 100%; }
  .step { flex-direction: column; text-align: center; }
}
</style>