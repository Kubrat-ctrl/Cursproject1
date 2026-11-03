import { useState } from 'react'
import { api } from '../api'
import { useAuth } from '../auth'
import { useFetch, Status, Pager, Flash, FormErrors } from '../components/ui'
import { LEVELS } from '../format'

const empty = { name: '', description: '', duration_minutes: 60, level: 'beginner', is_active: true }

export default function Workouts() {
  const { user } = useAuth()
  const isAdmin = user?.role === 'admin'
  const [level, setLevel] = useState('')
  const [page, setPage] = useState(1)
  const [form, setForm] = useState(empty)
  const [editId, setEditId] = useState(null)
  const [err, setErr] = useState(null)
  const [flash, setFlash] = useState(null)

  const list = useFetch(() => api.get('/workouts', { level, page, ...(isAdmin ? {} : { is_active: 1 }) }), [level, page, isAdmin])

  const save = async (e) => {
    e.preventDefault()
    setErr(null)
    const body = { ...form, duration_minutes: Number(form.duration_minutes) }
    try {
      if (editId) await api.patch(`/workouts/${editId}`, body)
      else await api.post('/workouts', body)
      setForm(empty); setEditId(null)
      setFlash({ type: 'ok', text: 'Сохранено' })
      list.reload()
    } catch (e2) { setErr(e2) }
  }

  const edit = (w) => {
    setEditId(w.id)
    setForm({ name: w.name, description: w.description ?? '', duration_minutes: w.duration_minutes, level: w.level, is_active: !!w.is_active })
    window.scrollTo(0, 0)
  }

  const remove = async (w) => {
    if (!confirm(`Удалить «${w.name}»?`)) return
    try {
      await api.del(`/workouts/${w.id}`)
      setFlash({ type: 'ok', text: 'Удалено' })
      list.reload()
    } catch (e2) { setFlash({ type: 'error', text: e2.message }) }
  }

  return (
    <>
      <h1>Виды занятий</h1>
      <Flash flash={flash} />
      {isAdmin && (
        <form className="card grid" onSubmit={save}>
          <h3>{editId ? 'Редактирование' : 'Новое занятие'}</h3>
          <FormErrors error={err} />
          <label>Название<input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required /></label>
          <label>Длительность, мин<input type="number" min="1" value={form.duration_minutes} onChange={(e) => setForm({ ...form, duration_minutes: e.target.value })} required /></label>
          <label>Уровень
            <select value={form.level} onChange={(e) => setForm({ ...form, level: e.target.value })}>
              {Object.entries(LEVELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
            </select>
          </label>
          <label>Описание<textarea value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} /></label>
          <label className="check"><input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} /> Активно</label>
          <div>
            <button className="btn primary">{editId ? 'Сохранить' : 'Добавить'}</button>{' '}
            {editId && <button type="button" className="btn" onClick={() => { setEditId(null); setForm(empty) }}>Отмена</button>}
          </div>
        </form>
      )}

      <div className="filters">
        <select value={level} onChange={(e) => { setPage(1); setLevel(e.target.value) }}>
          <option value="">Любой уровень</option>
          {Object.entries(LEVELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
        </select>
      </div>

      <Status loading={list.loading} error={list.error} />
      <div className="cards">
        {list.data?.data.map((w) => (
          <div className={`card ${w.is_active ? '' : 'dim'}`} key={w.id}>
            {w.image_url && <img src={w.image_url} alt={w.name} className="thumb" />}
            <h3>{w.name}</h3>
            <p>{w.description}</p>
            <p className="muted">{w.duration_minutes} мин · {LEVELS[w.level] ?? w.level}{w.is_active ? '' : ' · отключено'}</p>
            {isAdmin && (
              <div className="actions">
                <button className="btn small" onClick={() => edit(w)}>Изменить</button>
                <button className="btn small danger" onClick={() => remove(w)}>Удалить</button>
              </div>
            )}
          </div>
        ))}
      </div>
      <Pager meta={list.data?.meta} onPage={setPage} />
    </>
  )
}
