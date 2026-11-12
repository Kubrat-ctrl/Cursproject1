import { createContext, useContext, useEffect, useState, useCallback } from 'react'
import { api, getToken, setToken, setUnauthorizedHandler } from './api'

const AuthContext = createContext(null)
export const useAuth = () => useContext(AuthContext)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(!!getToken())

  const logoutLocal = useCallback(() => {
    setToken(null)
    setUser(null)
  }, [])

  useEffect(() => {
    setUnauthorizedHandler(logoutLocal)
    if (!getToken()) return
    api.get('/profile')
      .then((r) => setUser(r.data))
      .catch(logoutLocal)
      .finally(() => setLoading(false))
  }, [logoutLocal])

  const login = async (email, password) => {
    const r = await api.post('/auth/login', { email, password })
    setToken(r.token)
    setUser(r.user.data ?? r.user)
  }

  const register = async (form) => {
    const r = await api.post('/auth/register', form)
    setToken(r.token)
    setUser(r.user.data ?? r.user)
  }

  // Вход через Google: ID-токен проверяет сервер, при первом входе создаётся учётная запись клиента
  const loginWithGoogle = async (credential) => {
    const r = await api.post('/auth/google', { credential })
    setToken(r.token)
    setUser(r.user.data ?? r.user)
  }

  const logout = async () => {
    try { await api.post('/auth/logout') } catch { /* токен мог уже истечь */ }
    logoutLocal()
  }

  return (
    <AuthContext.Provider value={{ user, loading, login, loginWithGoogle, register, logout, setUser }}>
      {children}
    </AuthContext.Provider>
  )
}
