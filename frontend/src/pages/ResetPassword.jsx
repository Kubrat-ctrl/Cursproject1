import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { api } from '../api'
import { FormErrors } from '../components/ui'

// Страница открывается по ссылке из письма: /reset-password?token=...&email=...
export default function ResetPassword() {
  const [params] = useSearchParams()
  const token = params.get('token') || ''
  const email = params.get('email') || ''
  const [form, setForm] = useState({ password: '', password_confirmation: '' })
  const [error, setError] = useState(null)
  const [done, setDone] = useState(false)
  const [busy, setBusy] = useState(false)

  if (!token || !email) {
    return (
      <div className="card narrow">
        <h1>Новый пароль</h1>
        <div className="alert error">Ссылка неполная. Откройте ссылку из письма целиком или запросите новое письмо.</div>
        <Link className="btn" to="/forgot-password">Запросить письмо</Link>
      </div>
    )
  }

  const submit = async (e) => {
    e.preventDefault()
    setError(null)
    setBusy(true)
    try {
      await api.post('/auth/reset-password', { token, email, ...form })
      setDone(true)
    } catch (err) {
      setError(err)
    } finally {
      setBusy(false)
    }
  }

  if (done) {
    return (
      <div className="card narrow">
        <h1>Пароль изменён</h1>
        <div className="alert ok">Теперь можно войти с новым паролем.</div>
        <Link className="btn primary" to="/login">Войти</Link>
      </div>
    )
  }

  return (
    <form className="card narrow" onSubmit={submit}>
      <h1>Новый пароль</h1>
      <FormErrors error={error} />
      <p className="muted">Учётная запись: <b>{email}</b></p>
      <label>Новый пароль
        <input type="password" value={form.password} minLength={8} autoFocus
               onChange={(e) => setForm({ ...form, password: e.target.value })} required />
      </label>
      <label>Повтор пароля
        <input type="password" value={form.password_confirmation} minLength={8}
               onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })} required />
      </label>
      <button className="btn primary" disabled={busy}>{busy ? 'Сохранение…' : 'Сохранить пароль'}</button>
    </form>
  )
}
