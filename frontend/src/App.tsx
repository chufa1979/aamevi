import { BrowserRouter as Router, Routes, Route } from 'react-router-dom'

function App() {
  return (
    <Router>
      <Routes>
        <Route path="/" element={
          <div className="min-h-screen bg-gradient-to-br from-sky-50 to-blue-50 flex items-center justify-center">
            <div className="text-center">
              <h1 className="text-4xl font-bold text-gray-900 mb-4">🎓 AAMEVI</h1>
              <p className="text-xl text-gray-600 mb-8">Plataforma de E-Learning</p>
              <div className="space-y-2 text-gray-700">
                <p>✅ Backend: http://localhost:3000</p>
                <p>✅ API Docs: http://localhost:3000/api/docs</p>
                <p className="mt-4 text-sm text-gray-500">Estructura lista para Fase 1: Autenticación</p>
              </div>
            </div>
          </div>
        } />
      </Routes>
    </Router>
  )
}

export default App
