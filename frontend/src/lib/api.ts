import type { Favorite, WeatherResponse } from '../types'

const API_BASE = (import.meta.env.VITE_API_BASE as string | undefined) ?? '/api'

type JsonValue = Record<string, unknown>

async function request<T>(path: string, options?: RequestInit): Promise<T> {
    const response = await fetch(`${API_BASE}${path}`, {
        headers: {
            'Content-Type': 'application/json',
            ...(options?.headers ?? {}),
        },
        ...options,
    })

    if (!response.ok) {
        const text = await response.text()
        throw new Error(text || `Request failed with ${response.status}`)
    }

    if (response.status === 204) {
        return undefined as T
    }

    return (await response.json()) as T
}

export async function fetchWeatherByCity(city: string): Promise<WeatherResponse> {
    const params = new URLSearchParams({ city })
    return request<WeatherResponse>(`/weather?${params.toString()}`)
}

export async function fetchWeatherByCoords(latitude: number, longitude: number): Promise<WeatherResponse> {
    const params = new URLSearchParams({
        lat: latitude.toString(),
        lon: longitude.toString(),
    })
    return request<WeatherResponse>(`/weather?${params.toString()}`)
}

export async function listFavorites(): Promise<Favorite[]> {
    return request<Favorite[]>('/favorites')
}

export async function saveFavorite(payload: JsonValue): Promise<Favorite> {
    return request<Favorite>('/favorites', {
        method: 'POST',
        body: JSON.stringify(payload),
    })
}

export async function deleteFavorite(id: number): Promise<void> {
    await request<void>(`/favorites/${id}`, { method: 'DELETE' })
}
