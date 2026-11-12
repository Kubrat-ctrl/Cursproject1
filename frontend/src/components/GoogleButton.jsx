import { useEffect, useRef } from 'react'

// Кнопка «Войти через Google» (Google Identity Services). Client ID задаётся в frontend/.env.
// После выбора аккаунта Google возвращает ID-токен (credential), его проверяет сервер.
const CLIENT_ID = import.meta.env.VITE_GOOGLE_CLIENT_ID

let scriptPromise = null
let initialized = false
let handler = null

function loadGsi() {
  if (window.google?.accounts?.id) return Promise.resolve()
  if (!scriptPromise) {
    scriptPromise = new Promise((resolve, reject) => {
      const s = document.createElement('script')
      s.src = 'https://accounts.google.com/gsi/client'
      s.async = true
      s.onload = resolve
      s.onerror = () => {
        scriptPromise = null
        reject(new Error('Не удалось загрузить скрипт Google. Проверьте подключение к интернету'))
      }
      document.head.appendChild(s)
    })
  }
  return scriptPromise
}

export default function GoogleButton({ onCredential, onError }) {
  const box = useRef(null)
  const callback = useRef(onCredential)
  callback.current = onCredential

  useEffect(() => {
    if (!CLIENT_ID) return undefined
    let cancelled = false

    loadGsi()
      .then(() => {
        if (cancelled || !box.current) return
        if (!initialized) {
          window.google.accounts.id.initialize({
            client_id: CLIENT_ID,
            callback: (r) => handler?.(r.credential),
          })
          initialized = true
        }
        handler = (credential) => callback.current(credential)
        window.google.accounts.id.renderButton(box.current, {
          theme: 'outline',
          size: 'large',
          text: 'continue_with',
          locale: 'ru',
          width: 340,
        })
      })
      .catch((e) => onError?.(e))

    return () => { cancelled = true }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  if (!CLIENT_ID) {
    return <p className="muted">Вход через Google не настроен: укажите VITE_GOOGLE_CLIENT_ID в frontend/.env</p>
  }
  return <div ref={box} className="google-btn" />
}
