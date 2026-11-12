import { useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api'
import { useAuth } from '../auth'
import { useFetch, Status, Pager, Flash } from '../components/ui'
import ScheduleForm from '../components/ScheduleForm'
import { dt, SCHEDULE_STATUS } from '../format'

export default function Schedule() {
  const { user } = useAuth()
  const isAdmin = user?.role === 'admin'
  const [filters, setFilters] = useState({ search: '', workout_id: '', date_from: '', date_to: '', sort: 'starts_at', order: 'asc' })
  const [applied, setApplied] = useState(filters)
  const [page, setPage] = useState(1)
  const [flash, setFlash] = useState(null)
  const [showForm, setShowForm] = useState(false)

  // Параметры фильтрации, поиска, сортировки и страницы уходят в строку запроса к API
  const list = useFetch(() => api.get('/schedule-items', { ...applied, page, per_page: 10 }), [applied, page])
  const workouts = useFetch(() => api.get('/workouts', { is_active: 1, per_page: 50 }), [])
  const trainers = useFetch(() => (isAdmin ? api.get('/users', { role: 'trainer', per_page: 50 }) : Promise.resolve(null)), [isAdmin])

  const apply = (e) => { e.preventDefault(); setPage(1); setApplied(filters) }
  const reset = () => {
    const empty = { search: '', workout_id: '', date_from: '', date_to: '', sort: 'starts_at', order: 'asc' }
    setFilters(empty); setApplied(empty); setPage(1)
  }

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

  const create = async (body) => {
    await api.post('/schedule-items', body)
    setShowForm(false)
    setFlash({ type: 'ok', text: 'Занятие добавлено в расписание' })
    list.reload()
  }

  const items = list.data?.data ?? []

  return (
    <>
      <h1>Расписание занятий</h1>
      <Flash flash={flash} />

      <form className="filters" onSubmit={apply}>
        <input placeholder="Поиск: занятие или тренер" value={filters.search} onChange={(e) => setFilters({ ...filters, search: e.target.value })} />
        <select value={filters.workout_id} onChange={(e) => setFilters({ ...filters, workout_id: e.target.value })}>
          <option value="">Все занятия</option>
          {workouts.data?.data.map((w) => <option key={w.id} value={w.id}>{w.name}</option>)}
        </select>
        <label>с <input type="date" value={filters.date_from} onChange={(e) => setFilters({ ...filters, date_from: e.target.value })} /></label>
        <label>по <input type="date" value={filters.date_to} onChange={(e) => setFilters({ ...filters, date_to: e.target.value })} /></label>
        <select value={filters.sort} onChange={(e) => setFilters({ ...filters, sort: e.target.value })}>
          <option value="starts_at">По времени</option>
          <option value="capacity">По числу мест</option>
          <option value="room">По залу</option>
        </select>
        <select value={filters.order} onChange={(e) => setFilters({ ...filters, order: e.target.value })}>
          <option value="asc">По возрастанию</option>
          <option value="desc">По убыванию</option>
        </select>
        <button className="btn primary">Применить</button>
        <button type="button" className="btn" onClick={reset}>Сбросить</button>
      </form>

      {isAdmin && (
        <div className="card">
          <button className="btn" onClick={() => setShowForm(!showForm)}>{showForm ? 'Скрыть форму' : '+ Добавить занятие'}</button>
          {showForm && (
            <ScheduleForm
              workouts={workouts.data?.data}
              trainers={trainers.data?.data}
              submitLabel="Создать"
              onSubmit={create}
            />
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
                    <td><Link to={`/schedule/${s.id}`}>{s.workout?.name}</Link></td>
                    <td>{s.trainer?.name}</td>
                    <td>{s.room}</td>
                    <td>{free} из {s.capacity}</td>
                    <td>{SCHEDULE_STATUS[s.status] ?? s.status}</td>
                    <td className="actions">
                      <Link className="btn small" to={`/schedule/${s.id}`}>Подробнее</Link>
                      {user?.role === 'client' && active && (
                        <button className="btn small primary" disabled={free <= 0} onClick={() => book(s.id)}>
                          {free <= 0 ? 'Мест нет' : 'Записаться'}
                        </button>
                      )}
                      {isAdmin && active && (
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
