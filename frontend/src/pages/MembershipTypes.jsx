import { useState } from 'react'
import { api } from '../api'
import { useAuth } from '../auth'
import { useFetch, Status, Pager, Flash, FormErrors } from '../components/ui'
import { money } from '../format'

const empty = { name: '', price: '', duration_days: 30, visits_limit: '', is_active: true }

export default function MembershipTypes() {
  const { user } = useAuth()
  const isAdmin = user?.role === 'admin'
  const [page, setPage] = useState(1)
  const [form, setForm] = useState(empty)
  const [editId, setEditId] = useState(null)
  const [err, setErr] = useState(null)
  const [flash, setFlash] = useState(null)

  const list = useFetch(() => api.get('/membership-types', { page, ...(isAdmin ? {} : { is_active: 1 }) }), [page, isAdmin])

  const buy = async (t) => {
    setFlash(null)
    try {
      await api.post('/memberships', { membership_type_id: t.id })
      setFlash({ type: 'ok', text: `Абонемент «${t.name}» куплен` })
    } catch (e) { setFlash({ type: 'error', text: e.message }) }
  }

  const save = async (e) => {
    e.preventDefault()
    setErr(null)
    const body = {
      ...form,
      price: Number(form.price),
      duration_days: Number(form.duration_days),
      visits_limit: form.visits_limit === '' ? null : Number(form.visits_limit),
    }
    try {
      if (editId) await api.patch(`/membership-types/${editId}`, body)
      else await api.post('/membership-types', body)
      setForm(empty); setEditId(null)
      setFlash({ type: 'ok', text: 'Сохранено' })
      list.reload()
    } catch (e2) { setErr(e2) }
  }

  const edit = (t) => {
    setEditId(t.id)
    setForm({ name: t.name, price: t.price, duration_days: t.duration_days, visits_limit: t.visits_limit ?? '', is_active: !!t.is_active })
    window.scrollTo(0, 0)
  }

  const remove = async (t) => {
    if (!confirm(`Удалить «${t.name}»?`)) return
    try {
      await api.del(`/membership-types/${t.id}`)
      setFlash({ type: 'ok', text: 'Удалено' })
      list.reload()
    } catch (e2) { setFlash({ type: 'error', text: e2.message }) }
  }

  return (
    <>
      <h1>Абонементы</h1>
      <Flash flash={flash} />
      {isAdmin && (
        <form className="card grid" onSubmit={save}>
          <h3>{editId ? 'Редактирование типа' : 'Новый тип абонемента'}</h3>
          <FormErrors error={err} />
          <label>Название<input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required /></label>
          <label>Цена<input type="number" step="0.01" value={form.price} onChange={(e) => setForm({ ...form, price: e.target.value })} required /></label>
          <label>Срок, дней<input type="number" min="1" value={form.duration_days} onChange={(e) => setForm({ ...form, duration_days: e.target.value })} required /></label>
          <label>Лимит посещений (пусто — безлимит)<input type="number" min="1" value={form.visits_limit} onChange={(e) => setForm({ ...form, visits_limit: e.target.value })} /></label>
          <label className="check"><input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} /> Активен</label>
          <div>
            <button className="btn primary">{editId ? 'Сохранить' : 'Добавить'}</button>{' '}
            {editId && <button type="button" className="btn" onClick={() => { setEditId(null); setForm(empty) }}>Отмена</button>}
          </div>
        </form>
      )}

      <Status loading={list.loading} error={list.error} />
      <div className="cards">
        {list.data?.data.map((t) => (
          <div className={`card ${t.is_active ? '' : 'dim'}`} key={t.id}>
            <h3>{t.name}</h3>
            <p className="price">{money(t.price)}</p>
            <p className="muted">{t.duration_days} дн. · {t.visits_limit ? `${t.visits_limit} посещений` : 'безлимит'}{t.is_active ? '' : ' · отключён'}</p>
            <div className="actions">
              {user?.role === 'client' && t.is_active && <button className="btn small primary" onClick={() => buy(t)}>Купить</button>}
              {isAdmin && (
                <>
                  <button className="btn small" onClick={() => edit(t)}>Изменить</button>
                  <button className="btn small danger" onClick={() => remove(t)}>Удалить</button>
                </>
              )}
            </div>
          </div>
        ))}
      </div>
      <Pager meta={list.data?.meta} onPage={setPage} />
      {!user && <p className="muted">Чтобы купить абонемент, войдите как клиент.</p>}
    </>
  )
}
