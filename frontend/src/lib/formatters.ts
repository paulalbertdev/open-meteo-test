export const formatCoords = (lat: number, lon: number): string => {
    return `${lat.toFixed(3)}, ${lon.toFixed(3)}`
}

export const formatDate = (value: string): string => {
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) {
        return value
    }
    return date.toLocaleString('fr-FR')
}

export const parseNumber = (value: string): number | null => {
    if (!value) {
        return null
    }

    const parsed = Number.parseFloat(value)
    return Number.isFinite(parsed) ? parsed : null
}
