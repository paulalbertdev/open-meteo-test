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
  <div class="container py-4 py-lg-5 d-flex flex-column gap-4">
    <header class="row g-4 align-items-center">
      <div class="col-12 col-lg-6">
        <p class="eyebrow text-muted mb-2">Open-Meteo Gateway</p>
        <h1 class="display-5 fw-semibold mb-3">Weather Atlas</h1>
        <p class="lead text-secondary mb-0">
          Browse city forecasts or drop coordinates, then pin favorites for quick access.
        </p>
      </div>
      <div class="col-12 col-lg-6">
        <div class="hero-panel rounded-4 p-4 shadow">
          <p class="panel-label text-uppercase mb-2">Current focus</p>
          <h2 class="h3 mb-2">{{ weather?.location?.label ?? 'No search yet' }}</h2>
          <p class="text-light-emphasis mb-0" v-if="weather?.current">
            {{ weather.current.temperature_2m ?? '-' }} C · {{ weather.current.wind_speed_10m ?? '-' }} km/h
          </p>
          <p class="text-light-emphasis mb-0" v-else>Search to see live conditions.</p>
        </div>
      </div>
    </header>

    <section class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
          <h2 class="h4 mb-0">Search</h2>
          <span class="badge text-bg-warning" v-if="isLoading">Loading</span>
        </div>
        <form class="row g-3" @submit.prevent="handleSearch">
          <div class="col-12">
            <label class="form-label">City</label>
            <input class="form-control" v-model="city" placeholder="Paris, Lyon, Marseille" />
          </div>
          <div class="col-12">
            <span class="divider text-uppercase text-secondary">or</span>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Latitude</label>
            <input class="form-control" v-model="latitude" placeholder="48.856" />
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Longitude</label>
            <input class="form-control" v-model="longitude" placeholder="2.352" />
          </div>
          <div class="col-12">
            <button class="btn btn-primary" type="submit">Search</button>
          </div>
        </form>
        <div class="alert alert-danger mt-3 mb-0" v-if="errorMessage">{{ errorMessage }}</div>
        <div class="alert alert-success mt-3 mb-0" v-if="statusMessage">{{ statusMessage }}</div>
      </div>
    </section>

    <section class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
          <h2 class="h4 mb-0">Conditions</h2>
          <p class="text-secondary small mb-0" v-if="weather">{{ formattedLocation }}</p>
        </div>
        <div v-if="weather" class="row g-3">
          <div class="col-12 col-lg-5">
            <article class="card border-0 shadow-sm h-100">
              <div class="card-body d-flex flex-column gap-2">
                <h3 class="h5 mb-0">Now</h3>
                <p class="display-6 fw-semibold mb-0">{{ weather.current?.temperature_2m ?? '-' }} C</p>
                <p class="text-secondary small mb-0">Wind {{ weather.current?.wind_speed_10m ?? '-' }} km/h</p>
                <p class="text-secondary small mb-0">Timezone {{ weather.timezone ?? '-' }}</p>
                <div class="mt-2">
                  <button class="btn btn-outline-secondary" type="button" @click="saveCurrent">
                    Save favorite
                  </button>
                </div>
              </div>
            </article>
          </div>
          <div class="col-12 col-lg-7">
            <article class="card border-0 shadow-sm h-100">
              <div class="card-body">
                <h3 class="h5">Next days</h3>
                <div class="list-group list-group-flush" v-if="dailyRows.length">
                  <div v-for="row in dailyRows" :key="row.date" class="list-group-item px-0">
                    <div class="fw-semibold">{{ row.date }}</div>
                    <div>{{ row.max ?? '-' }} C / {{ row.min ?? '-' }} C</div>
                    <div class="text-secondary small">Wind {{ row.wind ?? '-' }} km/h</div>
                  </div>
                </div>
                <p v-else class="text-secondary mb-0">No forecast yet.</p>
              </div>
            </article>
          </div>
        </div>
        <p v-else class="text-secondary mb-0">Start with a search to see conditions.</p>
      </div>
    </section>

    <section class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
          <h2 class="h4 mb-0">Favorites</h2>
          <span class="badge text-bg-secondary" v-if="favorites.length">{{ favorites.length }}</span>
        </div>
        <div class="row g-3" v-if="favorites.length">
          <div class="col-12 col-md-6 col-lg-4" v-for="favorite in favorites" :key="favorite.id">
            <article class="card h-100 border-0 shadow-sm">
              <div class="card-body d-flex flex-column gap-2">
                <h3 class="h5 mb-0">{{ favorite.label }}</h3>
                <p class="text-secondary small mb-0">{{ formatCoords(favorite.latitude, favorite.longitude) }}</p>
                <p class="text-secondary small mb-0">Saved {{ formatDate(favorite.createdAt) }}</p>
                <div class="d-flex flex-wrap gap-2 mt-2">
                  <button class="btn btn-primary btn-sm" type="button" @click="loadFavorite(favorite)">
                    Load
                  </button>
                  <button class="btn btn-outline-secondary btn-sm" type="button" @click="removeFavorite(favorite.id)">
                    Remove
                  </button>
                </div>
              </div>
            </article>
          </div>
        </div>
        <p v-else class="text-secondary mb-0">No favorites yet. Save one from a search.</p>
      </div>
    </section>
  </div>
</template>
