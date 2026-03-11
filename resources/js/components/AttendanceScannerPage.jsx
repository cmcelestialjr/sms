import React, { useEffect, useRef, useState } from 'react';
import { User, Users, LogIn, LogOut, CheckCircle, Clock } from 'lucide-react';
import axios from 'axios';
import { useParams } from 'react-router-dom';
import 'toastr/build/toastr.min.css';
import toastr from 'toastr';

const AttendanceScannerPage = () => {
  const { id } = useParams();
  const [code, setCode] = useState('');
  const [scannedStudent, setScannedStudent] = useState(null);
  const [recentAttendances, setRecentAttendances] = useState([]);
  const [cameraAvailable, setCameraAvailable] = useState(false);
  const [timeNow, setTimeNow] = useState(new Date());
  const [totalStudents, setTotalStudents] = useState(0);
  const [loggedInCount, setLoggedInCount] = useState(0);
  const [loggedOutCount, setLoggedOutCount] = useState(0);
  
  const inputRef = useRef(null);
  const audioRef = useRef(null);
  const scanTimeoutRef = useRef(null);

  // Update Time Every Second
  useEffect(() => {
    const timer = setInterval(() => {
      setTimeNow(new Date());
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  // Ensure the hidden input always stays focused so the RFID scanner works seamlessly
  useEffect(() => {
    const handleGlobalClick = () => {
      if (inputRef.current) {
        inputRef.current.focus();
      }
    };
    window.addEventListener('click', handleGlobalClick);
    return () => {
      window.removeEventListener('click', handleGlobalClick);
    };
  }, []);

  useEffect(() => {
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
      navigator.mediaDevices.getUserMedia({ video: true })
        .then(() => setCameraAvailable(true))
        .catch(() => setCameraAvailable(false));
    } else {
      setCameraAvailable(false);
    }
  }, []);

  useEffect(() => {
    fetchAttendances();
    handleCount();
  }, []);

  const resetScanTimeout = () => {
    if (scanTimeoutRef.current) {
      clearTimeout(scanTimeoutRef.current);
    }
    scanTimeoutRef.current = setTimeout(() => {
      setScannedStudent(null);
    }, 30000); // Clear the scanned profile after 30 seconds
  };

  const fetchAttendances = async () => {
    try {
      const response = await axios.get('/api/attendances/recent', {
        params: { id: id, timeNow: timeNow }
      });
        setRecentAttendances(response.data);
    } catch (error) {
      console.error("Failed to fetch attendances", error);
    }
  };

  const handleCount = async () => {
    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const response = await axios.get('/api/attendance/count', {            
        params: {
          id: id,
          timeNow: timeNow,
          api_key: 'SMS@CelestialDev01',
        },
        headers: {
          'X-CSRF-Token': csrfToken,
        }
      });

      setTotalStudents(response.data.total);
      setLoggedInCount(response.data.loggedin);
      setLoggedOutCount(response.data.loggedout);
    } catch (error) {
      console.error("Failed to fetch counts", error);
    }
  };

  const handleScan = async () => {
    if (audioRef.current) {
      audioRef.current.currentTime = 0;
      audioRef.current.volume = 1;
      audioRef.current.play();
    }

    if (!code.trim()) return;

    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const response = await axios.post('/api/attendance/scan', {
        id: id,
        code: code,
        api_key: 'SMS@CelestialDev01',
      }, {
        headers: {
          'X-CSRF-Token': csrfToken,
        }
      });

      if (response.data.success) {
        setScannedStudent(response.data.student);            
        setRecentAttendances(response.data.attendances);
        
        // Refresh counts after successful scan
        handleCount();
        resetScanTimeout();
      } else {
        toastr.error(response.data.message, "Error");
      }
    } catch (error) {
      console.error('Scan error:', error.response?.data || error.message);
      toastr.error("An error occurred during scanning.", "System Error");
    }
    setCode(''); // Reset code input instantly
  };

  const handleKeyDown = (e) => {
    if (e.key === 'Enter') {
      handleScan();
    }
  };

  const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
      month: 'long',
      day: '2-digit',
      year: 'numeric',
    });
  };

  const formatTime = (date) => {
    return new Date(date).toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: true,
    });
  };

  const formatDay = (date) => new Date(date).toLocaleDateString('en-US', { weekday: 'long' });

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col font-sans">
      {/* Hidden Inputs & Audio */}
      <input
        ref={inputRef}
        type="text"
        value={code}
        onChange={(e) => setCode(e.target.value)}
        onKeyDown={handleKeyDown}
        className="opacity-0 absolute -z-10"
        autoFocus
      />
      <audio ref={audioRef} src="/assets/audio/beep.mp3" preload="auto" />

      {/* Header Section */}
      <header className="bg-white shadow-sm border-b border-slate-200 px-8 py-4 flex justify-between items-center">
        <div className="flex items-center">
          <img src="/images/clstldev2.png" alt="Celestial Dev Logo" className="w-24 h-24 object-contain" />
        </div>
        
        <div className="text-center flex flex-col items-center">
          <h1 className="text-3xl lg:text-4xl font-extrabold text-blue-900 uppercase tracking-wider">
            Alangalang National High School
          </h1>
          <p className="text-lg text-slate-500 font-medium mt-1 uppercase tracking-widest">
            Student Attendance Portal
          </p>
          <div className="mt-3 bg-blue-50 px-6 py-2 rounded-full border border-blue-100 shadow-inner">
            <p className="text-xl text-blue-800 font-bold">
              {formatDay(timeNow)}, {formatDate(timeNow)} <span className="mx-2 text-blue-300">|</span> 
              <span className="font-mono text-2xl tracking-tight ml-2">{formatTime(timeNow)}</span>
            </p>
          </div>
        </div>

        <div className="flex items-center">
          <img src="/assets/logo/logo.jpg" alt="School Logo" className="w-24 h-24 object-contain rounded-full shadow-sm" />
        </div>
      </header>

      {/* Main Content Area */}
      <main className="flex-1 max-w-7xl w-full mx-auto p-6 grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {/* Left Column: Active Scanner Display */}
        <div className="lg:col-span-5 bg-white rounded-2xl shadow-lg border border-slate-100 flex flex-col overflow-hidden">
          <div className="bg-blue-900 py-3 text-center">
            <h2 className="text-white font-semibold text-lg tracking-wide">LATEST SCAN</h2>
          </div>
          
          <div className="flex-1 flex flex-col items-center justify-center p-8">
            {scannedStudent ? (
              <div className="flex flex-col items-center animate-in fade-in zoom-in duration-300">
                <div className="relative">
                  {scannedStudent.student?.photo ? (
                    <img 
                      src={scannedStudent.student.photo} 
                      alt="Student" 
                      className="w-56 h-56 rounded-full object-cover border-8 border-slate-50 shadow-xl"
                    />
                  ) : (
                    <div className="w-56 h-56 rounded-full bg-slate-100 flex items-center justify-center border-8 border-slate-50 shadow-xl">
                      <User size={100} className="text-slate-300" />
                    </div>
                  )}
                  {/* Status Indicator Badge */}
                  <div className={`absolute bottom-2 right-2 w-12 h-12 rounded-full border-4 border-white flex items-center justify-center shadow-md ${scannedStudent.status === 'in' ? 'bg-emerald-500' : 'bg-orange-500'}`}>
                    <CheckCircle className="text-white w-6 h-6" />
                  </div>
                </div>

                <h3 className="mt-6 text-3xl font-bold text-slate-800 text-center">
                  {scannedStudent.student?.firstname}
                  {scannedStudent.student?.middlename?.trim() &&
                    ` ${scannedStudent.student.middlename.trim()[0]}.`}
                  {" "}
                  {scannedStudent.student?.lastname}
                  {scannedStudent.student?.extname && ` ${scannedStudent.student.extname}`}
                </h3>
                <p className="text-slate-500 mt-1 uppercase text-xl font-medium tracking-wider">
                  ID: {scannedStudent.student?.student_id || 'N/A'}
                </p>
                <p className="text-slate-400 text-sm font-semibold mt-1">
                  Grade {scannedStudent.grade} - {scannedStudent.section}
                </p>

                <div className={`mt-8 px-10 py-3 rounded-full text-white font-bold text-2xl uppercase shadow-lg ${scannedStudent.status === 'in' ? 'bg-emerald-500 shadow-emerald-500/30' : 'bg-orange-500 shadow-orange-500/30'}`}>
                  TIME {scannedStudent.status === 'in' ? 'IN' : 'OUT'} SECURED
                </div>
              </div>
            ) : (
              <div className="flex flex-col items-center text-slate-400 opacity-80">
                <div className="w-48 h-48 rounded-full bg-slate-50 border-4 border-dashed border-slate-200 flex items-center justify-center mb-6">
                  <Clock size={80} className="text-slate-300 animate-pulse" />
                </div>
                <h3 className="text-2xl font-bold text-slate-600">Ready to Scan</h3>
                <p className="text-slate-500 mt-2 text-lg">Please tap your RFID card</p>
              </div>
            )}
          </div>
        </div>

        {/* Right Column: Statistics & Log Table */}
        <div className="lg:col-span-7 flex flex-col gap-6">
          
          {/* Top Row: Statistics Cards */}
          <div className="grid grid-cols-3 gap-4">
            <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 flex items-center">
              <div className="bg-blue-100 p-3 rounded-lg mr-4">
                <Users className="text-blue-600 w-8 h-8" />
              </div>
              <div>
                <p className="text-sm text-slate-500 font-semibold uppercase">Total Students</p>
                <p className="text-2xl font-bold text-slate-800">{totalStudents}</p>
              </div>
            </div>
            
            <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 flex items-center">
              <div className="bg-emerald-100 p-3 rounded-lg mr-4">
                <LogIn className="text-emerald-600 w-8 h-8" />
              </div>
              <div>
                <p className="text-sm text-slate-500 font-semibold uppercase">Logged In</p>
                <p className="text-2xl font-bold text-slate-800">{loggedInCount}</p>
              </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 flex items-center">
              <div className="bg-orange-100 p-3 rounded-lg mr-4">
                <LogOut className="text-orange-600 w-8 h-8" />
              </div>
              <div>
                <p className="text-sm text-slate-500 font-semibold uppercase">Logged Out</p>
                <p className="text-2xl font-bold text-slate-800">{loggedOutCount}</p>
              </div>
            </div>
          </div>

          {/* Bottom Row: Recent Scans Table */}
          <div className="bg-white rounded-xl shadow-sm border border-slate-100 flex-1 flex flex-col overflow-hidden">
            <div className="border-b border-slate-100 bg-slate-50 py-4 px-6 flex justify-between items-center">
              <h3 className="font-bold text-slate-700 text-lg">Today's Recent Scans</h3>
              <span className="text-sm text-slate-400 font-medium">Auto-updating</span>
            </div>
            
            <div className="overflow-y-auto flex-1 p-0">
              <table className="w-full text-left border-collapse">
                <thead className="bg-white shadow-sm">
                  <tr className="text-slate-400 text-xs uppercase tracking-wider">
                    <th className="p-4 font-semibold">Student Name</th>
                    <th className="p-4 font-semibold">Grade & Sec</th>
                    <th className="p-4 font-semibold">Time</th>
                    <th className="p-4 font-semibold">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-50">
                  {recentAttendances.length > 0 ? (
                    recentAttendances.map((log, index) => (
                      <tr key={index} className="hover:bg-slate-50 transition-colors">
                        <td className="p-4 font-semibold text-slate-700">
                          {log.student?.firstname} {log.student?.lastname}
                        </td>
                        <td className="p-4 text-slate-500 text-sm">
                          {log.student?.grade} - {log.student?.section}
                        </td>
                        <td className="p-4 text-slate-600 font-medium">
                          {formatTime(log.created_at)}
                        </td>
                        <td className="p-4">
                          <span className={`px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide ${
                            log.status === 'in' 
                              ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' 
                              : 'bg-orange-100 text-orange-700 border border-orange-200'
                          }`}>
                            {log.status}
                          </span>
                        </td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan="4" className="text-center py-10 text-slate-400 italic">
                        No recent scans found for today.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </main>
    </div>
  );
};

export default AttendanceScannerPage;