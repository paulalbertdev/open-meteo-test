<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import {
  deleteFavorite,
  fetchWeatherByCity,
  fetchWeatherByCoords,
  listFavorites,
  saveFavorite,
} from './lib/api'
import type { Favorite, WeatherResponse } from './types'

const city = ref('')
const latitude = ref('')
const longitude = ref('')
const weather = ref<WeatherResponse | null>(null)
const favorites = ref<Favorite[]>([])
const errorMessage = ref('')
const statusMessage = ref('')
const isLoading = ref(false)

const dailyRows = computed(() => {
  if (!weather.value?.daily?.time) {
    return []
  }

  return weather.value.daily.time.map((day, index) => ({
    date: day,
    max: weather.value?.daily?.temperature_2m_max?.[index] ?? null,
    min: weather.value?.daily?.temperature_2m_min?.[index] ?? null,
    wind: weather.value?.daily?.wind_speed_10m_max?.[index] ?? null,
  }))
})

const formattedLocation = computed(() => {
  if (!weather.value?.location) {
    return ''
  }

  return `${weather.value.location.label} (${formatCoords(
    weather.value.location.latitude,
    weather.value.location.longitude,
  )})`
})

const loadFavorites = async () => {
  favorites.value = await listFavorites()
}

const handleSearch = async () => {
  errorMessage.value = ''
  statusMessage.value = ''

  const trimmedCity = city.value.trim()
  const latValue = parseNumber(latitude.value)
  const lonValue = parseNumber(longitude.value)

  if (!trimmedCity && (latValue === null || lonValue === null)) {
    errorMessage.value = 'Enter a city or both coordinates.'
    return
  }

  isLoading.value = true

  try {
    if (trimmedCity) {
      weather.value = await fetchWeatherByCity(trimmedCity)
    } else if (latValue !== null && lonValue !== null) {
      weather.value = await fetchWeatherByCoords(latValue, lonValue)
    }

    statusMessage.value = 'Weather loaded.'
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Request failed.'
  } finally {
    isLoading.value = false
  }
}

const saveCurrent = async () => {
  if (!weather.value) {
    return
  }

  errorMessage.value = ''
  statusMessage.value = ''

  try {
    await saveFavorite({
      label: weather.value.location.label,
      latitude: weather.value.location.latitude,
      longitude: weather.value.location.longitude,
    })
    await loadFavorites()
    statusMessage.value = 'Favorite saved.'
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Save failed.'
  }
}

const removeFavorite = async (id: number) => {
  errorMessage.value = ''
  statusMessage.value = ''

  try {
    await deleteFavorite(id)
    await loadFavorites()
    statusMessage.value = 'Favorite removed.'
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Delete failed.'
  }
}

const loadFavorite = async (favorite: Favorite) => {
  city.value = ''
  latitude.value = favorite.latitude.toString()
  longitude.value = favorite.longitude.toString()
  await handleSearch()
}

const parseNumber = (value: string): number | null => {
  if (!value) {
    return null
  }

  const parsed = Number.parseFloat(value)
  return Number.isFinite(parsed) ? parsed : null
}

const formatCoords = (lat: number, lon: number): string => {
  return `${lat.toFixed(3)}, ${lon.toFixed(3)}`
}

const formatDate = (value: string): string => {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return value
  }
  return date.toLocaleString('fr-FR')
}

onMounted(() => {
  void loadFavorites()
})
</script>

<template>
  <div class="page">
    <header class="hero">
      <div class="hero-text">
        <p class="eyebrow">Open-Meteo Gateway</p>
        <h1>Weather Atlas</h1>
        <p class="subtitle">
          Browse city forecasts or drop coordinates, then pin favorites for quick access.
        </p>
      </div>
      <div class="hero-panel">
        <p class="panel-label">Current focus</p>
        <h2>{{ weather?.location?.label ?? 'No search yet' }}</h2>
        <p class="meta" v-if="weather?.current">
          {{ weather.current.temperature_2m ?? '-' }} C · {{ weather.current.wind_speed_10m ?? '-' }} km/h
        </p>
        <p class="meta" v-else>Search to see live conditions.</p>
      </div>
    </header>

    <section class="panel search-panel">
      <div class="panel-header">
        <h2>Search</h2>
        <span class="badge" v-if="isLoading">Loading</span>
      </div>
      <form class="search-form" @submit.prevent="handleSearch">
        <label class="field">
          <span>City</span>
          <input v-model="city" placeholder="Paris, Lyon, Marseille" />
        </label>
        <div class="divider">or</div>
        <div class="coords">
          <label class="field">
            <span>Latitude</span>
            <input v-model="latitude" placeholder="48.856" />
          </label>
          <label class="field">
            <span>Longitude</span>
            <input v-model="longitude" placeholder="2.352" />
          </label>
        </div>
        <button class="primary" type="submit">Search</button>
      </form>
      <div class="notice error" v-if="errorMessage">{{ errorMessage }}</div>
      <div class="notice success" v-if="statusMessage">{{ statusMessage }}</div>
    </section>

    <section class="panel results-panel">
      <div class="panel-header">
        <h2>Conditions</h2>
        <p class="meta" v-if="weather">{{ formattedLocation }}</p>
      </div>
      <div v-if="weather" class="results-grid">
        <article class="card current-card">
          <h3>Now</h3>
          <p class="value">{{ weather.current?.temperature_2m ?? '-' }} C</p>
          <p class="meta">Wind {{ weather.current?.wind_speed_10m ?? '-' }} km/h</p>
          <p class="meta">Timezone {{ weather.timezone ?? '-' }}</p>
          <button class="ghost" type="button" @click="saveCurrent">Save favorite</button>
        </article>
        <article class="card forecast-card">
          <h3>Next days</h3>
          <div class="forecast-grid" v-if="dailyRows.length">
            <div v-for="row in dailyRows" :key="row.date" class="forecast-row">
              <span class="date">{{ row.date }}</span>
              <span>{{ row.max ?? '-' }} C / {{ row.min ?? '-' }} C</span>
              <span class="meta">Wind {{ row.wind ?? '-' }} km/h</span>
            </div>
          </div>
          <p v-else class="empty">No forecast yet.</p>
        </article>
      </div>
      <p v-else class="empty">Start with a search to see conditions.</p>
    </section>

    <section class="panel favorites-panel">
      <div class="panel-header">
        <h2>Favorites</h2>
        <span class="badge" v-if="favorites.length">{{ favorites.length }}</span>
      </div>
      <div class="favorites-grid" v-if="favorites.length">
        <article v-for="favorite in favorites" :key="favorite.id" class="card favorite-card">
          <h3>{{ favorite.label }}</h3>
          <p class="meta">{{ formatCoords(favorite.latitude, favorite.longitude) }}</p>
          <p class="meta">Saved {{ formatDate(favorite.createdAt) }}</p>
          <div class="actions">
            <button class="primary" type="button" @click="loadFavorite(favorite)">Load</button>
            <button class="ghost" type="button" @click="removeFavorite(favorite.id)">Remove</button>
          </div>
        </article>
      </div>
      <p v-else class="empty">No favorites yet. Save one from a search.</p>
    </section>
  </div>
</template>
