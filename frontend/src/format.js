export const LEVELS = { beginner: 'Начальный', intermediate: 'Средний', advanced: 'Продвинутый' }
export const ROLES = { client: 'Клиент', trainer: 'Тренер', admin: 'Администратор' }
export const SCHEDULE_STATUS = { scheduled: 'Запланировано', cancelled: 'Отменено', completed: 'Проведено' }
export const BOOKING_STATUS = { booked: 'Записан', cancelled: 'Отменена', visited: 'Посетил' }

export const dt = (s) =>
  s ? new Date(s).toLocaleString('ru-RU', { dateStyle: 'medium', timeStyle: 'short' }) : '—'
export const d = (s) => (s ? new Date(s).toLocaleDateString('ru-RU') : '—')
export const money = (v) => `${Number(v).toLocaleString('ru-RU')} ₸`
