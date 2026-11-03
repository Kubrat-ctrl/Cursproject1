import { useState } from 'react'
import { api } from '../api'
import { useFetch, Status, FormErrors } from '../components/ui'

const iso = (dt) => dt.toISOString().slice(0, 10)
const today = new Date()
const monthAgo = new Date(Date.now() - 30 * 864e5)

function Json({ title, state }) {
  return (
    <div className="card">
      <h3>{title}</h3>
      <Status loading={state.loading} error={state.error} />
      {state.data && <pre>{JSON.stringify(state.data.data ?? state.data, null, 2)}</pre>}
    </div>
  )
}

export default function AdminReports() {
  const [range, setRange] = useState({ from: iso(monthAgo), to: iso(today) })
  const [applied, setApplied] = useState(range)

  const sales = useFetch(() => api.get('/reports/memberships-sales', applied), [applied])
  const byStatus = useFetch(() => api.get('/reports/bookings-by-status'), [])
  const popular = useFetch(() => api.get('/reports/popular-workouts', { ...applied, limit: 5 }), [applied])

  return (
    <>
      <h1>Отчёты</h1>
      <form className="filters" onSubmit={(e) => { e.preventDefault(); setApplied(range) }}>
        <label>с <input type="date" value={range.from} onChange={(e) => setRange({ ...range, from: e.target.value })} /></label>
        <label>по <input type="date" value={range.to} onChange={(e) => setRange({ ...range, to: e.target.value })} /></label>
        <button className="btn primary">Показать</button>
      </form>
      <FormErrors error={sales.error?.errors ? sales.error : null} />
      <Json title="Продажи абонементов за период" state={sales} />
      <Json title="Записи по статусам" state={byStatus} />
      <Json title="Популярные занятия" state={popular} />
    </>
  )
}
