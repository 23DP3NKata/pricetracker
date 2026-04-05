<template>
  <v-container class="py-8 py-md-10 market-view">
    <v-card rounded="xl" class="pa-6 pa-md-8 market-hero">
      <div class="d-flex align-center ga-3 mb-3">
        <v-icon size="34" color="primary">mdi-finance</v-icon>
        <h1 class="text-h4 text-md-h3 font-weight-bold ma-0">{{ $t('markets.title') }}</h1>
      </div>
      <p class="text-medium-emphasis mb-0">
        {{ $t('markets.subtitle') }}
      </p>
    </v-card>

    <v-card rounded="xl" class="pa-6 pa-md-8 mt-6">
      <div class="d-flex align-center ga-2 mb-3">
        <v-icon color="primary">mdi-chart-line</v-icon>
        <h2 class="text-h6 font-weight-bold ma-0">{{ $t('markets.alphaTitle') }}</h2>
      </div>

      <p class="text-medium-emphasis mb-4">{{ $t('markets.alphaDescription') }}</p>

      <v-alert type="info" variant="tonal" rounded="lg" icon="mdi-timer-sand" class="mb-0">
        {{ $t('markets.alphaStatus') }}
      </v-alert>
    </v-card>
  </v-container>
</template>

<script setup>
</script>

<style scoped>
.market-view {
  max-width: 1100px;
}

.market-hero {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  background: linear-gradient(145deg, rgba(var(--v-theme-primary), 0.08), rgba(var(--v-theme-surface), 1));
}
</style>
