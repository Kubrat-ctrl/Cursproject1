import { Routes, Route, Navigate } from 'react-router-dom'
import { useAuth } from './auth'
import Layout from './components/Layout'
import Login from './pages/Login'
import Register from './pages/Register'
import Schedule from './pages/Schedule'
import ScheduleDetail from './pages/ScheduleDetail'
import Workouts from './pages/Workouts'
import MembershipTypes from './pages/MembershipTypes'
import MyMemberships from './pages/MyMemberships'
import MyBookings from './pages/MyBookings'
import TrainerSchedule from './pages/TrainerSchedule'
import AdminUsers from './pages/AdminUsers'
import AdminReports from './pages/AdminReports'

function Guard({ roles, children }) {
  const { user, loading } = useAuth()
  if (loading) return <p className="muted">Загрузка…</p>
  if (!user) return <Navigate to="/login" replace />
  if (roles && !roles.includes(user.role)) {
    return <div className="alert error">Недостаточно прав для просмотра этой страницы (403).</div>
  }
  return children
}

export default function App() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route path="/" element={<Schedule />} />
        <Route path="/schedule/:id" element={<ScheduleDetail />} />
        <Route path="/workouts" element={<Workouts />} />
        <Route path="/membership-types" element={<MembershipTypes />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/my/memberships" element={<Guard roles={['client']}><MyMemberships /></Guard>} />
        <Route path="/my/bookings" element={<Guard roles={['client']}><MyBookings /></Guard>} />
        <Route path="/trainer" element={<Guard roles={['trainer']}><TrainerSchedule /></Guard>} />
        <Route path="/admin/users" element={<Guard roles={['admin']}><AdminUsers /></Guard>} />
        <Route path="/admin/reports" element={<Guard roles={['admin']}><AdminReports /></Guard>} />
        <Route path="*" element={<div className="alert error">Страница не найдена (404).</div>} />
      </Route>
    </Routes>
  )
}
