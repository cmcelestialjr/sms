import React, { useState, useRef, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { Scanner } from '@yudiel/react-qr-scanner';
import BarcodeScanner from "react-qr-barcode-scanner";
import axios from 'axios';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

const AttendanceScannerQr = () => {
    const { id } = useParams();
    const [scanned, setScanned] = useState(null);
    const [scannedStudent, setScannedStudent] = useState(null);
    const [canScan, setCanScan] = useState(true);
    const [timeNow, setTimeNow] = useState(new Date());
    const [data, setData] = useState('No result');
    const scanTimeoutRef = useRef(null);

    useEffect(() => {
        const timer = setInterval(() => setTimeNow(new Date()), 1000);
        return () => clearInterval(timer);
    }, []);

    useEffect(() => {
        return () => {
            if (scanTimeoutRef.current) {
                clearTimeout(scanTimeoutRef.current);
            }
        };
    }, []);

    const handleScan = async (data) => {
        if (data && canScan) {
        setScanned(data);
        setCanScan(false);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await axios.post('/api/attendance/scan/qr', {
                id: id,
                code: data,
                api_key: 'SMS@CelestialDev01',
            }, {
            headers: {
                'X-CSRF-Token': csrfToken,
                }
            });
            if (response.data.success) {
                const scannedStudent = response.data.student;
                setScannedStudent(scannedStudent);
                resetScanTimeout();
            }else{
                toastr.error(response.data.message, "Error");
            }
        } catch (error) {
            toastr.error(
            error.response?.data?.message || 'Error recording attendance'
            );
        }

        setTimeout(() => {
            setCanScan(true);
            setScanned(null);
        }, 3000);
        }
    };

    const resetScanTimeout = () => {
        if (scanTimeoutRef.current) {
            clearTimeout(scanTimeoutRef.current);
        }

        scanTimeoutRef.current = setTimeout(() => {
            setScannedStudent(null);
        }, 30000); // 30 seconds
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

    return (
        <div className="min-h-screen flex flex-col items-center bg-gray-100 p-4 font-sans">
        
            {/* Header Section (Dark Blue Accent) */}
            <header className="w-full max-w-6xl mb-6">
                <div className="flex flex-col items-center sm:flex-row sm:justify-between sm:items-center sm:space-x-4">    
                    {/* Logos */}
                    <div className="flex justify-center sm:justify-start gap-4 mb-3 sm:mb-0 flex-shrink-0">
                        <img 
                            src="/images/clstldev2-removebg.png" 
                            alt="CDEV IT Solutions Logo" 
                            className="w-12 h-12 sm:w-16 sm:h-16 object-contain" 
                        />
                        <img 
                            src="/images/lnu/lnu_logo.png" 
                            alt="School Logo 2" 
                            className="w-12 h-12 sm:w-16 sm:h-16 object-contain" 
                        />
                    </div>

                    {/* School and Time Info */}
                    <div className="w-full text-center sm:text-left">
                        <h1 className="text-xl sm:text-2xl font-extrabold text-blue-900 leading-snug">
                            LEYTE NORMAL UNIVERSITY
                        </h1>
                        <div className="mt-1">
                            {/* Time/Date in Gold/Amber */}
                            <p className="text-sm sm:text-base text-amber-600 font-semibold">
                                {/* Use actual component functions for real data */}
                                {formatDay(timeNow)}, {formatDate(timeNow)} &bull; {formatTime(timeNow)}
                            </p>
                        </div>
                    </div>
                </div>
            </header>
            
            {/* Main Content Area */}
            <main className="w-full max-w-6xl flex flex-col md:flex-row md:space-x-6 md:items-start">
                
                {/* === LEFT COLUMN: Student Information === */}
                <section className="flex-1 mb-6 md:mb-0 flex flex-col items-center md:items-start space-y-2">
                    
                    {/* Student Information Display Block (Dark Blue Border) */}
                    <div className="w-full max-w-sm md:max-w-none p-6 bg-white rounded-xl shadow-2xl border-t-4 border-blue-900">
                        <h2 className="text-lg font-bold text-gray-700 mb-4 border-b pb-2">Student Attendance</h2>
                        
                        <div className="flex flex-row items-center gap-4">
                            {/* Student Photo */}
                            <img
                                src={scannedStudent?.student?.photo || "/images/cdevqr.png"}
                                alt="Student Photo"
                                className="w-24 h-24 sm:w-28 sm:h-28 rounded-lg object-cover border-4 border-white shadow-lg flex-shrink-0"
                            />

                            {/* Student Details */}
                            <div className="text-gray-800 space-y-1 flex-grow">
                                {scannedStudent ? (
                                    <>
                                        {/* Name in Dark Blue */}
                                        <h3 className="text-xl font-extrabold text-blue-900 leading-snug">
                                            {scannedStudent.student.firstname}{" "}
                                            {scannedStudent.student.middlename?.trim()
                                                ? scannedStudent.student.middlename.trim().charAt(0) + "." : ""}{" "}
                                            {scannedStudent.student.lastname} {scannedStudent.student.extname}
                                        </h3>
                                        <div className="text-base text-gray-600 space-y-0.5">
                                            <p><strong>Grade:</strong> {scannedStudent.student.grade}</p>
                                            <p><strong>Section:</strong> {scannedStudent.student.section}</p>
                                        </div>
                                        {/* Time Scanned in Gold/Amber */}
                                        <p className="text-sm text-amber-600 font-bold pt-2">
                                            Time Scanned: {formatTime(scannedStudent.scanned_at)}
                                        </p>
                                    </>
                                ) : (
                                    <>
                                        <h3 className="text-xl font-semibold text-gray-800">
                                            No Student Scanned
                                        </h3>
                                        <p className="text-base text-gray-500 italic">
                                            Awaiting student information...
                                        </p>
                                        <div className="text-sm text-gray-400 pt-2">
                                            --:--
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Additional Status/Info */}
                    <div className="w-full max-w-sm md:max-w-none p-4 bg-white rounded-xl shadow-lg text-sm text-gray-600">
                        <p className="font-semibold text-gray-700">System Status:</p>
                        <p className='text-xs'>Scanner is online and ready for attendance logging.</p>
                    </div>
                    
                </section>
                
                {/* === RIGHT COLUMN: Barcode Scanner === */}
                <section className="flex-1 flex flex-col items-center space-y-4"> 
                    {/* Barcode Scanner Area (Gold Border) */}
                    <div 
                        className="w-full max-w-xs md:max-w-md overflow-hidden rounded-xl shadow-2xl border-4 border-blue-900 bg-gray-200 relative"
                        // Using a simple height style for 70% of the available flex space
                        style={{ height: '60%' }}
                    >
                        {/* <div className="absolute inset-0 flex items-center justify-center bg-gray-200 z-0">
                            <img 
                                src="/images/cdevqr.png"
                                alt="QR Code Scanner Ready" 
                                className="w-32 h-32 opacity-75"
                            />
                            <p className='absolute bottom-5 text-gray-500 font-bold text-sm'>
                                Point Camera at QR Code
                            </p>
                        </div> */}
                        <BarcodeScanner
                            onUpdate={(err, result) => {
                                if (result) handleScan(result);
                                else setData("Not Found");
                            }}
                            videoConstraints={{ facingMode: "user" }}
                            className="w-full h-full object-cover absolute inset-0" 
                        />
                        <div 
    // This is the main container for all overlay elements
    className="absolute inset-0 pointer-events-none z-10"
>
    {/* 1. Full-Screen Mask (Semi-transparent black) */}
    <div 
        className="absolute inset-0 bg-black opacity-50" 
    />

    {/* 2. Positioning Container for the Focus Frame (Centers the frame) */}
    <div className="absolute inset-0 flex items-center justify-center">
        
        {/* Frame Visual Guide (W-4/5 H-4/5 is the scan zone) */}
        <div className="w-4/5 h-4/5 relative">
            
            {/* Corner Markers (Top-Left) */}
            <div className="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-blue-400 rounded-tl-lg"></div>
            {/* Corner Markers (Top-Right) */}
            <div className="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-blue-400 rounded-tr-lg"></div>
            {/* Corner Markers (Bottom-Left) */}
            <div className="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-blue-400 rounded-bl-lg"></div>
            {/* Corner Markers (Bottom-Right) */}
            <div className="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-blue-400 rounded-br-lg"></div>
            
            {/* Animated Scanning Line (centered within the guide) */}
            <div className="absolute inset-0 flex items-center justify-center">
                {/* Ensure you have the 'animate-scan-line' keyframes defined in your CSS */}
                <div className="absolute w-full h-2 bg-blue-400 rounded-full animate-scan-line filter blur-sm"></div>
            </div>
            
        </div>
    </div>
    
    {/* 3. Instruction Text (Anchored to the absolute bottom) */}
    <div className="absolute bottom-4 left-0 right-0 text-center">
        <p className="text-sm font-bold text-white py-1 px-4 inline-block bg-blue-900 bg-opacity-80 rounded shadow-lg">
            <span className="text-amber-400">Scan QR Code</span> at the camera
        </p>
    </div>
</div>
                        {/* <div style={{ width: '100%', height: '400px' }}>
                        <Scanner
                            onScan={(result) => handleScan(result)}                    
                            onError={(err) => {
                                toastr.error('Camera error');
                            }}
                            facingMode="user"
                            videoConstraints={{
                                facingMode: "user"
                            }}
                            style={{
                                width: '70%',
                                height: 'auto',
                            }}
                        />
                        </div> */}
                    </div>
                </section>

            </main>

            <div className="flex-grow"></div>
            
            {/* Footer (Dark Blue Text) */}
            <footer className="w-full max-w-6xl mt-6 text-center text-xs text-gray-500 font-medium">
                &copy; {new Date().getFullYear()} Powered by CDEV IT Solutions
            </footer>
        </div>
    );

    // return (
    //     <div className="min-h-screen flex flex-col items-center justify-between bg-white px-2 py-2">
    //         <div className="flex flex-col sm:flex-row sm:items-start sm:space-x-2 w-full mb-1">
    //             {/* Logos */}
    //             <div className="flex justify-center sm:justify-start gap-6 mb-3 sm:mb-0 flex-shrink-0">
    //                 <img src="/images/clstldev2.png" alt="Logo 1" className="w-16 h-16 sm:w-20 sm:h-20 object-contain" />
    //                 <img src="/assets/logo/logo.jpg" alt="Logo 2" className="w-16 h-16 sm:w-20 sm:h-20 object-contain" />
    //             </div>

    //             {/* Text block with explicit full width on mobile */}
    //             <div className="w-full">
    //                 <div className="flex flex-col justify-center flex-1 w-full">
    //                     <h1
    //                         className="text-center sm:text-left text-lg sm:text-xl font-extrabold text-gray-800 leading-tight"
    //                         style={{
    //                             textShadow: '1px 1px 0 #ccc, -1px -1px 0 #fff',
    //                         }}
    //                     >
    //                         ALANGALANG NATIONAL HIGH SCHOOL
    //                     </h1>

    //                     <div className="flex flex-col sm:flex-row justify-between mt-1 gap-1 sm:gap-4 text-center sm:text-left">
    //                         <p className="text-md sm:text-lg text-blue-800 font-medium">
    //                             {formatDay(timeNow)}, {formatDate(timeNow)} — {formatTime(timeNow)}
    //                         </p>
    //                     </div>
    //                 </div>
    //             </div>
    //         </div>

    //         <div className="mx-auto w-[280px] sm:w-[320px] mt-0 mb-0">
    //             <BarcodeScanner
    //                 // width={500}
    //                 // height={500}
    //                 onUpdate={(err, result) => {
    //                 if (result) handleScan(result);
    //                 else setData("Not Found");
    //                 }}
    //                 videoConstraints={{
    //                     facingMode: "user",
    //                 }}
    //                 className="mx-auto w-[280px] sm:w-[320px]"
    //             />
    //             {/* <Scanner
    //                 onScan={(result) => handleScan(result)}                    
    //                 onError={(err) => {
    //                     toastr.error('Camera error');
    //                 }}
    //                 facingMode="user"
    //                 videoConstraints={{
    //                     facingMode: "user" // Make sure it's wrapped inside videoConstraints
    //                 }}
    //             /> */}
    //         </div>
            
    //         <div className="flex flex-row items-center gap-4 mt-1 w-full">
    //             <img
    //                 src={scannedStudent?.student?.photo || "/assets/images/user.png"}
    //                 alt="Student"
    //                 className="w-24 h-24 sm:w-32 sm:h-32 rounded-2xl object-cover border-4 border-white shadow-md"
    //             />

    //             {scannedStudent ? (
    //                 <div className="text-gray-800 text-left space-y-2 w-full">
    //                     <h2 className="text-sm sm:text-lg font-semibold bg-white bg-opacity-70 rounded-lg shadow px-2 py-2">
    //                         {scannedStudent.student.firstname}{" "}
    //                         {scannedStudent.student.middlename?.trim()
    //                             ? scannedStudent.student.middlename.trim().charAt(0) + "."
    //                             : ""}{" "}
    //                         {scannedStudent.student.lastname} {scannedStudent.student.extname}
    //                     </h2>

    //                     <h3 className="text-sm sm:text-lg bg-white bg-opacity-60 rounded-lg shadow px-2 py-2 text-gray-700 italic">
    //                         Grade: {scannedStudent.student.grade} <br />
    //                         Section: {scannedStudent.student.section}
    //                     </h3>

    //                     <div className="text-sm sm:text-lg bg-white bg-opacity-50 rounded-lg shadow px-2 py-2 text-gray-800">
    //                         {formatTime(scannedStudent.scanned_at)}
    //                     </div>
    //                 </div>
    //             ) : (
    //                 <div className="text-gray-800 text-left space-y-1 w-full">
    //                     <h2 className="text-sm sm:text-lg font-semibold bg-white bg-opacity-70 rounded-lg shadow px-2 py-2">
    //                         No Student Scanned
    //                     </h2>
    //                     <h3 className="text-sm sm:text-lg bg-white bg-opacity-60 rounded-lg shadow px-2 py-2 text-gray-700 italic">
    //                         Awaiting student info...
    //                     </h3>
    //                     <div className="text-sm sm:text-lg bg-white bg-opacity-50 rounded-lg shadow px-2 py-2 text-gray-600">
    //                         --
    //                     </div>
    //                 </div>
    //             )}
    //         </div>

    //         <div className="text-xs text-gray-500 mt-2 text-center">
    //             Ensure your QR code is visible and aligned properly on screen.
    //         </div>
    //     </div>
    // );
};

export default AttendanceScannerQr;
