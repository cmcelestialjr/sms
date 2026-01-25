
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
import Absences from './components/Absences';
import Holidays from './components/holidays';
import AttendanceStationListQr from './components/AttendanceStationListQr';
import AttendanceScannerQr from './components/AttendanceScannerQr';
import Dtrs from './components/Dtrs';
import Dtr from './components/Dtr';
import Reports from './components/Reports';

function RouterApp() {  
  return (
    <Router>
      <Routes>
        <Route path="/login"
          element={<Login />} 
        />

        <Route path="/attendance" 
          element={<AttendanceStationListPage />}
        />

        <Route path="/attendance/qr" 
          element={<AttendanceStationListQr />}
        />

        <Route path="/attendance/:id" 
          element={<AttendanceScanner />}
        />

        <Route path="/attendance/qr/:id" 
          element={<AttendanceScannerQr />}
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

        <Route path="/absences" 
          element={
          <ProtectedRoute allowedRoles={["1","2","3"]}>
            <Absences />
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

        {/* <Route path="/dtr/:id/:from" 
          element={
          <ProtectedRoute allowedRoles={["1","2","3"]}>
            <Dtr />
          </ProtectedRoute>}
        /> */}

        {/* <Route path="/dtrs" 
          element={
          <ProtectedRoute allowedRoles={["1","2"]}>
            <Dtrs />
          </ProtectedRoute>}
        /> */}

        <Route path="/schoolYears" 
          element={
          <ProtectedRoute allowedRoles={["1","2"]}>
            <SchoolYears />
          </ProtectedRoute>}
        />

        <Route path="/holidays" 
          element={
          <ProtectedRoute allowedRoles={["1","2"]}>
            <Holidays />
          </ProtectedRoute>}
        />

        <Route path="/reports" 
          element={
            <ProtectedRoute allowedRoles={["1"]}>
              <Reports />
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