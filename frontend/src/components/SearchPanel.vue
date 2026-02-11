<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    city: string
    latitude: string
    longitude: string
    isLoading: boolean
    errorMessage: string
    statusMessage: string
}>()

const emit = defineEmits<{
    (event: 'update:city', value: string): void
    (event: 'update:latitude', value: string): void
    (event: 'update:longitude', value: string): void
    (event: 'submit'): void
}>()

const cityValue = computed({
    get: () => props.city,
    set: (value: string) => emit('update:city', value),
})

const latitudeValue = computed({
    get: () => props.latitude,
    set: (value: string) => emit('update:latitude', value),
})

const longitudeValue = computed({
    get: () => props.longitude,
    set: (value: string) => emit('update:longitude', value),
})

const handleSubmit = () => {
    emit('submit')
}
</script>

<template>
    <section class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                <h2 class="h4 mb-0">Search</h2>
                <span class="badge text-bg-warning" v-if="isLoading">Loading</span>
            </div>
            <form class="row g-3" @submit.prevent="handleSubmit">
                <div class="col-12">
                    <label class="form-label">City</label>
                    <input class="form-control" v-model="cityValue" placeholder="Paris, Lyon, Marseille" />
                </div>
                <div class="col-12">
                    <span class="divider text-uppercase">or</span>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Latitude</label>
                    <input class="form-control" v-model="latitudeValue" placeholder="48.856" />
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Longitude</label>
                    <input class="form-control" v-model="longitudeValue" placeholder="2.352" />
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">
                        <span class="button-label">
                            <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path
                                    d="M10.5 3a7.5 7.5 0 0 1 5.93 12.1l3.73 3.74-1.41 1.41-3.74-3.73A7.5 7.5 0 1 1 10.5 3zm0 2a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11z" />
                            </svg>
                            Search
                        </span>
                    </button>
                </div>
            </form>
            <div class="alert alert-danger mt-3 mb-0" v-if="errorMessage">{{ errorMessage }}</div>
            <div class="alert alert-success mt-3 mb-0" v-if="statusMessage">{{ statusMessage }}</div>
        </div>
    </section>
</template>
