<template>
  <div class="about">
    <div class="header">
      <h1>About <span class="red">PriceTracker</span></h1>
      <p>We help people save money by tracking product prices</p>
    </div>

    <v-container>
      <div class="stats">
        <div class="stat">
          <h2>...</h2>
          <p>Products</p>
        </div>
        <div class="stat">
          <h2>...</h2>
          <p>Saved</p>
        </div>
        <div class="stat">
          <h2>...</h2>
          <p>Users</p>
        </div>
      </div>

      <div class="form-section">
        <h2>Get in Touch</h2>
        <p>We'll reply within 24 hours</p>

        <v-form @submit.prevent="send">
          <v-text-field
            v-model="name"
            label="Your Name"
            variant="outlined"
            rounded="lg"
          />

          <v-text-field
            v-model="email"
            label="Email"
            type="email"
            variant="outlined"
            rounded="lg"
          />

          <v-textarea
            v-model="message"
            label="Message"
            variant="outlined"
            rounded="lg"
            rows="4"
          />

          <v-btn
            type="submit"
            color="primary"
            size="large"
            rounded="xl"
            block
            :loading="loading"
          >
            Send Message
          </v-btn>
        </v-form>

        <v-alert
          v-if="success"
          type="success"
          variant="tonal"
          rounded="lg"
          class="mt-4">
          Thank you! We received your message!
        </v-alert>
      </div>
    </v-container>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const name = ref('')
const email = ref('')
const message = ref('')
const loading = ref(false)
const success = ref(false)

function send() {
  loading.value = true

  setTimeout(() => {
    loading.value = false
    success.value = true

    name.value = ''
    email.value = ''
    message.value = ''

    setTimeout(() => {
      success.value = false
    }, 5000)
  }, 1500)
}
</script>

<style scoped>
/* header */
.header {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.1), rgba(var(--v-theme-secondary), 0.1));
  padding: 4rem 2rem 3rem;
  text-align: center;
}

.header h1 {
  font-size: 3rem;
  font-weight: bold;
  margin-bottom: 1rem;
  color: rgb(var(--v-theme-on-surface));
}

.red {
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)), rgb(var(--v-theme-secondary)));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.header p {
  font-size: 1.2rem;
  color: rgba(var(--v-theme-on-surface), 0.7);
}

/* stats */
.stats {
  display: flex;
  justify-content: center;
  gap: 3rem;
  margin: 3rem 0;
  flex-wrap: wrap;
}

.stat {
  text-align: center;
}

.stat h2 {
  font-size: 2.5rem;
  font-weight: bold;
  color: rgb(var(--v-theme-primary));
}

.stat p {
  color: rgba(var(--v-theme-on-surface), 0.6);
  margin-top: 0.5rem;
}

/* forms */
.form-section {
  max-width: 600px;
  margin: 3rem auto;
  padding: 2rem;
  background: rgba(var(--v-theme-surface), 1);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.1);
  border-radius: 20px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.form-section h2 {
  font-size: 2rem;
  font-weight: bold;
  text-align: center;
  margin-bottom: 0.5rem;
  color: rgb(var(--v-theme-on-surface));
}

.form-section > p {
  text-align: center;
  color: rgba(var(--v-theme-on-surface), 0.6);
  margin-bottom: 2rem;
}

.v-text-field,
.v-textarea {
  margin-bottom: 1rem;
}

/* responsive */
@media (max-width: 600px) {
  .header h1 {
    font-size: 2rem;
  }

  .stats {
    gap: 2rem;
  }

  .form-section {
    padding: 1.5rem;
  }
}
</style>