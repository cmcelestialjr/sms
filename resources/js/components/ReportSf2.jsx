import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Layout from "./Layout";
import { Edit, Plus, ReplyAll } from "lucide-react";
import toastr from 'toastr';
import Swal from "sweetalert2";
import 'toastr/build/toastr.min.css';
import "react-datepicker/dist/react-datepicker.css";

const ReportSf2 = ({setActiveTab}) => {
    const userRole = localStorage.getItem("userRole");
    const [searchTeacherTerm, setSearchTeacherTerm] = useState("");
    const [teacher, setTeacher] = useState([]);
    const [teacherSuggestions, setTeacherSuggestions] = useState([]);
    const [showPdf, setShowPdf] = useState(false);
    const [pdfData, setPdfData] = useState('');
    const [month, setMonth] = useState(new Date().getMonth() + 1);
    const [year, setYear] = useState(new Date().getFullYear());
    const currentYear = new Date().getFullYear();
    const startYear = 2025;
    const maxYear = Math.max(startYear, currentYear);
    const minYear = Math.min(startYear, currentYear);
    const years = [];
    for(let y = maxYear; y >= minYear; y--) {
        years.push(y);
    }
    const months = [
        { value: 1, label: "January" },
        { value: 2, label: "February" },
        { value: 3, label: "March" },
        { value: 4, label: "April" },
        { value: 5, label: "May" },
        { value: 6, label: "June" },
        { value: 7, label: "July" },
        { value: 8, label: "August" },
        { value: 9, label: "September" },
        { value: 10, label: "October" },
        { value: 11, label: "November" },
        { value: 12, label: "December" },
    ];

    useEffect(() => {
        if(userRole == 3){
            handlePdf(userRole, [], year, month);
        }else{
            handlePdf(userRole, teacher, year, month);
        }
    }, [teacher, year, month]);

    useEffect(() => {
        if (searchTeacherTerm.length < 1) return;
        const delayDebounce = setTimeout(async () => {
            try {
                const authToken = localStorage.getItem("token");
                const response = await axios.get('/api/teachers/search', {
                    params: { search : searchTeacherTerm },
                    headers: { Authorization: `Bearer ${authToken}` },
                });
                setTeacherSuggestions(response.data);
            } catch (err) {
                // console.error("Failed to fetch suggestions", err);
            }
        }, 150);
        return () => clearTimeout(delayDebounce);
    }, [searchTeacherTerm]);

    const handlePdf = async (userRole, teacher, year, month) => { 
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.post(`/api/reports/sf2`, 
                    { userRole, teacher, year, month }, 
                    {
                        headers: {
                            'Content-Type': 'application/json',
                            Authorization: `Bearer ${authToken}`,
                        },
                    }
            );

            if (response.data.pdf) {
                setPdfData(response.data.pdf); 
                setShowPdf(true); 
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    return (
        <div>
            <div className="flex flex-col md:flex-row gap-4">
                <button
                    onClick={() => setActiveTab('lists')}
                    className="flex items-center gap-2 text-xs bg-blue-600 text-white px-2 py-1 rounded-lg shadow hover:bg-blue-700 transition cursor-pointer"
                >
                    <ReplyAll size={12}/> Back
                </button>
                <h4 className="text-lg font-medium">School Form 2 (SF2) Daily Attendance Report of Learners</h4>            
            </div>
            {userRole!=3 &&
                <div className="flex flex-col">
                    <label className="text-sm font-medium text-gray-700 mb-1 mt-4">Teacher</label>
                    <div className="flex items-center space-x-4">
                        {/* Teacher Search Input */}
                        <input
                            type="text"
                            placeholder="Search teacher"
                            value={searchTeacherTerm}
                            onChange={(e) => setSearchTeacherTerm(e.target.value)}
                            className="p-3 border border-gray-300 rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    {/* Teacher Suggestions */}
                    {teacherSuggestions.length > 0 && (
                        <ul className="border border-gray-300 rounded-lg mt-1 max-h-48 overflow-y-auto bg-white z-10 relative">
                            {teacherSuggestions.map((teacher) => (
                                <li
                                    key={teacher.user_id}
                                    onClick={() => {
                                        setTeacher(teacher);
                                        setSearchTeacherTerm(`${teacher.lastname}, ${teacher.firstname} ${teacher.extname || ''} ${teacher.middlename || ''}`);
                                        setTeacherSuggestions([]);
                                    }}
                                    className="p-2 hover:bg-gray-100 cursor-pointer"
                                >
                                    {teacher.lastname}, {teacher.firstname} {teacher.extname || ''} {teacher.middlename || ''}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            }

            <div className="grid grid-cols-2 gap-4 mb-4">
                <div className="flex flex-col">
                    <label htmlFor="year" className="mb-1 font-semibold text-gray-700">Year</label>
                    <select
                        id="year"
                        value={year}
                        onChange={e => setYear(Number(e.target.value))}
                        className="border border-gray-300 rounded-md px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    {years.map(y => (
                        <option key={y} value={y}>{y}</option>
                    ))}
                    </select>
                </div>
                <div className="flex flex-col">
                    <label htmlFor="month" className="mb-1 font-semibold text-gray-700">Month</label>
                    <select
                        id="month"
                        value={month}
                        onChange={e => setMonth(Number(e.target.value))}
                        className="border border-gray-300 rounded-md px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    {months.map(m => (
                        <option key={m.value} value={m.value}>{m.label}</option>
                    ))}
                    </select>
                </div>
            </div>

            {showPdf && (
                <div className="mt-4">
                    <iframe
                        src={`data:application/pdf;base64,${pdfData}`}
                        width="100%"
                        height="600px"
                        title="SF2 PDF"
                        allowfullscreen="false"
                    />
                </div>
            )}
        </div>
    );
};

export default ReportSf2;
