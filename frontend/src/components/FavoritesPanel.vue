<script setup lang="ts">
import type { Favorite } from '../types';

defineProps<{
    favorites: Favorite[]
    formatCoords: (lat: number, lon: number) => string
    formatDate: (value: string) => string
}>()

const emit = defineEmits<{
    (event: 'load-favorite', favorite: Favorite): void
    (event: 'remove-favorite', id: number): void
}>()

const handleLoad = (favorite: Favorite) => {
    emit('load-favorite', favorite)
}

const handleRemove = (id: number) => {
    emit('remove-favorite', id)
}
</script>

<template>
    <section class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                <h2 class="h4 mb-0">
                    <span class="star-label">
                        <svg class="star-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path
                                d="M12 3.5l2.96 6.01 6.64.96-4.8 4.68 1.13 6.61L12 18.9l-5.93 3.12 1.13-6.61-4.8-4.68 6.64-.96L12 3.5z" />
                        </svg>
                        Favorites
                    </span>
                </h2>
                <span class="badge text-bg-secondary" v-if="favorites.length">{{ favorites.length }}</span>
            </div>
            <div class="row g-3" v-if="favorites.length">
                <div class="col-12 col-md-6 col-lg-4" v-for="favorite in favorites" :key="favorite.id">
                    <article class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex flex-column gap-2">
                            <h3 class="h5 mb-0">{{ favorite.label }}</h3>
                            <p class="small mb-0">{{ formatCoords(favorite.latitude, favorite.longitude)
                                }}</p>
                            <p class="small mb-0">Saved {{ formatDate(favorite.createdAt) }}</p>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <button class="btn btn-primary btn-sm" type="button" @click="handleLoad(favorite)">
                                    <span class="button-label">
                                        <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true"
                                            focusable="false">
                                            <path
                                                d="M11 3h2v10.17l3.59-3.58L18 11l-6 6-6-6 1.41-1.41L11 13.17V3zm-7 16h16v2H4z" />
                                        </svg>
                                        Load
                                    </span>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" type="button"
                                    @click="handleRemove(favorite.id)">
                                    <span class="button-label">
                                        <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true"
                                            focusable="false">
                                            <path
                                                d="M9 3h6l1 1h4v2H4V4h4l1-1zm-2 5h10l-1 12H8L7 8zm3 2v8h2v-8h-2zm4 0v8h2v-8h-2z" />
                                        </svg>
                                        Remove
                                    </span>
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
            <p v-else class="mb-0">No favorites yet. Save one from a search.</p>
        </div>
    </section>
</template>
