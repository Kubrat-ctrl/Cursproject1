import { useState } from 'react'
import { Field, serverErrs } from './ui'

const pad = (n) => String(n).padStart(2, '0')
// ISO-строка с сервера -> значение для <input type="datetime-local"> в часовом поясе браузера
const toLocalInput = (iso) => {
  if (!iso) return ''
  const d = new Date(iso)
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

// Форма создания и редактирования занятия расписания.
// workouts и trainers — данные для выпадающих списков, загруженные из API.
export default function ScheduleForm({ workouts, trainers, initial, submitLabel, onSubmit, onCancel }) {
  const endIso = initial?.ends_at ||
    (initial?.starts_at && initial?.workout?.duration_minutes
      ? new Date(new Date(initial.starts_at).getTime() + initial.workout.duration_minutes * 60000).toISOString()
      : '')
  const [f, setF] = useState({
    workout_id: initial?.workout?.id ?? '',
    trainer_id: initial?.trainer?.id ?? '',
    starts_at: toLocalInput(initial?.starts_at),
    ends_at: toLocalInput(endIso),
    room: initial?.room ?? '',
    capacity: initial?.capacity ?? '',
  })
  const [errs, setErrs] = useState({})
  const [msg, setMsg] = useState(null)
  const set = (k) => (e) => setF({ ...f, [k]: e.target.value })

  const submit = async (e) => {
    e.preventDefault()
    setMsg(null)
    // Предварительная проверка на клиенте
    const local = {}
    if (!f.workout_id) local.workout_id = 'Выберите вид занятия'
    if (!f.trainer_id) local.trainer_id = 'Выберите тренера'
    if (!f.starts_at) local.starts_at = 'Укажите время начала'
    if (!f.ends_at) local.ends_at = 'Укажите время окончания'
    if (f.starts_at && f.ends_at && new Date(f.ends_at) <= new Date(f.starts_at)) local.ends_at = 'Окончание должно быть позже начала'
    if (!f.room.trim()) local.room = 'Укажите зал'
    if (!f.capacity || Number(f.capacity) < 1) local.capacity = 'Число мест должно быть не меньше 1'
    setErrs(local)
    if (Object.keys(local).length) return

    try {
      await onSubmit({
        workout_id: Number(f.workout_id),
        trainer_id: Number(f.trainer_id),
        starts_at: new Date(f.starts_at).toISOString(),
        ends_at: new Date(f.ends_at).toISOString(),
        room: f.room.trim(),
        capacity: Number(f.capacity),
      })
    } catch (err) {
      setErrs(serverErrs(err)) // ошибки 422 выводятся рядом с полями
      setMsg(err.message)      // 409 (пересечение у тренера) и прочие — общим сообщением
    }
  }

  return (
    <form className="grid" onSubmit={submit} noValidate>
      {msg && <div className="alert error">{msg}</div>}
      <Field label="Вид занятия" error={errs.workout_id}>
        <select value={f.workout_id} onChange={set('workout_id')}>
          <option value="">—</option>
          {workouts?.map((w) => <option key={w.id} value={w.id}>{w.name}</option>)}
        </select>
      </Field>
      <Field label="Тренер" error={errs.trainer_id}>
        <select value={f.trainer_id} onChange={set('trainer_id')}>
          <option value="">—</option>
          {trainers?.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
        </select>
      </Field>
      <Field label="Начало" error={errs.starts_at}><input type="datetime-local" value={f.starts_at} onChange={set('starts_at')} /></Field>
      <Field label="Окончание" error={errs.ends_at}><input type="datetime-local" value={f.ends_at} onChange={set('ends_at')} /></Field>
      <Field label="Зал" error={errs.room}><input value={f.room} onChange={set('room')} /></Field>
      <Field label="Мест" error={errs.capacity}><input type="number" min="1" value={f.capacity} onChange={set('capacity')} /></Field>
      <div className="actions">
        <button className="btn primary">{submitLabel}</button>
        {onCancel && <button type="button" className="btn" onClick={onCancel}>Отмена</button>}
      </div>
    </form>
  )
}
