
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Login from './components/LoginPage';
import ProtectedRoute from './components/ProtectedRoute';
import AttendanceStationListPage from './components/AttendanceStationListPage';
import AttendanceScanner from './components/AttendanceScannerPage';
import Dashboard from './components/Dashboard';
import UsersList from './components/UsersList';
import Logout from './components/Logout';
import Attendances from './components/Attendances';
import Students from './components/Students';
import Stations from './components/Stations';
import Teachers from './components/Teachers';
import Messages from './components/Messages';
import SchoolYears from './components/SchoolYears';

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
          <ProtectedRoute allowedRoles={["1","2","3"]}>
            <Dashboard />
          </ProtectedRoute>}
        />

        <Route path="/attendances" 
          element={
          <ProtectedRoute allowedRoles={["1","2","3"]}>
            <Attendances />
          </ProtectedRoute>}
        />

        <Route path="/students" 
          element={
          <ProtectedRoute allowedRoles={["1","2","3"]}>
            <Students />
          </ProtectedRoute>}
        />

        <Route path="/teachers" 
          element={
          <ProtectedRoute allowedRoles={["1","2"]}>
            <Teachers />
          </ProtectedRoute>}
        />

        <Route path="/stations" 
          element={
          <ProtectedRoute allowedRoles={["1","2","3"]}>
            <Stations />
          </ProtectedRoute>}
        />

        <Route path="/messages" 
          element={
          <ProtectedRoute allowedRoles={["1","2","3"]}>
            <Messages />
          </ProtectedRoute>}
        />

        <Route path="/schoolYears" 
          element={
          <ProtectedRoute allowedRoles={["1","2"]}>
            <SchoolYears />
          </ProtectedRoute>}
        />

        <Route path="/users" 
          element={
            <ProtectedRoute allowedRoles={["1"]}>
              <UsersList />
            </ProtectedRoute>} 
        />

        <Route path="/logout" 
          element={
            <ProtectedRoute allowedRoles={["1","2","3"]}>
              <Logout />
            </ProtectedRoute>} 
        />

      </Routes>
    </Router>
  );
}

export default RouterApp;