import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { api } from '../api'
import { useAuth } from '../auth'
import { useFetch, Status, Flash } from '../components/ui'
import ScheduleForm from '../components/ScheduleForm'
import { dt, LEVELS, SCHEDULE_STATUS } from '../format'

// Карточка одного занятия со связанными данными (вид занятия, тренер, места)
export default function ScheduleDetail() {
  const { id } = useParams()
  const { user } = useAuth()
  const [flash, setFlash] = useState(null)
  const [editing, setEditing] = useState(false)

  const item = useFetch(() => api.get(`/schedule-items/${id}`), [id])
  const s = item.data?.data
  const isAdmin = user?.role === 'admin'

  // Если сервер не вложил вид занятия, догружаем его отдельным запросом
  const workoutId = s?.workout?.id ?? s?.workout_id
  const workoutLoaded = useFetch(
    () => (s && !s.workout && workoutId ? api.get(`/workouts/${workoutId}`) : Promise.resolve(null)),
    [s?.id])
  const workout = s?.workout ?? workoutLoaded.data?.data

  const workouts = useFetch(() => (isAdmin ? api.get('/workouts', { per_page: 50 }) : Promise.resolve(null)), [isAdmin])
  const trainers = useFetch(() => (isAdmin ? api.get('/users', { role: 'trainer', per_page: 50 }) : Promise.resolve(null)), [isAdmin])

  const book = async () => {
    setFlash(null)
    try {
      await api.post(`/schedule-items/${id}/bookings`)
      setFlash({ type: 'ok', text: 'Вы записаны на занятие' })
      item.reload()
    } catch (e) {
      setFlash({ type: 'error', text: e.message }) // 409 / 422: сообщение о нарушении бизнес-правила
    }
  }

  const cancelItem = async () => {
    if (!confirm('Отменить занятие?')) return
    setFlash(null)
    try {
      await api.post(`/schedule-items/${id}/cancel`)
      setFlash({ type: 'ok', text: 'Занятие отменено' })
      item.reload()
    } catch (e) {
      setFlash({ type: 'error', text: e.message })
    }
  }

  const save = async (body) => {
    await api.patch(`/schedule-items/${id}`, body)
    setEditing(false)
    setFlash({ type: 'ok', text: 'Изменения сохранены' })
    item.reload()
  }

  return (
    <>
      <p><Link to="/">← К расписанию</Link></p>
      <Status loading={item.loading} error={item.error} />
      <Flash flash={flash} />
      {s && (
        <div className="card detail">
          {workout?.image_url && <img src={workout.image_url} alt={workout.name} className="cover" />}
          <h1>{workout?.name ?? 'Занятие'}</h1>
          {workout?.description && <p>{workout.description}</p>}
          <table className="props">
            <tbody>
              <tr><th>Время</th><td>{dt(s.starts_at)}</td></tr>
              <tr><th>Тренер</th><td>{s.trainer?.name ?? '—'}</td></tr>
              <tr><th>Зал</th><td>{s.room}</td></tr>
              <tr><th>Свободных мест</th><td>{s.capacity - (s.bookings_count ?? 0)} из {s.capacity}</td></tr>
              {workout && <tr><th>Длительность</th><td>{workout.duration_minutes} мин · {LEVELS[workout.level] ?? workout.level}</td></tr>}
              <tr><th>Статус</th><td>{SCHEDULE_STATUS[s.status] ?? s.status}</td></tr>
            </tbody>
          </table>
          <div className="actions">
            {user?.role === 'client' && s.status === 'scheduled' && (
              <button className="btn primary" disabled={s.capacity - (s.bookings_count ?? 0) <= 0} onClick={book}>Записаться</button>
            )}
            {isAdmin && s.status === 'scheduled' && (
              <>
                <button className="btn" onClick={() => setEditing(!editing)}>{editing ? 'Скрыть форму' : 'Редактировать'}</button>
                <button className="btn danger" onClick={cancelItem}>Отменить занятие</button>
              </>
            )}
          </div>
          {!user && <p className="muted">Чтобы записаться, войдите как клиент.</p>}
          {editing && (
            <ScheduleForm
              key={s.id + String(s.starts_at)}
              workouts={workouts.data?.data}
              trainers={trainers.data?.data}
              initial={{ ...s, workout }}
              submitLabel="Сохранить"
              onSubmit={save}
              onCancel={() => setEditing(false)}
            />
          )}
        </div>
      )}
    </>
  )
}
