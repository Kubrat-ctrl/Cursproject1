import { useState } from 'react'
import { api } from '../api'
import { useFetch, Status, Pager, Flash } from '../components/ui'
import { dt, BOOKING_STATUS } from '../format'

export default function MyBookings() {
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [flash, setFlash] = useState(null)
  const list = useFetch(() => api.get('/bookings', { status, page }), [status, page])

  const cancel = async (b) => {
    if (!confirm('Отменить запись?')) return
    try {
      await api.del(`/bookings/${b.id}`)
      setFlash({ type: 'ok', text: 'Запись отменена' })
      list.reload()
    } catch (e) { setFlash({ type: 'error', text: e.message }) }
  }

  return (
    <>
      <h1>Мои записи</h1>
      <Flash flash={flash} />
      <div className="filters">
        <select value={status} onChange={(e) => { setPage(1); setStatus(e.target.value) }}>
          <option value="">Все</option>
          <option value="booked">Активные</option>
          <option value="cancelled">Отменённые</option>
        </select>
      </div>
      <Status loading={list.loading} error={list.error} />
      {list.data && (
        <table>
          <thead><tr><th>Занятие</th><th>Время</th><th>Тренер</th><th>Статус</th><th>Посещение</th><th></th></tr></thead>
          <tbody>
            {list.data.data.map((b) => (
              <tr key={b.id}>
                <td>{b.schedule_item?.workout?.name}</td>
                <td>{dt(b.schedule_item?.starts_at)}</td>
                <td>{b.schedule_item?.trainer?.name}</td>
                <td>{BOOKING_STATUS[b.status] ?? b.status}</td>
                <td>{b.visit ? dt(b.visit.checked_in_at) : '—'}</td>
                <td className="actions">
                  {b.status === 'booked' && !b.visit && <button className="btn small danger" onClick={() => cancel(b)}>Отменить</button>}
                </td>
              </tr>
            ))}
            {list.data.data.length === 0 && <tr><td colSpan="6" className="muted">Записей нет</td></tr>}
          </tbody>
        </table>
      )}
      <Pager meta={list.data?.meta} onPage={setPage} />
    </>
  )
}
