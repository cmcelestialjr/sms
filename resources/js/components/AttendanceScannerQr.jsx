import React, { useState, useRef, useEffect } from 'react';
import { useParams } from 'react-router-dom';
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
                    headers: { 'X-CSRF-Token': csrfToken }
                });

                if (response.data.success) {
                    const scannedStudent = response.data.student;
                    setScannedStudent(scannedStudent);
                    resetScanTimeout();
                } else {
                    toastr.error(response.data.message, "Error");
                }
            } catch (error) {
                toastr.error(error.response?.data?.message || 'Error recording attendance');
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
        return new Date(date).toLocaleDateString('en-US', { month: 'long', day: '2-digit', year: 'numeric' });
    };

    const formatTime = (date) => {
        return new Date(date).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    };

    const formatDay = (date) => new Date(date).toLocaleDateString('en-US', { weekday: 'long' });

    return (
        <div className="min-h-screen flex flex-col items-center bg-slate-50 p-4 md:p-6 lg:p-8 font-sans selection:bg-blue-200">
        
            {/* === HEADER SECTION === */}
            <header className="w-full max-w-7xl mb-6 md:mb-8 bg-white rounded-2xl shadow-sm border border-gray-100 p-5 md:p-6 transition-all">
                <div className="flex flex-col md:flex-row md:justify-between items-center gap-4 md:gap-0">    
                    
                    {/* Logos */}
                    <div className="flex items-center gap-4 md:gap-6 flex-shrink-0">
                        <div className="p-2 bg-slate-50 rounded-xl border border-gray-100">
                            <img src="/images/clstldev2-removebg.png" alt="CDEV Logo" className="w-12 h-12 md:w-16 md:h-16 object-contain" />
                        </div>
                        <div className="h-10 w-px bg-gray-200 hidden md:block"></div>
                        <div className="p-2 bg-slate-50 rounded-xl border border-gray-100">
                            <img src="/images/lnu/lnu_logo.png" alt="School Logo" className="w-12 h-12 md:w-16 md:h-16 object-contain" />
                        </div>
                    </div>

                    {/* School and Time Info */}
                    <div className="text-center md:text-right flex-grow">
                        <h1 className="text-xl md:text-2xl lg:text-3xl font-extrabold text-slate-800 tracking-tight leading-none">
                            LEYTE NORMAL UNIVERSITY
                        </h1>
                        <h2 className="text-sm md:text-base lg:text-lg font-bold text-blue-600 tracking-widest uppercase mt-1 md:mt-1.5">
                            Integrated Laboratory School
                        </h2>
                        <div className="mt-2.5 flex flex-col md:flex-row items-center md:justify-end gap-1 md:gap-3">
                            <p className="text-sm md:text-base font-semibold text-slate-500">
                                {formatDay(timeNow)}, {formatDate(timeNow)}
                            </p>
                            <span className="hidden md:inline text-slate-300">&bull;</span>
                            <p className="text-sm md:text-base font-bold text-blue-600 bg-blue-50 px-3 py-0.5 rounded-full">
                                {formatTime(timeNow)}
                            </p>
                        </div>
                    </div>
                </div>
            </header>
            
            {/* === MAIN CONTENT AREA === */}
            <main className="w-full max-w-7xl flex flex-col lg:flex-row gap-6 md:gap-8 items-stretch flex-grow">
                
                {/* --- LEFT COLUMN: Student Profile Card --- */}
                <section className="flex-1 flex flex-col min-w-[50%]">
                    <div className="bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden flex flex-col h-full relative transition-all duration-300">
                        
                        {/* Card Banner Background */}
                        <div className="h-28 md:h-36 bg-gradient-to-r from-blue-900 to-blue-700 w-full absolute top-0 left-0 z-0">
                            <div className="w-full h-full opacity-10 bg-[url('/images/pattern-dots.svg')]"></div>
                        </div>

                        {/* Card Content */}
                        <div className="px-6 md:px-10 pb-8 z-10 relative flex-grow flex flex-col mt-16 md:mt-20">
                            
                            {scannedStudent ? (
                                <div className="animate-fade-in-up flex flex-col h-full">
                                    {/* Avatar & Header */}
                                    <div className="flex flex-col sm:flex-row items-center sm:items-end gap-4 md:gap-6 border-b border-gray-100 pb-6 mb-6">
                                        <div className="relative">
                                            <img
                                                src={scannedStudent.student.photo || "/images/cdevqr.png"}
                                                alt="Student Profile"
                                                className="w-28 h-28 md:w-36 md:h-36 rounded-2xl border-4 border-white shadow-md bg-slate-50 object-cover"
                                            />
                                            {/* Success Badge */}
                                            <div className="absolute -bottom-2 -right-2 bg-green-500 text-white p-1.5 rounded-full border-4 border-white shadow-sm">
                                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M5 13l4 4L19 7"></path></svg>
                                            </div>
                                        </div>
                                        
                                        <div className="text-center sm:text-left mb-1">
                                            <h2 className="text-xs font-bold text-blue-600 uppercase tracking-wider mb-1">Student Logged</h2>
                                            <h3 className="text-2xl md:text-3xl font-extrabold text-slate-800 leading-tight">
                                                {scannedStudent.student.firstname}{" "}
                                                {scannedStudent.student.middlename?.trim() ? scannedStudent.student.middlename.trim().charAt(0) + "." : ""}{" "}
                                                {scannedStudent.student.lastname}
                                            </h3>
                                            {scannedStudent.student.extname && (
                                                <span className="text-lg font-semibold text-slate-500 ml-1">{scannedStudent.student.extname}</span>
                                            )}
                                        </div>
                                    </div>

                                    {/* Student Details Grid */}
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6 mb-8 flex-grow">
                                        <div className="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                            <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Grade Level</p>
                                            <p className="text-lg font-bold text-slate-700">{scannedStudent.student.grade || "N/A"}</p>
                                        </div>
                                        <div className="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                            <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Section</p>
                                            <p className="text-lg font-bold text-slate-700">{scannedStudent.student.section || "N/A"}</p>
                                        </div>
                                    </div>

                                    {/* Scan Timestamp */}
                                    <div className="mt-auto bg-amber-50 border border-amber-100 rounded-xl p-4 flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="p-2 bg-amber-100 rounded-lg text-amber-600">
                                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </div>
                                            <div>
                                                <p className="text-xs font-bold text-amber-600/80 uppercase">Time Recorded</p>
                                                <p className="text-base font-bold text-amber-700">{formatTime(scannedStudent.scanned_at)}</p>
                                            </div>
                                        </div>
                                        <span className="text-sm font-bold text-green-600 bg-green-100 px-3 py-1 rounded-full">Success</span>
                                    </div>
                                </div>
                            ) : (
                                /* Empty State */
                                <div className="flex flex-col items-center justify-center h-full text-center py-10 mt-6">
                                    <div className="w-32 h-32 bg-slate-100 rounded-full flex items-center justify-center mb-6 shadow-inner relative">
                                        <svg className="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        {/* Pulse indicator */}
                                        <span className="absolute top-2 right-2 flex h-4 w-4">
                                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                            <span className="relative inline-flex rounded-full h-4 w-4 bg-blue-500 border-2 border-white"></span>
                                        </span>
                                    </div>
                                    <h3 className="text-2xl font-bold text-slate-700 mb-2">Awaiting Scan</h3>
                                    <p className="text-slate-500 max-w-xs mx-auto">
                                        Please present a student QR code to the camera to record attendance.
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </section>
                
                {/* --- RIGHT COLUMN: Scanner Interface --- */}
                <section className="flex-1 flex flex-col justify-center"> 
                    <div className="w-full h-full min-h-[400px] max-w-2xl mx-auto rounded-3xl shadow-xl bg-slate-900 border border-slate-800 relative overflow-hidden flex flex-col">
                        
                        {/* Top bar of scanner */}
                        <div className="absolute top-0 left-0 right-0 z-20 flex justify-between items-center p-4 bg-gradient-to-b from-black/60 to-transparent">
                            <div className="flex items-center gap-2 bg-black/40 backdrop-blur-md px-3 py-1.5 rounded-full border border-white/10">
                                <div className="w-2.5 h-2.5 rounded-full bg-green-400 animate-pulse"></div>
                                <span className="text-xs font-semibold text-white tracking-wide">Scanner Active</span>
                            </div>
                        </div>

                        {/* Scanner Component */}
                        <div className="flex-grow relative">
                            <BarcodeScanner
                                onUpdate={(err, result) => {
                                    if (result) handleScan(result.text || result);
                                }}
                                videoConstraints={{ facingMode: "user" }}
                                className="w-full h-full object-cover absolute inset-0" 
                            />
                            
                            {/* Overlay UI */}
                            <div className="absolute inset-0 pointer-events-none z-10 flex flex-col items-center justify-center">
                                {/* Semi-transparent mask */}
                                <div className="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px]" />

                                {/* Viewfinder Frame */}
                                <div className="relative w-[70%] max-w-[300px] aspect-square rounded-2xl border border-white/20 shadow-[0_0_0_9999px_rgba(0,0,0,0.5)] overflow-hidden flex items-center justify-center">
                                    
                                    {/* Corner Accents */}
                                    <div className="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-blue-500 rounded-tl-2xl"></div>
                                    <div className="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-blue-500 rounded-tr-2xl"></div>
                                    <div className="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-blue-500 rounded-bl-2xl"></div>
                                    <div className="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-blue-500 rounded-br-2xl"></div>
                                    
                                    {/* Animated Laser */}
                                    <div className="absolute top-1/2 left-0 w-full h-0.5 bg-blue-500 shadow-[0_0_12px_2px_rgba(59,130,246,0.8)] animate-pulse"></div>
                                </div>
                                
                                {/* Instructions */}
                                <div className="absolute bottom-8 left-0 right-0 flex justify-center">
                                    <div className="bg-black/60 backdrop-blur-md px-6 py-3 rounded-2xl border border-white/10 shadow-xl">
                                        <p className="text-sm font-medium text-slate-200">
                                            Align QR code within the frame
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

            </main>

            {/* === FOOTER === */}
            <footer className="w-full max-w-7xl mt-8 pt-6 border-t border-gray-200 text-center flex flex-col sm:flex-row justify-between items-center gap-2">
                <p className="text-sm text-slate-500 font-medium">
                    &copy; {new Date().getFullYear()} Leyte Normal University
                </p>
                <p className="text-xs text-slate-400">
                    System powered by <span className="font-semibold text-slate-500">CDEV IT Solutions</span>
                </p>
            </footer>
        </div>
    );
};

export default AttendanceScannerQr;