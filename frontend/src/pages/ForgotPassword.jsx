import { useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api'
import { FormErrors } from '../components/ui'

export default function ForgotPassword() {
  const [email, setEmail] = useState('')
  const [error, setError] = useState(null)
  const [sent, setSent] = useState(false)
  const [busy, setBusy] = useState(false)

  const submit = async (e) => {
    e.preventDefault()
    setError(null)
    setBusy(true)
    try {
      await api.post('/auth/forgot-password', { email })
      setSent(true)
    } catch (err) {
      setError(err)
    } finally {
      setBusy(false)
    }
  }

  if (sent) {
    return (
      <div className="card narrow">
        <h1>Проверьте почту</h1>
        <div className="alert ok">
          Если адрес <b>{email}</b> зарегистрирован, мы отправили на него письмо со ссылкой для сброса пароля.
          Ссылка действует 60 минут.
        </div>
        <p className="muted">Письма нет? Проверьте папку «Спам» или повторите попытку через минуту.</p>
        <Link className="btn" to="/login">Вернуться ко входу</Link>
      </div>
    )
  }

  return (
    <form className="card narrow" onSubmit={submit}>
      <h1>Восстановление пароля</h1>
      <FormErrors error={error} />
      <p className="muted">Укажите email учётной записи: мы отправим на него ссылку для создания нового пароля.</p>
      <label>Email
        <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required autoFocus />
      </label>
      <button className="btn primary" disabled={busy}>{busy ? 'Отправка…' : 'Отправить ссылку'}</button>
      <p className="links"><Link to="/login">Вернуться ко входу</Link></p>
    </form>
  )
}
