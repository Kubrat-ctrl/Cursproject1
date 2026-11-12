import { useState } from 'react'
import { api } from '../api'
import { useAuth } from '../auth'
import { useFetch, Status, Pager, Flash, Field, serverErrs } from '../components/ui'
import { LEVELS } from '../format'

const empty = { name: '', description: '', duration_minutes: 60, level: 'beginner', is_active: true }

export default function Workouts() {
  const { user } = useAuth()
  const isAdmin = user?.role === 'admin'
  const [level, setLevel] = useState('')
  const [page, setPage] = useState(1)
  const [form, setForm] = useState(empty)
  const [image, setImage] = useState(null)
  const [editId, setEditId] = useState(null)
  const [errs, setErrs] = useState({})
  const [msg, setMsg] = useState(null)
  const [flash, setFlash] = useState(null)
  const [fileKey, setFileKey] = useState(0)

  const list = useFetch(() => api.get('/workouts', { level, page, ...(isAdmin ? {} : { is_active: 1 }) }), [level, page, isAdmin])

  const reset = () => { setForm(empty); setImage(null); setEditId(null); setErrs({}); setMsg(null); setFileKey((k) => k + 1) }

  const save = async (e) => {
    e.preventDefault()
    setMsg(null)
    // Предварительная проверка на клиенте
    const local = {}
    if (!form.name.trim()) local.name = 'Укажите название'
    if (!form.duration_minutes || Number(form.duration_minutes) < 1) local.duration_minutes = 'Длительность должна быть не меньше 1 минуты'
    if (image && !image.type.startsWith('image/')) local.image = 'Выберите файл-изображение'
    if (image && image.size > 2 * 1024 * 1024) local.image = 'Размер файла не должен превышать 2 МБ'
    setErrs(local)
    if (Object.keys(local).length) return

    // Файл отправляется как multipart/form-data: FormData, без Content-Type: application/json
    const fd = new FormData()
    fd.append('name', form.name.trim())
    fd.append('description', form.description)
    fd.append('duration_minutes', form.duration_minutes)
    fd.append('level', form.level)
    fd.append('is_active', form.is_active ? '1' : '0')
    if (image) fd.append('image', image)

    try {
      if (editId) {
        fd.append('_method', 'PATCH') // multipart-запросы PHP разбирает только для POST, поэтому метод передаётся полем
        await api.post(`/workouts/${editId}`, fd)
      } else {
        await api.post('/workouts', fd)
      }
      reset()
      setFlash({ type: 'ok', text: 'Сохранено' })
      list.reload()
    } catch (err) {
      setErrs(serverErrs(err))
      setMsg(err.message)
    }
  }

  const edit = (w) => {
    setEditId(w.id)
    setImage(null)
    setFileKey((k) => k + 1)
    setErrs({}); setMsg(null)
    setForm({ name: w.name, description: w.description ?? '', duration_minutes: w.duration_minutes, level: w.level, is_active: !!w.is_active })
    window.scrollTo(0, 0)
  }

  const remove = async (w) => {
    if (!confirm(`Удалить «${w.name}»?`)) return
    setFlash(null)
    try {
      await api.del(`/workouts/${w.id}`)
      setFlash({ type: 'ok', text: 'Удалено' })
      list.reload()
    } catch (e2) { setFlash({ type: 'error', text: e2.message }) } // 409: занятие есть в расписании
  }

  return (
    <>
      <h1>Виды занятий</h1>
      <Flash flash={flash} />
      {isAdmin && (
        <form className="card grid" onSubmit={save} noValidate>
          <h3>{editId ? 'Редактирование' : 'Новое занятие'}</h3>
          {msg && <div className="alert error">{msg}</div>}
          <Field label="Название" error={errs.name}><input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} /></Field>
          <Field label="Длительность, мин" error={errs.duration_minutes}><input type="number" min="1" value={form.duration_minutes} onChange={(e) => setForm({ ...form, duration_minutes: e.target.value })} /></Field>
          <Field label="Уровень" error={errs.level}>
            <select value={form.level} onChange={(e) => setForm({ ...form, level: e.target.value })}>
              {Object.entries(LEVELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
            </select>
          </Field>
          <Field label="Описание" error={errs.description}><textarea value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} /></Field>
          <Field label="Изображение (до 2 МБ)" error={errs.image}>
            <input key={fileKey} type="file" accept="image/*" onChange={(e) => setImage(e.target.files[0] ?? null)} />
          </Field>
          {image && <img className="thumb preview" src={URL.createObjectURL(image)} alt="Предпросмотр" />}
          <label className="check"><input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} /> Активно</label>
          <div className="actions">
            <button className="btn primary">{editId ? 'Сохранить' : 'Добавить'}</button>
            {editId && <button type="button" className="btn" onClick={reset}>Отмена</button>}
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
