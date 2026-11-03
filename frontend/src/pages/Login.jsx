import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../auth'
import { FormErrors } from '../components/ui'

export default function Login() {
  const { login } = useAuth()
  const nav = useNavigate()
  const [form, setForm] = useState({ email: '', password: '' })
  const [error, setError] = useState(null)

  const submit = async (e) => {
    e.preventDefault()
    setError(null)
    try {
      await login(form.email, form.password)
      nav('/')
    } catch (err) {
      setError(err)
    }
  }

  return (
    <form className="card narrow" onSubmit={submit}>
      <h1>Вход</h1>
      <FormErrors error={error} />
      <label>Email
        <input type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} required />
      </label>
      <label>Пароль
        <input type="password" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} required />
      </label>
      <button className="btn primary">Войти</button>
      <p className="muted">Тест: admin@fitclub.test, sokolov@fitclub.test (тренер), petrov@fitclub.test (клиент). Пароль задан в сидере.</p>
    </form>
  )
}
