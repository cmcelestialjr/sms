    import React, { useEffect, useRef, useState } from 'react';
    import { User } from 'lucide-react';
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
      const inputRef = useRef(null);
      const audioRef = useRef(null);

      useEffect(() => {
        // Update Time Every Second
        const timer = setInterval(() => {
          setTimeNow(new Date());
        }, 1000);
        return () => clearInterval(timer);
      }, []);

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
      }, []);

      const fetchAttendances = async () => {
        const response = await axios.get('/api/attendances', {
          params: { id: id,
            timeNow: timeNow 
          }
        });
        setRecentAttendances(response.data);
      };

      const handleScan = async () => {
        audioRef.current.currentTime = 0;
        audioRef.current.volume = 1;
        audioRef.current.play();

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
            const scannedStudent = response.data.student;
            setScannedStudent(scannedStudent);            
            setRecentAttendances(response.data.attendances);            
          }else{
            toastr.error(response.data.message, "Error");
          }
        } catch (error) {
          console.error('Scan error:', error.response?.data || error.message);
        }
        setCode('');
      };

      const handleKeyDown = (e) => {
        if (e.key === 'Enter') {
          handleScan();
        }
      };

      const formatDate = (date) => {
        const validDate = new Date(date);

        return validDate.toLocaleDateString('en-US', {
          month: 'short',
          day: '2-digit',
          year: 'numeric',
        });
      };

      const formatTime = (date) => {
        const validDate = new Date(date);

        return validDate.toLocaleTimeString('en-US', {
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit',
          hour12: true,
        });
      };

      return (
        <div className="flex flex-col min-h-screen bg-gray-100 p-4">
          {/* Success Sound */}
          {/* <audio ref={audioRef} preload="auto">
            <source src="assets/audio/success.mp3" type="audio/mpeg" />
            Your browser does not support the audio element.
          </audio> */}

          <audio ref={audioRef} src="/assets/audio/beep.mp3" preload="auto" />

          {/* Top Section */}
          <div className="flex gap-4 mb-4">
            {/* Left: Camera */}
            <div className="flex-1 bg-white rounded-2xl flex items-center justify-center shadow-md">
              {/* {cameraAvailable ? (
                <div className="text-gray-600">🎥 Camera Detected</div>
              ) : (
                <div className="text-gray-400 text-6xl">📷</div>
              )} */}
              <img src="/images/clstldev2.png" alt="Logo" className="mx-auto w-40" />
            </div>

            {/* Center: Date Time */}
            <div className="flex-1 bg-white rounded-2xl flex flex-col items-center justify-center shadow-md">
              <div className="text-4xl font-bold">{formatDate(timeNow)}</div>
              <div className="text-5xl font-bold">{formatTime(timeNow)}</div>
            </div>

            {/* Right: Scanned Student */}
            <div className="flex-1 p-6 bg-white rounded-2xl flex flex-col items-center justify-center shadow-md">
              {scannedStudent ? (
                <>
                  {scannedStudent.photo ? (
                    <img
                      src={scannedStudent.photo}
                      alt="Student"
                      className="w-40 h-40 rounded-full object-cover mb-4"
                    />
                  ) : (
                    <div className="w-40 h-40 mb-2 flex items-center justify-center bg-gray-200 rounded-full">
                      <User className="w-25 h-25 text-gray-500" />
                    </div>
                  )}
                  <h2 className="text-xl font-semibold">{scannedStudent.student.firstname} {scannedStudent.student.lastname}</h2>
                  <h2 className="text-md font-semibold">{scannedStudent.student.student_id}</h2>
                  <h2 className="text-md font-semibold">{formatTime(scannedStudent.scanned_at)}</h2>
                  <h2 className={`text-sm font-semibold ${scannedStudent.status === 'success' ? 'text-green-500' : 'text-red-500'}`}>
                    {scannedStudent.message}
                  </h2>
                </>
              ) : (
                <div className="w-50 h-50 mb-2 flex items-center justify-center bg-gray-200 rounded-full">
                  <User className="w-35 h-35 text-gray-500" />
                </div>
              )}
            </div>
          </div>

          {/* Hidden Input for Scanner */}
          <input
            ref={inputRef}
            type="text"
            value={code}
            onChange={(e) => setCode(e.target.value)}
            onKeyDown={handleKeyDown}
            autoFocus
            className="opacity-0 absolute"
          />

          {/* Bottom Section: Recent Attendance */}
          <div className="flex-1 bg-white rounded-2xl p-6 overflow-y-auto shadow-md">
            <h3 className="text-xl font-bold text-gray-800 mb-4  border-b pb-2">Recent Attendance</h3>
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
              {recentAttendances.map((student, index) => (
                <div
                  key={index}
                  className="flex flex-col items-center p-4 bg-gray-50 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 ease-in-out"
                >
                  {student.photo ? (
                    <img
                      src={student.photo}
                      alt="Student"
                      className="w-20 h-20 rounded-full object-cover mb-2"
                    />
                  ) : (
                    <div className="w-20 h-20 mb-2 flex items-center justify-center bg-gray-200 rounded-full">
                      <User className="w-10 h-10 text-gray-500" />
                    </div>
                  )}
                  <div className="text-xs font-semibold">{student.student.firstname} {student.student.lastname}</div>
                  <div className="text-xs text-gray-500">{student.student.student_id}</div>
                  <div className="text-xs text-gray-500">{formatTime(student.scanned_at)}</div>
                  <div className={`text-xs text-gray-500 ${student.status === 'success' ? 'text-green-500' : 'text-red-500'}`}>
                    {student.message}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      );
    };

    export default AttendanceScannerPage;
