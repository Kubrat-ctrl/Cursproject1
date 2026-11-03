import { useState } from 'react'
import { api } from '../api'
import { useAuth } from '../auth'
import { useFetch, Status, Pager, Flash, FormErrors } from '../components/ui'
import { dt, SCHEDULE_STATUS } from '../format'

const emptyForm = { workout_id: '', trainer_id: '', starts_at: '', ends_at: '', room: '', capacity: '' }

export default function Schedule() {
  const { user } = useAuth()
  const [filters, setFilters] = useState({ search: '', date_from: '', date_to: '', sort: 'starts_at', order: 'asc' })
  const [applied, setApplied] = useState(filters)
  const [page, setPage] = useState(1)
  const [flash, setFlash] = useState(null)
  const [formErr, setFormErr] = useState(null)
  const [form, setForm] = useState(emptyForm)
  const [showForm, setShowForm] = useState(false)

  const list = useFetch(() => api.get('/schedule-items', { ...applied, page, per_page: 10 }), [applied, page])
  const workouts = useFetch(() => (user?.role === 'admin' ? api.get('/workouts', { per_page: 50 }) : Promise.resolve(null)), [user?.role])
  const trainers = useFetch(() => (user?.role === 'admin' ? api.get('/users', { role: 'trainer', per_page: 50 }) : Promise.resolve(null)), [user?.role])

  const apply = (e) => { e.preventDefault(); setPage(1); setApplied(filters) }

  const book = async (id) => {
    setFlash(null)
    try {
      await api.post(`/schedule-items/${id}/bookings`)
      setFlash({ type: 'ok', text: 'Вы записаны на занятие' })
      list.reload()
    } catch (err) {
      setFlash({ type: 'error', text: err.message })
    }
  }

  const cancelItem = async (id) => {
    if (!confirm('Отменить занятие?')) return
    setFlash(null)
    try {
      await api.post(`/schedule-items/${id}/cancel`)
      setFlash({ type: 'ok', text: 'Занятие отменено' })
      list.reload()
    } catch (err) {
      setFlash({ type: 'error', text: err.message })
    }
  }

  const create = async (e) => {
    e.preventDefault()
    setFormErr(null)
    try {
      await api.post('/schedule-items', { ...form, capacity: Number(form.capacity) })
      setForm(emptyForm)
      setShowForm(false)
      setFlash({ type: 'ok', text: 'Занятие добавлено в расписание' })
      list.reload()
    } catch (err) {
      setFormErr(err)
    }
  }

  const items = list.data?.data ?? []

  return (
    <>
      <h1>Расписание занятий</h1>
      <Flash flash={flash} />

      <form className="filters" onSubmit={apply}>
        <input placeholder="Поиск: занятие или тренер" value={filters.search} onChange={(e) => setFilters({ ...filters, search: e.target.value })} />
        <label>с <input type="date" value={filters.date_from} onChange={(e) => setFilters({ ...filters, date_from: e.target.value })} /></label>
        <label>по <input type="date" value={filters.date_to} onChange={(e) => setFilters({ ...filters, date_to: e.target.value })} /></label>
        <select value={filters.sort} onChange={(e) => setFilters({ ...filters, sort: e.target.value })}>
          <option value="starts_at">По времени</option>
          <option value="capacity">По числу мест</option>
          <option value="room">По залу</option>
        </select>
        <select value={filters.order} onChange={(e) => setFilters({ ...filters, order: e.target.value })}>
          <option value="asc">↑</option>
          <option value="desc">↓</option>
        </select>
        <button className="btn primary">Применить</button>
      </form>

      {user?.role === 'admin' && (
        <div className="card">
          <button className="btn" onClick={() => setShowForm(!showForm)}>{showForm ? 'Скрыть форму' : '+ Добавить занятие'}</button>
          {showForm && (
            <form className="grid" onSubmit={create}>
              <FormErrors error={formErr} />
              <label>Вид занятия
                <select value={form.workout_id} onChange={(e) => setForm({ ...form, workout_id: e.target.value })} required>
                  <option value="">—</option>
                  {workouts.data?.data.map((w) => <option key={w.id} value={w.id}>{w.name}</option>)}
                </select>
              </label>
              <label>Тренер
                <select value={form.trainer_id} onChange={(e) => setForm({ ...form, trainer_id: e.target.value })} required>
                  <option value="">—</option>
                  {trainers.data?.data.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
                </select>
              </label>
              <label>Начало<input type="datetime-local" value={form.starts_at} onChange={(e) => setForm({ ...form, starts_at: e.target.value })} required /></label>
              <label>Конец<input type="datetime-local" value={form.ends_at} onChange={(e) => setForm({ ...form, ends_at: e.target.value })} required /></label>
              <label>Зал<input value={form.room} onChange={(e) => setForm({ ...form, room: e.target.value })} required /></label>
              <label>Мест<input type="number" min="1" value={form.capacity} onChange={(e) => setForm({ ...form, capacity: e.target.value })} required /></label>
              <button className="btn primary">Создать</button>
            </form>
          )}
        </div>
      )}

      <Status loading={list.loading} error={list.error} />
      {!list.loading && !list.error && (
        <>
          <table>
            <thead>
              <tr><th>Время</th><th>Занятие</th><th>Тренер</th><th>Зал</th><th>Места</th><th>Статус</th><th></th></tr>
            </thead>
            <tbody>
              {items.map((s) => {
                const free = s.capacity - (s.bookings_count ?? 0)
                const active = s.status === 'scheduled'
                return (
                  <tr key={s.id} className={active ? '' : 'dim'}>
                    <td>{dt(s.starts_at)}</td>
                    <td>{s.workout?.name}</td>
                    <td>{s.trainer?.name}</td>
                    <td>{s.room}</td>
                    <td>{free} из {s.capacity}</td>
                    <td>{SCHEDULE_STATUS[s.status] ?? s.status}</td>
                    <td className="actions">
                      {user?.role === 'client' && active && (
                        <button className="btn small primary" disabled={free <= 0} onClick={() => book(s.id)}>
                          {free <= 0 ? 'Мест нет' : 'Записаться'}
                        </button>
                      )}
                      {user?.role === 'admin' && active && (
                        <button className="btn small danger" onClick={() => cancelItem(s.id)}>Отменить</button>
                      )}
                    </td>
                  </tr>
                )
              })}
              {items.length === 0 && <tr><td colSpan="7" className="muted">Занятий не найдено</td></tr>}
            </tbody>
          </table>
          <Pager meta={list.data?.meta} onPage={setPage} />
        </>
      )}
      {!user && <p className="muted">Чтобы записаться на занятие, войдите как клиент.</p>}
    </>
  )
}
