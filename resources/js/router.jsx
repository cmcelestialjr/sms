
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Login from './components/LoginPage';
import ProtectedRoute from './components/ProtectedRoute';
import AttendanceStationListPage from './components/AttendanceStationListPage';
import AttendanceScanner from './components/AttendanceScannerPage';
import Dashboard from './components/Dashboard';
import Logout from './components/Logout';

function RouterApp() {  
  return (
    <Router>
      <Routes>
        <Route path="/" 
          element={<Login />} 
        />

        <Route path="/attendance" 
          element={<AttendanceStationListPage />}
        />

        <Route path="/attendance/:id" 
          element={<AttendanceScanner />}
        />

        <Route path="/dashboard" 
          element={
          <ProtectedRoute allowedRoles={["1"]}>
            <Dashboard />
          </ProtectedRoute>}
        />

        <Route path="/logout" 
          element={
            <ProtectedRoute allowedRoles={["1"]}>
              <Logout />
            </ProtectedRoute>} 
        />

      </Routes>
    </Router>
  );
}

export default RouterApp;