import { useState } from 'react'
import { api } from '../api'
import { useFetch, Status, Flash, FormErrors } from '../components/ui'
import { money } from '../format'

const METHODS = [
  { id: 'card', label: 'Банковская карта' },
  { id: 'kaspi', label: 'Kaspi QR' },
  { id: 'apple_pay', label: 'Apple Pay' },
  { id: 'google_pay', label: 'Google Pay' },
]
const TYPES = { topup: 'Пополнение', purchase: 'Покупка', refund: 'Возврат' }
const empty = { amount: '', method: 'card', card_number: '', card_expiry: '', card_cvc: '' }

export default function Wallet() {
  const [form, setForm] = useState(empty)
  const [err, setErr] = useState(null)
  const [flash, setFlash] = useState(null)
  const wallet = useFetch(() => api.get('/wallet'), [])
  const set = (k, v) => setForm({ ...form, [k]: v })

  const submit = async (e) => {
    e.preventDefault()
    setErr(null); setFlash(null)
    const body = { amount: Number(form.amount), method: form.method }
    if (form.method === 'card') {
      body.card_number = form.card_number
      body.card_expiry = form.card_expiry
      body.card_cvc = form.card_cvc
    }
    try {
      await api.post('/wallet/top-up', body)
      setFlash({ type: 'ok', text: 'Баланс пополнен' })
      setForm(empty)
      wallet.reload()
      window.dispatchEvent(new Event('wallet:changed'))
    } catch (e2) { setErr(e2) }
  }

  return (
    <>
      <h1>Баланс</h1>
      <Flash flash={flash} />
      <Status loading={wallet.loading} error={wallet.error} />
      {wallet.data && <p className="price">{money(wallet.data.balance)}</p>}

      <form className="card grid" onSubmit={submit}>
        <h3>Пополнить баланс</h3>
        <p className="muted">Тестовая оплата: реальные деньги не списываются. Карта с успехом: 4242 4242 4242 4242, с отказом: 4000 0000 0000 0002.</p>
        <FormErrors error={err} />
        <label>Сумма, ₸
          <input type="number" min="100" max="500000" value={form.amount} onChange={(e) => set('amount', e.target.value)} required />
        </label>
        <div>
          {[1000, 5000, 10000, 30000].map((v) => (
            <button type="button" className="btn small" key={v} onClick={() => set('amount', v)}>{money(v)}</button>
          ))}
        </div>
        <label>Способ оплаты
          <select value={form.method} onChange={(e) => set('method', e.target.value)}>
            {METHODS.map((m) => <option key={m.id} value={m.id}>{m.label}</option>)}
          </select>
        </label>
        {form.method === 'card' && (
          <>
            <label>Номер карты<input inputMode="numeric" maxLength={23} placeholder="4242 4242 4242 4242" value={form.card_number} onChange={(e) => set('card_number', e.target.value)} required /></label>
            <label>Срок (ММ/ГГ)<input placeholder="12/30" maxLength={5} value={form.card_expiry} onChange={(e) => set('card_expiry', e.target.value)} required /></label>
            <label>CVC<input inputMode="numeric" maxLength={4} placeholder="123" value={form.card_cvc} onChange={(e) => set('card_cvc', e.target.value)} required /></label>
          </>
        )}
        <div><button className="btn primary">Пополнить</button></div>
      </form>

      <h3>История операций</h3>
      <table className="table">
        <thead><tr><th>Дата</th><th>Тип</th><th>Описание</th><th>Сумма</th><th>Остаток</th></tr></thead>
        <tbody>
          {wallet.data?.transactions.map((t) => (
            <tr key={t.id}>
              <td>{new Date(t.created_at).toLocaleString('ru-RU')}</td>
              <td>{TYPES[t.type] ?? t.type}</td>
              <td>{t.description}</td>
              <td>{Number(t.amount) > 0 ? '+' : ''}{money(t.amount)}</td>
              <td>{money(t.balance_after)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </>
  )
}