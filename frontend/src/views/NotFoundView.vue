<template>
  <v-container class="py-10 text-center">
    <v-card rounded="xl" class="pa-8 mx-auto" max-width="640">
      <h1 class="text-h4 font-weight-bold mb-2">{{ $t('notFound.code') }}</h1>
      <p class="text-body-1 text-medium-emphasis mb-6">
        {{ $t('notFound.message') }}
      </p>
      <div class="d-flex justify-center ga-3 flex-wrap">
        <v-btn color="primary" rounded="xl" to="/">{{ $t('notFound.goHome') }}</v-btn>
        <v-btn variant="tonal" rounded="xl" to="/dashboard">{{ $t('notFound.goDashboard') }}</v-btn>
      </div>
    </v-card>
  </v-container>
</template>
