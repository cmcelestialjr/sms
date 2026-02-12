import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Layout from "./Layout";
import { Check, Edit, Plus, X, } from "lucide-react";
import toastr from 'toastr';
import Swal from "sweetalert2";
import 'toastr/build/toastr.min.css';
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";
import AttendanceSelectedDay from "./AttendanceSelectedDay";

const AttendanceCalendar = () => {
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

    const [year, setYear] = useState(new Date().getFullYear());
    const [month, setMonth] = useState(new Date().getMonth() + 1);
    const [students, setStudents] = useState([]);
    const [search, setSearch] = useState("");
    const [selectedDay, setSelectedDay] = useState([]);
    const [schoolYears, setSchoolYears] = useState([]);
    const [selectedSchoolYear, setSelectedSchoolYear] = useState("");
    const [formModal, setFormModal] = useState(false);
    const [form, setForm] = useState({
        student_id: "",
        status: "absent",
        date: "",
        is_late: 0,
        is_undertime: 0,
        is_excused: 0,
        attendance_id: "",
        remarks: "",
    });

    useEffect(() => {
        fetchSchoolYears()
    }, []);

    useEffect(() => {
        fetchAttendances();
    }, [search, month, year, selectedSchoolYear]);

    const fetchAttendances = async () => {
        try {            
            const authToken = localStorage.getItem("token");
            const response = await axios.get(`/api/attendances`, {
                params: {
                    search: search,
                    month: month,
                    year: year,
                    schoolYear: selectedSchoolYear
                },
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setStudents(response.data.data);
        } catch (error) {
            // console.error("Error fetching:", error);
        }
    };

    const fetchSchoolYears = async () => {
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.get('/api/schoolYears/lists', {
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setSchoolYears(response.data.data);
            setSelectedSchoolYear(response.data.data[0]?.id || "");
        } catch (error) {
            toastr.error('Failed to load school years');
            setSchoolYears([]);
            setSelectedSchoolYear("");
        }
    };

    const handleSearch = (e) => {
        setSearch(e.target.value);
    };

    const getDaysInMonth = (year, month) => {
        return new Date(year, month, 0).getDate(); 
    };

    const generateDayColumns = (daysInMonth) => {
        const days = [];
        for (let i = 1; i <= daysInMonth; i++) {
            const bgColor = isWeekEnd(i, month - 1, year) ? 'bg-blue-500 text-white' : '';
            days.push(<th key={i} className={`${bgColor} px-3 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider border-l border-gray-200`}>{i}</th>);
        }
        return days;
    };

    function isWeekEnd(date, month = 1, year = 2025) {
        const weekEnd = new Date(year, month, date).getDay();
        return  weekEnd === 0 || weekEnd === 6; 
    }

    const daysInMonth = getDaysInMonth(year, month);

    const handleDailyClick = (student_id, date, attendance) => {
        setForm({
            student_id,
            date,
            attendance_id: attendance?.id ?? null,
            status: attendance?.id ? "present" : "absent",
            is_late: attendance?.is_late ?? 0,
            is_undertime: attendance?.is_undertime ?? 0,
            is_excused: attendance?.is_excused ?? 0,
            remarks: attendance?.remarks ?? ""
        });

        setFormModal(true);
    };

    return (
        <div className="border border-gray-300 shadow-xl rounded-lg p-6 bg-white mx-auto w-full mt-4">
            <div className="grid grid-cols-3 sm:grid-cols-3 gap-4 items-end mb-4">
                <div className="flex flex-col">
                    <label htmlFor="schoolYear" className="mb-1 font-semibold text-gray-700">School Year</label>
                    <select
                        id="schoolYear"
                        value={selectedSchoolYear}
                        onChange={e => setSelectedSchoolYear(Number(e.target.value))}
                        className="border border-gray-300 rounded-md px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        {schoolYears.map((sy) => (
                            <option key={sy.id} value={sy.id}>
                                S.Y. {sy.sy_from}-{sy.sy_to}
                            </option>
                        ))}
                    </select>
                </div>
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

            {/* Search Input */}
            <div className="mb-4">
                <div className="flex items-center gap-4 mb-6">
                    <input
                        type="text"
                        placeholder="Search Student..."
                        value={search}
                        onChange={handleSearch}
                        className="flex-grow border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>
            </div>

            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200 border border-gray-200 shadow-lg">
                    <thead className="bg-gray-50">
                        {/* Header Row 1 - Main Categories */}
                        <tr>
                            <th className="px-3 py-3 text-xs font-medium text-gray-700 uppercase tracking-wider border-r border-gray-200" rowSpan="2">#</th>
                            <th className="px-3 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-r border-gray-200" rowSpan="2">Name</th>
                            
                            {/* DATE Column Header */}
                            <th colSpan={daysInMonth} className="px-3 py-2 text-center text-sm font-semibold text-gray-800 bg-blue-50 border-x border-gray-200">
                                Attendance Dates (Day.Hour.Minute)
                            </th>
                            
                            {/* Summary Columns */}
                            <th className="px-3 py-3 text-center text-xs font-medium text-gray-700 tracking-wider border-l border-gray-200" rowSpan="2">Present</th>
                            <th className="px-3 py-3 text-center text-xs font-medium text-gray-700 tracking-wider border-l border-gray-200" rowSpan="2">Absent</th>
                            <th className="px-3 py-3 text-center text-xs font-medium text-gray-700 tracking-wider border-l border-gray-200" rowSpan="2">Total</th>
                        </tr>
                        
                        {/* Header Row 2 - Day Numbers */}
                        <tr className="border-b border-gray-300">
                            {/* Dynamically generate day columns (1-31 or 1-30 depending on the month) */}
                            {generateDayColumns(daysInMonth)}
                        </tr>
                    </thead>

                    <tbody className="bg-white divide-y divide-gray-200">
                        {students.map((student, index) => {
                        
                            const attendanceMap = (student.attendance_daily_summary || []).reduce((acc, att) => {
                                acc[new Date(att.date).toLocaleDateString('en-CA')] = att;
                                return acc;
                            }, {});

                            const absenceMap = (student.absences || []).reduce((acc, att) => {
                                acc[new Date(att.date).toLocaleDateString('en-CA')] = att;
                                return acc;
                            }, {});

                            const paddedMonth = String(month).padStart(2, '0');

                            let totalDays = 0;
                            let totalPresent = 0;
                            let totalAbsence = 0;

                            Array.from({ length: daysInMonth }).forEach((_, dayIndex) => {
                                const day = dayIndex + 1;
                                const paddedDay = String(day).padStart(2, '0');
                                const fullDate = `${year}-${paddedMonth}-${paddedDay}`;
                                const attendance = attendanceMap[fullDate];
                                const absence = absenceMap[fullDate];

                                if (attendance) {
                                    totalDays += 1;
                                    totalPresent += 1;
                                } else if(absence) {
                                    totalDays += 1;
                                    totalAbsence += 1;
                                }
                            });

                            return (
                                <tr key={student.id} className="hover:bg-gray-50 transition duration-150 ease-in-out">
                            
                                    <td className="px-3 py-2 text-sm text-center text-gray-500 font-medium border-r border-gray-200">{index + 1}</td>
                                    <td className="px-3 py-2 text-sm font-medium text-gray-900 whitespace-nowrap border-r border-gray-200">
                                        {student.lastname}, {student.firstname}
                                        {student.extname ? ` ${student.extname}` : ''} 
                                        {student.middlename ? ` ${student.middlename.charAt(0)}.` : ''}    
                                    </td>

                                    {Array.from({ length: daysInMonth }).map((_, dayIndex) => {
                                        const day = dayIndex + 1;
                                        const paddedDay = String(day).padStart(2, '0');
                                        
                                        const fullDate = `${year}-${paddedMonth}-${paddedDay}`;
                                        
                                        const attendance = attendanceMap[fullDate];
                                        const absence = absenceMap[fullDate];

                                        const hasFullAttendance = attendance;

                                        const hasFullAbsence = absence;
                                        
                                        const hasAnyAttendance = !!attendance; 
                                        
                                        let cellContent = '';
                                        let cellColorClass = 'text-gray-700';                                        

                                        if (hasFullAttendance) {
                                            cellContent = (
                                                <Check className="h-4 w-4 text-green-500 mx-auto" />
                                            );

                                        } else if(hasFullAbsence) {
                                            cellContent = isWeekEnd(paddedDay, paddedMonth - 1, year) ? '' : <X className="h-4 w-4 text-red-500 mx-auto" />;
                                        } else {
                                            cellContent = '';
                                        }

                                        return (
                                            <td 
                                                key={dayIndex} 
                                                className={`text-center text-xs px-1 py-1 cursor-pointer border-l border-gray-200 hover:bg-gray-200 transition duration-100 ${cellColorClass}`}
                                                onClick={() => handleDailyClick(student.id, fullDate, attendance)}
                                            >
                                                {cellContent}
                                            </td>
                                        );
                                    })}
                                    
                                    <td className="px-3 py-2 text-sm text-center font-semibold text-gray-700 border-l border-gray-200">{totalPresent > 0 ? totalPresent : ''}</td>
                                    <td className="px-3 py-2 text-sm text-center font-bold text-blue-600 border-l border-gray-200">{totalAbsence > 0 ? totalAbsence : ''}</td>
                                    <td className="px-3 py-2 text-sm text-center font-bold text-blue-600 border-l border-gray-200">{totalDays > 0 ? totalDays : ''}</td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>

            <AttendanceSelectedDay
                formModal={formModal}
                setFormModal={setFormModal}
                form={form}
                setForm={setForm}
                fetchAttendances={fetchAttendances}
            />
        </div>
    );

};

export default AttendanceCalendar;