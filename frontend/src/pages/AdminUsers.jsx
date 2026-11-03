import { useState } from 'react'
import { api } from '../api'
import { useAuth } from '../auth'
import { useFetch, Status, Pager, Flash } from '../components/ui'
import { ROLES } from '../format'

export default function AdminUsers() {
  const { user: me } = useAuth()
  const [role, setRole] = useState('')
  const [page, setPage] = useState(1)
  const [flash, setFlash] = useState(null)
  const list = useFetch(() => api.get('/users', { role, page }), [role, page])

  const patch = async (u, body) => {
    setFlash(null)
    try {
      await api.patch(`/users/${u.id}`, body)
      setFlash({ type: 'ok', text: 'Сохранено' })
      list.reload()
    } catch (e) {
      setFlash({ type: 'error', text: e.message })
      list.reload()
    }
  }

  return (
    <>
      <h1>Пользователи</h1>
      <Flash flash={flash} />
      <div className="filters">
        <select value={role} onChange={(e) => { setPage(1); setRole(e.target.value) }}>
          <option value="">Все роли</option>
          {Object.entries(ROLES).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
        </select>
      </div>
      <Status loading={list.loading} error={list.error} />
      {list.data && (
        <table>
          <thead><tr><th>Имя</th><th>Email</th><th>Роль</th><th>Заблокирован</th></tr></thead>
          <tbody>
            {list.data.data.map((u) => (
              <tr key={u.id}>
                <td>{u.name}</td>
                <td>{u.email}</td>
                <td>
                  <select value={u.role} disabled={u.id === me.id} onChange={(e) => patch(u, { role: e.target.value })}>
                    {Object.entries(ROLES).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                  </select>
                </td>
                <td><input type="checkbox" checked={!!u.blocked} disabled={u.id === me.id} onChange={(e) => patch(u, { blocked: e.target.checked })} /></td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
      <Pager meta={list.data?.meta} onPage={setPage} />
    </>
  )
}
