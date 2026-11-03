import { useState } from 'react'
import { api } from '../api'
import { useFetch, Status, Pager, Flash } from '../components/ui'
import { dt } from '../format'

function Bookings({ item }) {
  const [flash, setFlash] = useState(null)
  const list = useFetch(() => api.get(`/schedule-items/${item.id}/bookings`), [item.id])

  const mark = async (b) => {
    setFlash(null)
    try {
      await api.post(`/bookings/${b.id}/visit`)
      setFlash({ type: 'ok', text: `Отмечен: ${b.user?.name}` })
      list.reload()
    } catch (e) { setFlash({ type: 'error', text: e.message }) }
  }

  return (
    <div className="card">
      <h3>{item.workout?.name} · {dt(item.starts_at)} · {item.room}</h3>
      <Flash flash={flash} />
      <Status loading={list.loading} error={list.error} />
      {list.data && (
        <table>
          <thead><tr><th>Клиент</th><th>Статус</th><th>Пришёл</th><th></th></tr></thead>
          <tbody>
            {(list.data.data ?? []).map((b) => (
              <tr key={b.id}>
                <td>{b.user?.name}</td>
                <td>{b.status}</td>
                <td>{b.visit ? dt(b.visit.checked_in_at) : '—'}</td>
                <td className="actions">
                  {b.status === 'booked' && !b.visit && <button className="btn small primary" onClick={() => mark(b)}>Отметить</button>}
                </td>
              </tr>
            ))}
            {(list.data.data ?? []).length === 0 && <tr><td colSpan="4" className="muted">Никто не записан</td></tr>}
          </tbody>
        </table>
      )}
    </div>
  )
}

export default function TrainerSchedule() {
  const [date, setDate] = useState('')
  const [page, setPage] = useState(1)
  const [open, setOpen] = useState(null)
  const list = useFetch(() => api.get('/trainer/schedule-items', { date, page }), [date, page])

  return (
    <>
      <h1>Моё расписание</h1>
      <div className="filters">
        <label>Дата <input type="date" value={date} onChange={(e) => { setPage(1); setDate(e.target.value) }} /></label>
      </div>
      <Status loading={list.loading} error={list.error} />
      {list.data && (
        <table>
          <thead><tr><th>Время</th><th>Занятие</th><th>Зал</th><th>Записано</th><th></th></tr></thead>
          <tbody>
            {list.data.data.map((s) => (
              <tr key={s.id}>
                <td>{dt(s.starts_at)}</td>
                <td>{s.workout?.name}</td>
                <td>{s.room}</td>
                <td>{s.bookings_count ?? '—'} / {s.capacity}</td>
                <td className="actions"><button className="btn small" onClick={() => setOpen(open?.id === s.id ? null : s)}>{open?.id === s.id ? 'Скрыть' : 'Записавшиеся'}</button></td>
              </tr>
            ))}
            {list.data.data.length === 0 && <tr><td colSpan="5" className="muted">Занятий нет</td></tr>}
          </tbody>
        </table>
      )}
      <Pager meta={list.data?.meta} onPage={setPage} />
      {open && <Bookings item={open} key={open.id} />}
    </>
  )
}
