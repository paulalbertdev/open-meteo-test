export interface WeatherResponse {
    location: {
        label: string
        latitude: number
        longitude: number
    }
    current?: {
        temperature_2m?: number
        wind_speed_10m?: number
    }
    daily?: {
        time?: string[]
        temperature_2m_max?: number[]
        temperature_2m_min?: number[]
        wind_speed_10m_max?: number[]
        weather_code?: number[]
    }
    timezone?: string
}

export interface Favorite {
    id: number
    label: string
    latitude: number
    longitude: number
    createdAt: string
}
