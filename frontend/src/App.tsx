import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { Layout } from '@/components/layout/Layout';
import { Home } from '@/pages/Home';
import { Placeholder } from '@/pages/Placeholder';

function App() {
  return (
    <Router>
      <Routes>
        <Route element={<Layout />}>
          <Route index element={<Home />} />
          <Route path="/cursos" element={<Placeholder title="Cursos" />} />
          <Route path="/mis-cursos" element={<Placeholder title="Mis cursos" />} />
          <Route path="/progreso" element={<Placeholder title="Mi progreso" />} />
          <Route path="/certificados" element={<Placeholder title="Certificados" />} />
          <Route path="/ayuda" element={<Placeholder title="Ayuda" />} />
          <Route path="/buscar" element={<Placeholder title="Buscar" />} />
          <Route path="/login" element={<Placeholder title="Iniciar sesión" />} />
          <Route path="/registro" element={<Placeholder title="Crear cuenta" />} />
          <Route path="*" element={<Placeholder title="Página no encontrada" />} />
        </Route>
      </Routes>
    </Router>
  );
}

export default App;
