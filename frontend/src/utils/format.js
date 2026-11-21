// Laravel devuelve fechas en el formato año,  mes, día. Por ejemplo, 2026-12-01T05:00:00.000000Z (6 decimales);
// Se recortan a 3 para que todos los navegadores las lean bien.
function toDate(value) {
  return new Date(String(value).replace(/(\.\d{3})\d+/, '$1'))
}

export function formatDate(value) {
  return toDate(value).toLocaleDateString('es', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

export function formatDateTime(value) {
  return toDate(value).toLocaleString('es', { dateStyle: 'short', timeStyle: 'short' })
}

// Las fechas de las estadísticas llegan como 'YYYY-MM-DD' (sin incluir la hora). Se formatean
// cortando el texto, porque al convertirlas con new Date() se desplazaría un día
// en zonas horarias al oeste de UTC.

export function formatDay(value) {
  const [y, m, d] = String(value).split('-')
  return `${d}/${m}/${y}`
}

export function formatDayShort(value) {
  const [, m, d] = String(value).split('-')
  return `${d}/${m}`
}

// Convierte una fecha ISO (UTC) al formato que espera <input type="datetime-local">,
// en la hora local del navegador: 2026-12-01T10:30
export function toDatetimeLocal(value) {
  const d = toDate(value)
  const pad = (n) => String(n).padStart(2, '0')

  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

export function hostOf(url) {
  try {
    return new URL(url).hostname.replace(/^www\./, '')
  } catch {
    return url
  }
}
