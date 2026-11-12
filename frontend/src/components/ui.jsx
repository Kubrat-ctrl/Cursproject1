import { useEffect, useState, useCallback } from 'react'

// Загрузка данных с состояниями loading / error
export function useFetch(fn, deps) {
  const [state, setState] = useState({ data: null, loading: true, error: null })
  const run = useCallback(() => {
    setState((s) => ({ ...s, loading: true, error: null }))
    fn()
      .then((data) => setState({ data, loading: false, error: null }))
      .catch((error) => setState({ data: null, loading: false, error }))
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, deps)
  useEffect(run, [run])
  return { ...state, reload: run }
}

export function Status({ loading, error }) {
  if (loading) return <p className="muted">Загрузка…</p>
  if (error) return <div className="alert error">{error.message}</div>
  return null
}

// Сообщение об ошибке + ошибки по полям (422)
export function FormErrors({ error }) {
  if (!error) return null
  return (
    <div className="alert error">
      {error.message}
      {error.errors && (
        <ul>
          {Object.entries(error.errors).flatMap(([f, msgs]) => msgs.map((m, i) => <li key={f + i}>{m}</li>))}
        </ul>
      )}
    </div>
  )
}

export function Pager({ meta, onPage }) {
  if (!meta || meta.last_page <= 1) return null
  return (
    <div className="pager">
      <button className="btn small" disabled={meta.current_page <= 1} onClick={() => onPage(meta.current_page - 1)}>← Назад</button>
      <span>Страница {meta.current_page} из {meta.last_page} (всего {meta.total})</span>
      <button className="btn small" disabled={meta.current_page >= meta.last_page} onClick={() => onPage(meta.current_page + 1)}>Вперёд →</button>
    </div>
  )
}

export function Flash({ flash }) {
  if (!flash) return null
  return <div className={`alert ${flash.type}`}>{flash.text}</div>
}

// Преобразует ответ 422 { errors: { field: ['сообщение'] } } в { field: 'сообщение' }
export function serverErrs(error) {
  const out = {}
  Object.entries(error?.errors || {}).forEach(([k, v]) => { out[k] = v[0] })
  return out
}

// Поле формы: подпись, элемент ввода и сообщение об ошибке рядом с ним
export function Field({ label, error, children }) {
  return (
    <label className={error ? 'has-error' : ''}>
      {label}
      {children}
      {error && <span className="field-error">{error}</span>}
    </label>
  )
}
