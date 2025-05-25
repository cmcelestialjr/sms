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
      const [totalStudents, setTotalStudents] = useState(0);
      const [loggedInCount, setLoggedInCount] = useState(0);
      const [loggedOutCount, setLoggedOutCount] = useState(0);
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
        handleCount();
      }, []);

      const fetchAttendances = async () => {
        const response = await axios.get('/api/attendances', {
          params: { id: id,
            timeNow: timeNow 
          }
        });
        setRecentAttendances(response.data);
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
          
        }
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

      const formatDay = (date) => new Date(date).toLocaleDateString('en-US', { weekday: 'long' });

      // return (
      //   <div className="flex flex-col min-h-screen bg-gray-100 p-4">
      //     {/* Success Sound */}
      //     {/* <audio ref={audioRef} preload="auto">
      //       <source src="assets/audio/success.mp3" type="audio/mpeg" />
      //       Your browser does not support the audio element.
      //     </audio> */}

      //     <audio ref={audioRef} src="/assets/audio/beep.mp3" preload="auto" />

      //     {/* Top Section */}
      //     <div className="flex gap-4 mb-4">
      //       {/* Left: Camera */}
      //       <div className="flex-1 bg-white rounded-2xl flex items-center justify-center shadow-md">
      //         {/* {cameraAvailable ? (
      //           <div className="text-gray-600">🎥 Camera Detected</div>
      //         ) : (
      //           <div className="text-gray-400 text-6xl">📷</div>
      //         )} */}
      //         <img src="/images/clstldev2.png" alt="Logo" className="mx-auto w-40" />
      //       </div>

      //       {/* Center: Date Time */}
      //       <div className="flex-1 bg-white rounded-2xl flex flex-col items-center justify-center shadow-md">
      //         <div className="text-4xl font-bold">{formatDate(timeNow)}</div>
      //         <div className="text-5xl font-bold">{formatTime(timeNow)}</div>
      //       </div>

      //       {/* Right: Scanned Student */}
      //       <div className="flex-1 p-6 bg-white rounded-2xl flex flex-col items-center justify-center shadow-md">
      //         {scannedStudent ? (
      //           <>
      //             {scannedStudent.photo ? (
      //               <img
      //                 src={scannedStudent.photo}
      //                 alt="Student"
      //                 className="w-40 h-40 rounded-full object-cover mb-4"
      //               />
      //             ) : (
      //               <div className="w-40 h-40 mb-2 flex items-center justify-center bg-gray-200 rounded-full">
      //                 <User className="w-25 h-25 text-gray-500" />
      //               </div>
      //             )}
      //             <h2 className="text-xl font-semibold">{scannedStudent.student.firstname} {scannedStudent.student.lastname}</h2>
      //             <h2 className="text-md font-semibold">{scannedStudent.student.student_id}</h2>
      //             <h2 className="text-md font-semibold">{formatTime(scannedStudent.scanned_at)}</h2>
      //             <h2 className={`text-sm font-semibold ${scannedStudent.status === 'success' ? 'text-green-500' : 'text-red-500'}`}>
      //               {scannedStudent.message}
      //             </h2>
      //           </>
      //         ) : (
      //           <div className="w-50 h-50 mb-2 flex items-center justify-center bg-gray-200 rounded-full">
      //             <User className="w-35 h-35 text-gray-500" />
      //           </div>
      //         )}
      //       </div>
      //     </div>

      //     {/* Hidden Input for Scanner */}
      //     <input
      //       ref={inputRef}
      //       type="text"
      //       value={code}
      //       onChange={(e) => setCode(e.target.value)}
      //       onKeyDown={handleKeyDown}
      //       autoFocus
      //       className="opacity-0 absolute"
      //     />

      //     {/* Bottom Section: Recent Attendance */}
      //     <div className="flex-1 bg-white rounded-2xl p-6 overflow-y-auto shadow-md">
      //       <h3 className="text-xl font-bold text-gray-800 mb-4  border-b pb-2">Recent Attendance</h3>
      //       <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
      //         {recentAttendances.map((student, index) => (
      //           <div
      //             key={index}
      //             className="flex flex-col items-center p-4 bg-gray-50 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 ease-in-out"
      //           >
      //             {student.photo ? (
      //               <img
      //                 src={student.photo}
      //                 alt="Student"
      //                 className="w-20 h-20 rounded-full object-cover mb-2"
      //               />
      //             ) : (
      //               <div className="w-20 h-20 mb-2 flex items-center justify-center bg-gray-200 rounded-full">
      //                 <User className="w-10 h-10 text-gray-500" />
      //               </div>
      //             )}
      //             <div className="text-xs font-semibold">{student.student.firstname} {student.student.lastname}</div>
      //             <div className="text-xs text-gray-500">{student.student.student_id}</div>
      //             <div className="text-xs text-gray-500">{formatTime(student.scanned_at)}</div>
      //             <div className={`text-xs text-gray-500 ${student.status === 'success' ? 'text-green-500' : 'text-red-500'}`}>
      //               {student.message}
      //             </div>
      //           </div>
      //         ))}
      //       </div>
      //     </div>
      //   </div>
      // );

  //   return (
  //   <div className="flex flex-col min-h-screen bg-gray-100 items-center py-4">
  //     <div className="w-full max-w-6xl rounded-2xl border-4 border-gray-300 shadow-lg overflow-hidden bg-white">

  //       {/* Audio */}
  //       <audio ref={audioRef} src="/assets/audio/beep.mp3" preload="auto" />

  //       {/* Top Section */}
  //       <div className="px-8 py-4 bg-white">
  //         <div className="flex items-start space-x-4 pt-2 pb-1">
  //           {/* Logos */}
  //           <img src="/images/clstldev2.png" alt="Logo" className="w-24 h-24 object-contain" />
  //           <img src="/assets/logo/logo.jpg" alt="Logo" className="w-24 h-24 object-contain" />

  //           {/* School Info Block */}
  //           <div className="flex flex-col justify-center w-full pl-4">
  //             <h1
  //               className="text-4xl font-extrabold text-gray-800 leading-tight text-center"
  //               style={{
  //                 textShadow: '1px 1px 0 #ccc, -1px -1px 0 #fff',
  //               }}
  //             >
  //               ALANGALANG NATIONAL HIGH SCHOOL
  //             </h1>

  //             <div className="flex justify-between mt-2">
  //               <p className="text-2xl text-orange-700 font-medium">Tunga, Leyte</p>
  //               <p className="text-2xl text-blue-800 font-medium">
  //                 {formatDay(timeNow)}, {formatDate(timeNow)} — {formatTime(timeNow)}
  //               </p>
  //             </div>
  //           </div>
  //         </div>
  //       </div>

  //       {/* Middle Section */}
  //       <div
  //         className="flex-1 flex items-center justify-center bg-cover bg-center relative"
  //         style={{ backgroundImage: `url('/assets/background/bg2.jpg')`, height: '500px' }}
  //       >
  //         <div className="absolute inset-0 bg-black bg-opacity-20" />
  //         {scannedStudent ? (
  //           <div className="backdrop-blur-md bg-white bg-opacity-80 border border-gray-300 rounded-2xl p-8 flex flex-col items-center shadow-2xl w-[380px] max-w-full z-10">
  //             {scannedStudent.photo ? (
  //               <img
  //                 src={scannedStudent.photo}
  //                 alt="Student"
  //                 className="w-40 h-40 rounded-full object-cover mb-4 border-4 border-white shadow-md"
  //               />
  //             ) : (
  //               <div className="w-40 h-40 mb-4 flex items-center justify-center bg-gray-200 rounded-full border border-gray-300">
  //                 <User className="w-20 h-20 text-gray-500" />
  //               </div>
  //             )}
  //             <h2 className="text-2xl font-bold text-gray-800 text-center">
  //               {scannedStudent.student.firstname} {scannedStudent.student.lastname}
  //             </h2>
  //             <h3 className="text-lg text-gray-600 mb-1 text-center">
  //               {scannedStudent.student.grade} - {scannedStudent.student.section}
  //             </h3>
  //             <div className="text-md text-gray-600">{formatTime(scannedStudent.scanned_at)}</div>
  //             <div
  //               className={`mt-1 text-md font-semibold ${
  //                 scannedStudent.status === 'success' ? 'text-green-600' : 'text-red-600'
  //               }`}
  //             >
  //               {scannedStudent.message}
  //             </div>
  //           </div>
  //         ) : (
  //           <div className="bg-white bg-opacity-70 border border-gray-300 p-6 rounded-2xl text-gray-500 text-xl flex items-center justify-center shadow-md z-10">
  //             Waiting for Scan...
  //           </div>
  //         )}
  //       </div>

  //       {/* Hidden Input */}
  //       <input
  //         ref={inputRef}
  //         type="text"
  //         value={code}
  //         onChange={(e) => setCode(e.target.value)}
  //         onKeyDown={handleKeyDown}
  //         autoFocus
  //         className="opacity-0 absolute"
  //       />

  //       {/* Footer */}
  //       <div className="text-center p-4 bg-white">
  //         <p className="text-sm text-gray-500">© 2025 ALANGALANG NATIONAL HIGH SCHOOL. All rights reserved.</p>
  //       </div>
  //     </div>
  //   </div>
  // );


  return (
  <div className="h-screen w-screen flex items-center justify-center bg-gray-100">
    <div className="flex flex-col w-full h-full border-4 border-gray-300 rounded-none shadow-lg overflow-hidden">

      {/* Audio */}
      <audio ref={audioRef} src="/assets/audio/beep.mp3" preload="auto" />

      {/* Top Section */}
      <div className="bg-white px-8 py-4 border border-gray-300 border-b-4 border-b-gray-400 rounded-md shadow-2xl">
        <div className="flex items-start space-x-4">
          <img src="/images/clstldev2.png" alt="Logo" className="w-24 h-24 object-contain" />
          <img src="/assets/logo/logo.jpg" alt="Logo" className="w-24 h-24 object-contain" />

          <div className="flex flex-col justify-center w-full pl-4">
            <h1
              className="text-4xl font-extrabold text-gray-800 leading-tight"
              style={{
                textShadow: '1px 1px 0 #ccc, -1px -1px 0 #fff',
              }}
            >
              ALANGALANG NATIONAL HIGH SCHOOL
            </h1>
            <div className="flex justify-between mt-2">
              <p className="text-2xl text-orange-700 font-medium">Alangalang, Leyte</p>
              <p className="text-2xl text-blue-800 font-medium">
                {formatDay(timeNow)}, {formatDate(timeNow)} — {formatTime(timeNow)}
              </p>
            </div>
          </div>
        </div>
      </div>


      {/* Middle Section */}
      <div
        className="flex-1 flex items-center justify-center bg-cover bg-center relative"
        style={{ backgroundImage: `url('/assets/background/bg1.jpg')`, backgroundColor: '#f3f4f6' }}
      >
        <div className="absolute inset-0 bg-opacity-10" />

        <div className="flex gap-6 p-6 w-full">

          {/* Left: Student Details */}
          <div className="w-1/3 pl-12 pr-12">
            <div
              className="bg-white border border-gray-300 p-4 rounded-2xl shadow-md flex items-center justify-center min-h-[320px]"
              style={{ backgroundColor: 'rgba(16, 52, 182, 0.25)' }}
            >
              {scannedStudent ? (
                <div className="text-gray-800 text-center space-y-3">
                  <h2 className="text-3xl font-semibold bg-white bg-opacity-70 rounded-lg shadow px-4 py-4 p-4">
                    {scannedStudent.student.firstname} {scannedStudent.student.middlename?.trim() 
                      ? scannedStudent.student.middlename.trim().charAt(0) + '.' 
                      : ''} {scannedStudent.student.lastname} {scannedStudent.student.extname}
                  </h2>
                  <h3 className="text-2xl bg-white bg-opacity-60 rounded-lg shadow px-4 py-4 p-4 text-gray-700 italic">
                    Grade: {scannedStudent.student.grade} <br></br> 
                    Section: {scannedStudent.student.section}
                  </h3>
                  <div className="text-4xl bg-white bg-opacity-50 rounded-lg shadow px-4 py-4 p-2 text-gray-800">
                    {formatTime(scannedStudent.scanned_at)}
                    {/* <br></br>
                    <span
                      className={`text-2xl font-semibold ${
                        scannedStudent.status === 'success' ? 'text-green-600' : 'text-red-600'
                      }`}
                    >
                      {scannedStudent.message}
                    </span> */}
                  </div>  
                  
                </div>
              ) : (
                <div className="text-gray-800 text-center space-y-3">
                  <h2 className="text-3xl font-semibold bg-white bg-opacity-70 rounded-lg shadow px-4 py-4">
                    No Student Scanned
                  </h2>
                  <h3 className="text-2xl bg-white bg-opacity-60 rounded-lg shadow px-4 py-4 text-gray-700 italic">
                    Awaiting student info...
                  </h3>
                  <div className="text-4xl bg-white bg-opacity-50 rounded-lg shadow px-4 py-4 text-gray-600">
                    --
                  </div>
                </div>
              )}
            </div>
          </div>


          {/* Middle: Student Image */}
          <div className="w-1/3 pl-12 pr-12 items-center justify-center">
            <div
              className="bg-white border border-gray-300 p-4 rounded-2xl shadow-md flex items-center justify-center min-h-[320px]"
              style={{ backgroundColor: 'rgba(16, 52, 182, 0.25)' }}
            >
              <img
                src={scannedStudent?.student?.photo || "/assets/images/user.png"}
                alt="Student"
                className="w-60 h-60 rounded-2xl object-cover border-4 border-white shadow-md"
              />
            </div>
          </div>

          {/* Right: Another Image (e.g., logo or animation) */}
          <div className="w-1/3 pl-30">
            <div
              className="bg-white border border-gray-300 p-6 rounded-2xl shadow-md flex flex-col justify-center space-y-4 min-h-[320px]"
              style={{ backgroundColor: 'rgba(16, 52, 182, 0.25)' }}
            >
              {/* Students Total */}
              <div className="bg-white bg-opacity-60 p-2 rounded-xl shadow text-center">
                <div className="text-lg text-gray-600 font-medium">Students</div>
                <div className="text-2xl font-bold text-gray-800">{totalStudents}</div>
              </div>

              {/* Logged In */}
              <div className="bg-white bg-opacity-60 p-2 rounded-xl shadow text-center">
                <div className="text-lg text-green-600 font-medium">Logged In</div>
                <div className="text-2xl font-bold text-green-700">{loggedInCount}</div>
              </div>

              {/* Logged Out */}
              <div className="bg-white bg-opacity-60 p-2 rounded-xl shadow text-center">
                <div className="text-lg text-red-600 font-medium">Logged Out</div>
                <div className="text-2xl font-bold text-red-700">{loggedOutCount}</div>
              </div>
            </div>
          </div>

        </div>
      </div>


      {/* Hidden Input */}
      <input
        ref={inputRef}
        type="text"
        value={code}
        onChange={(e) => setCode(e.target.value)}
        onKeyDown={handleKeyDown}
        autoFocus
        className="opacity-0 absolute"
      />

      {/* Footer */}
      <div className="bg-white px-6 py-4 border-t-4 border-t-gray-400 shadow-2xl">
        <div className="flex justify-between items-center text-sm text-gray-500">
          <p>© 2025 ALANGALANG NATIONAL HIGH SCHOOL. All rights reserved.</p>
          <p>Developed by: <span className="font-medium text-gray-700">Cesar M. Celestial Jr.</span></p>
        </div>
      </div>

    </div>
  </div>
);



};

export default AttendanceScannerPage;
