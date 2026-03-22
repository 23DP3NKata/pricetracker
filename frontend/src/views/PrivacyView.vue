<template>
  <div class="legal-page">
    <v-container class="py-10 py-md-14">
      <h1 class="text-h4 font-weight-bold mb-2">{{ $t('legal.privacy.title') }}</h1>
      <p class="text-body-2 text-medium-emphasis mb-8">{{ $t('legal.lastUpdated') }}</p>

      <v-card rounded="xl" class="pa-6 pa-md-8 mb-5">
        <h2 class="text-h6 font-weight-bold mb-3">{{ $t('legal.privacy.section1Title') }}</h2>
        <p>{{ $t('legal.privacy.section1Text') }}</p>
      </v-card>

      <v-card rounded="xl" class="pa-6 pa-md-8 mb-5">
        <h2 class="text-h6 font-weight-bold mb-3">{{ $t('legal.privacy.section2Title') }}</h2>
        <p>{{ $t('legal.privacy.section2Text') }}</p>
      </v-card>

      <v-card rounded="xl" class="pa-6 pa-md-8 mb-5">
        <h2 class="text-h6 font-weight-bold mb-3">{{ $t('legal.privacy.section3Title') }}</h2>
        <p>{{ $t('legal.privacy.section3Text') }}</p>
      </v-card>

      <v-card rounded="xl" class="pa-6 pa-md-8 mb-5">
        <h2 class="text-h6 font-weight-bold mb-3">{{ $t('legal.privacy.section4Title') }}</h2>
        <p>{{ $t('legal.privacy.section4Text') }}</p>
      </v-card>

      <v-card rounded="xl" class="pa-6 pa-md-8 mb-5">
        <h2 class="text-h6 font-weight-bold mb-3">{{ $t('legal.privacy.section5Title') }}</h2>
        <p>{{ $t('legal.privacy.section5Text') }}</p>
      </v-card>

      <v-card rounded="xl" class="pa-6 pa-md-8">
        <h2 class="text-h6 font-weight-bold mb-3">{{ $t('legal.privacy.section6Title') }}</h2>
        <p>{{ $t('legal.privacy.section6Text') }}</p>
      </v-card>
    </v-container>
  </div>
</template>

<style scoped>
.legal-page {
  background: rgba(var(--v-theme-background), 1);
}

p {
  color: rgba(var(--v-theme-on-surface), 0.78);
  line-height: 1.7;
  margin: 0;
}
</style>
