<template>
  <div class="hero-section">
    <v-container class="hero-container">
      <v-row class="align-center justify-center text-center">
        <v-col cols="12" md="8">
          <div class="hero-text">
            <h1 class="hero-title">
              Track Prices <span class="gradient-text">Easily</span>
            </h1>
            <p class="hero-subtitle">
              Automatic product price monitoring from the most popular online stores. 
              Get notifications when prices drop and save money while shopping.
            </p>
          </div>
        </v-col>
      </v-row>

      <v-row class="justify-center mb-8">
        <v-col cols="12" md="8">
          <div class="hero-buttons">
            <v-btn
              to="/products"
              color="primary"
              size="x-large"
              class="btn-primary"
            >
              Start Now
            </v-btn>
            <v-btn
              to="/about"
              variant="outlined"
              color="primary"
              size="x-large"
              class="btn-secondary"
            >
              Learn More
            </v-btn>
          </div>
        </v-col>
      </v-row>

      <v-row class="hero-stats justify-center">
        <v-col cols="auto" v-for="stat in stats" :key="stat.label">
          <div class="stat-item">
            <div class="stat-number">{{ stat.number }}</div>
            <div class="stat-label">{{ stat.label }}</div>
          </div>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const stats = ref([
  { number: '10K+', label: 'Products Tracked' },
  { number: '50K+', label: 'Active Users' },
  { number: '24/7', label: 'Monitoring' }
])
</script>

<style scoped>
.hero-section {
  min-height: 85vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(ellipse at top center, rgba(25, 118, 210, 0.08) 0%, transparent 60%);
  position: relative;
  overflow: hidden;
  padding: 6rem 0 4rem;
}

.hero-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 100%;
  max-width: 1400px;
  height: 400px;
  background: radial-gradient(circle, rgba(63, 81, 181, 0.15) 0%, transparent 70%);
  filter: blur(60px);
  z-index: 0;
}

.hero-container {
  position: relative;
  z-index: 1;
}

.hero-text {
  max-width: 900px;
  margin: 0 auto;
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 800;
  line-height: 1.05;
  margin-bottom: 1.5rem;
  letter-spacing: -0.03em;
  color: #1a1a1a;
}

.gradient-text {
  background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  display: inline-block;
}

.hero-subtitle {
  font-size: 1.25rem;
  line-height: 1.7;
  color: #666;
  margin-bottom: 2.5rem;
  max-width: 700px;
  margin-left: auto;
  margin-right: auto;
}

.hero-buttons {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.btn-primary {
  box-shadow: 0 4px 14px rgba(25, 118, 210, 0.4);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  min-width: 180px;
}

.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(25, 118, 210, 0.5), 0 0 40px rgba(63, 81, 181, 0.3);
}

.btn-secondary {
  min-width: 180px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-width: 2px;
}

.btn-secondary:hover {
  transform: translateY(-2px);
}

/* Stats Section */
.hero-stats {
  padding-top: 3rem;
  border-top: 1px solid #e5e7eb;
  margin-top: 2rem;
  gap: 2rem;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  text-align: center;
  min-width: 120px;
}

.stat-number {
  font-size: 2.5rem;
  font-weight: 800;
  background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: #999;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 500;
}

/* Responsive */
@media (max-width: 960px) {
  .hero-section {
    padding: 4rem 0 3rem;
  }

  .hero-title {
    font-size: 2.5rem;
  }

  .hero-subtitle {
    font-size: 1.125rem;
  }

  .hero-buttons {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-primary,
  .btn-secondary {
    min-width: auto;
    width: 100%;
  }

  .stat-number {
    font-size: 2rem;
  }
}

@media (max-width: 600px) {
  .hero-section {
    padding: 3rem 0 2rem;
    min-height: 65vh;
  }

  .hero-title {
    font-size: 1.875rem;
    line-height: 1.1;
  }

  .hero-subtitle {
    font-size: 0.95rem;
    line-height: 1.6;
  }

  .hero-buttons {
    gap: 0.75rem;
  }

  .btn-primary,
  .btn-secondary {
    font-size: 0.9rem;
  }

  .hero-stats {
    flex-direction: column;
    gap: 1.5rem;
    padding-top: 2rem;
  }

  .stat-number {
    font-size: 1.75rem;
  }

  .stat-label {
    font-size: 0.8rem;
  }
}
</style>
