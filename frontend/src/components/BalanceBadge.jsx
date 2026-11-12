import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api'
import { money } from '../format'

export default function BalanceBadge() {
  const [balance, setBalance] = useState(null)

  useEffect(() => {
    const load = () => api.get('/wallet').then((r) => setBalance(r.balance)).catch(() => {})
    load()
    window.addEventListener('wallet:changed', load)
    return () => window.removeEventListener('wallet:changed', load)
  }, [])

  if (balance === null) return null
  return <Link to="/my/wallet" className="btn small">Баланс: {money(balance)} · Пополнить</Link>
}