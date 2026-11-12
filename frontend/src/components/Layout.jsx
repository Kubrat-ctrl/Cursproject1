import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import { useAuth } from '../auth'
import { ROLES } from '../format'
import BalanceBadge from './BalanceBadge'

export default function Layout() {
  const { user, logout } = useAuth()
  const nav = useNavigate()

  return (
    <>
      <header className="topbar">
        <div className="wrap bar">
          <span className="brand">Фитнес-клуб</span>
          <nav>
            <NavLink to="/" end>Расписание</NavLink>
            <NavLink to="/workouts">Занятия</NavLink>
            <NavLink to="/membership-types">Абонементы</NavLink>
            {user?.role === 'client' && (
              <>
                <NavLink to="/my/memberships">Мои абонементы</NavLink>
                <NavLink to="/my/bookings">Мои записи</NavLink>
              </>
            )}
            {user?.role === 'trainer' && <NavLink to="/trainer">Мои занятия</NavLink>}
            {user?.role === 'admin' && (
              <>
                <NavLink to="/admin/users">Пользователи</NavLink>
                <NavLink to="/admin/reports">Отчёты</NavLink>
              </>
            )}
          </nav>
          <div className="who">
            {user ? (
              <>
                {user.role === 'client' && <BalanceBadge />}
                <span>{user.name} · {ROLES[user.role]}</span>
                <button className="btn small" onClick={async () => { await logout(); nav('/login') }}>Выйти</button>
              </>
            ) : (
              <>
                <NavLink to="/login">Вход</NavLink>
                <NavLink to="/register">Регистрация</NavLink>
              </>
            )}
          </div>
        </div>
      </header>
      <main className="wrap">
        <Outlet />
      </main>
    </>
  )
}