<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import FavoritesPanel from './components/FavoritesPanel.vue'
import SearchPanel from './components/SearchPanel.vue'
import {
  deleteFavorite,
  fetchWeatherByCity,
  fetchWeatherByCoords,
  listFavorites,
  saveFavorite,
} from './lib/api'
import { formatCoords, formatDate, parseNumber } from './lib/formatters'
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
    if (latValue !== null && lonValue !== null) {
      weather.value = await fetchWeatherByCoords(latValue, lonValue)
      if (trimmedCity) {
        weather.value.location.label = trimmedCity
      }
    } else if (trimmedCity) {
      weather.value = await fetchWeatherByCity(trimmedCity)
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
  city.value = favorite.label
  latitude.value = favorite.latitude.toString()
  longitude.value = favorite.longitude.toString()
  await handleSearch()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => {
  void loadFavorites()
})
</script>

<template>
  <div class="container py-4 py-lg-5 d-flex flex-column gap-4">
    <header class="row g-4 align-items-center">
      <div class="col-12 col-lg-6">
        <p class="eyebrow text-muted mb-2">Personal Weather Board</p>
        <h1 class="display-5 fw-semibold mb-3">Four Fata Weather</h1>
        <p class="lead mb-0">
          Get city forecasts by name, or drop coordinates
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

    <SearchPanel v-model:city="city" v-model:latitude="latitude" v-model:longitude="longitude" :is-loading="isLoading"
      :error-message="errorMessage" :status-message="statusMessage" @submit="handleSearch" />

    <section class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
          <h2 class="h4 mb-0">Conditions</h2>
          <p class="small mb-0" v-if="weather">{{ formattedLocation }}</p>
        </div>
        <div v-if="weather" class="row g-3">
          <div class="col-12 col-lg-5">
            <article class="card border-0 shadow-sm h-100">
              <div class="card-body d-flex flex-column gap-2">
                <h3 class="h5 mb-0">Now</h3>
                <p class="display-6 fw-semibold mb-0">{{ weather.current?.temperature_2m ?? '-' }} C</p>
                <p class="small mb-0">Wind {{ weather.current?.wind_speed_10m ?? '-' }} km/h</p>
                <p class="small mb-0">Timezone {{ weather.timezone ?? '-' }}</p>
                <div class="mt-2">
                  <button class="btn btn-primary" type="button" @click="saveCurrent">
                    <span class="star-label">
                      <svg class="star-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path
                          d="M12 3.5l2.96 6.01 6.64.96-4.8 4.68 1.13 6.61L12 18.9l-5.93 3.12 1.13-6.61-4.8-4.68 6.64-.96L12 3.5z" />
                      </svg>
                      Save favorite
                    </span>
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
                    <div class="small">Wind {{ row.wind ?? '-' }} km/h</div>
                  </div>
                </div>
                <p v-else class="mb-0">No forecast yet.</p>
              </div>
            </article>
          </div>
        </div>
        <p v-else class="mb-0">Start with a search to see conditions.</p>
      </div>
    </section>

    <FavoritesPanel :favorites="favorites" :format-coords="formatCoords" :format-date="formatDate"
      @load-favorite="loadFavorite" @remove-favorite="removeFavorite" />
  </div>
</template>
