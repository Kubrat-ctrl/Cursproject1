import { useState } from 'react'
import { api } from '../api'
import { useFetch, Status, Pager } from '../components/ui'
import { d, money } from '../format'

export default function MyMemberships() {
  const [page, setPage] = useState(1)
  const list = useFetch(() => api.get('/memberships', { page }), [page])
  return (
    <>
      <h1>Мои абонементы</h1>
      <Status loading={list.loading} error={list.error} />
      {list.data && (
        <table>
          <thead><tr><th>Тип</th><th>Действует</th><th>Остаток посещений</th><th>Оплачено</th><th>Статус</th></tr></thead>
          <tbody>
            {list.data.data.map((m) => (
              <tr key={m.id}>
                <td>{m.membership_type?.name}</td>
                <td>{d(m.starts_on)} — {d(m.ends_on)}</td>
                <td>{m.visits_left ?? 'безлимит'}</td>
                <td>{money(m.price_paid)}</td>
                <td>{m.status}</td>
              </tr>
            ))}
            {list.data.data.length === 0 && <tr><td colSpan="5" className="muted">Абонементов пока нет</td></tr>}
          </tbody>
        </table>
      )}
      <Pager meta={list.data?.meta} onPage={setPage} />
    </>
  )
}
