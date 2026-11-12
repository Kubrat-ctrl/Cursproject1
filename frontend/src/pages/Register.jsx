import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../auth'
import { FormErrors } from '../components/ui'
import GoogleButton from '../components/GoogleButton'

export default function Register() {
  const { register, loginWithGoogle } = useAuth()
  const nav = useNavigate()
  const [form, setForm] = useState({ name: '', email: '', phone: '', password: '', password_confirmation: '' })
  const [error, setError] = useState(null)
  const set = (k) => (e) => setForm({ ...form, [k]: e.target.value })

  const submit = async (e) => {
    e.preventDefault()
    setError(null)
    try {
      const body = { ...form }
      if (!body.phone) delete body.phone
      await register(body)
      nav('/')
    } catch (err) {
      setError(err)
    }
  }

  // Первый вход через Google создаёт учётную запись клиента автоматически
  const google = async (credential) => {
    setError(null)
    try {
      await loginWithGoogle(credential)
      nav('/')
    } catch (err) {
      setError(err)
    }
  }

  return (
    <form className="card narrow" onSubmit={submit}>
      <h1>Регистрация</h1>
      <FormErrors error={error} />
      <GoogleButton onCredential={google} onError={setError} />
      <div className="divider">или заполните форму</div>
      <label>Имя<input value={form.name} onChange={set('name')} required /></label>
      <label>Email<input type="email" value={form.email} onChange={set('email')} required /></label>
      <label>Телефон<input value={form.phone} onChange={set('phone')} placeholder="+77010000000" /></label>
      <label>Пароль<input type="password" value={form.password} onChange={set('password')} required /></label>
      <label>Повтор пароля<input type="password" value={form.password_confirmation} onChange={set('password_confirmation')} required /></label>
      <button className="btn primary">Зарегистрироваться</button>
    </form>
  )
}
