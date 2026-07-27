import React, { useState, useEffect } from "react";
import axios from "axios";
import toastr from 'toastr';
import DatePicker from "react-datepicker";
import { Save, Users, Search, Move, UserCheck } from "lucide-react";
import "react-datepicker/dist/react-datepicker.css";
import 'toastr/build/toastr.min.css';

// Import the modal component
import AttendanceSelectedDay from "./AttendanceSelectedDay";

const AttendanceClassView = () => {
    // --- Basic Data State ---
    const [students, setStudents] = useState([]);
    const [schoolYears, setSchoolYears] = useState([]);
    const [selectedSchoolYear, setSelectedSchoolYear] = useState("");
    
    // --- Teacher Admin State ---
    const [teachers, setTeachers] = useState([]); 
    const [selectedTeacher, setSelectedTeacher] = useState(""); 
    const [userRole, setUserRole] = useState(null); 
    
    // --- Class Selections ---
    const [teacherClasses, setTeacherClasses] = useState([]);
    const [level, setLevel] = useState("");
    const [grade, setGrade] = useState("");
    const [section, setSection] = useState("");

    const LEVELS = ['Kinder', 'Elementary', 'Junior High School', 'Senior High School'];

    const [duration, setDuration] = useState([new Date(), new Date()]);
    const [startDate, endDate] = duration;
    
    const isSingleDate = !endDate || (startDate && endDate && startDate.getTime() === endDate.getTime());

    // --- Seating Plan & Search States ---
    const [gridRows, setGridRows] = useState(5);
    const [gridCols, setGridCols] = useState(10);
    const [seatAssignments, setSeatAssignments] = useState([]); 
    const [search, setSearch] = useState(""); 
    const [unassignedSearch, setUnassignedSearch] = useState(""); 

    // --- View Mode & Modal States ---
    const [viewMode, setViewMode] = useState("seating"); // 'seating' or 'attendance'
    const [formModal, setFormModal] = useState(false);
    const [form, setForm] = useState({
        student_id: "",
        student_name: "",
        status: "absent",
        date: "",
        is_late: 0,
        is_undertime: 0,
        is_excused: 0,
        attendance_id: "",
        remarks: "",
    });

    // Automatically revert to 'seating' mode if the user selects a multi-day range
    useEffect(() => {
        if (!isSingleDate && viewMode === "attendance") {
            setViewMode("seating");
        }
    }, [isSingleDate, viewMode]);

    // 1. Fetch School Years on Component Mount
    useEffect(() => {
        fetchSchoolYears();
    }, []);

    // 2. Fetch Teachers whenever the School Year changes
    useEffect(() => {
        if (selectedSchoolYear) {
            fetchTeachers(); 
        }
    }, [selectedSchoolYear]);

    // 3. Fetch Classes whenever School Year, Teacher, or Role changes
    useEffect(() => {
        if (userRole === null) return; 

        if (selectedSchoolYear) {
            if ((userRole === 1 || userRole === 2) && !selectedTeacher) {
                setTeacherClasses([]); 
                setLevel(""); 
                setGrade("");
                setSection("");
            } else {
                fetchTeacherClasses();
            }
        }
    }, [selectedSchoolYear, selectedTeacher, userRole]);

    // 4. Fetch Roster and Seat Plan with Debounce
    useEffect(() => {
        const isTeacherValid = (userRole !== 1 && userRole !== 2) || selectedTeacher !== "";

        const timer = setTimeout(() => {
            if (selectedSchoolYear && level && grade && section && startDate && isTeacherValid) {
                fetchClassData();
                fetchSeatPlan();
            } else {
                setStudents([]);
                setSeatAssignments([]);
            }
        }, 500); 

        return () => clearTimeout(timer);
    }, [selectedSchoolYear, level, grade, section, startDate, selectedTeacher, userRole, search]);

    // --- API Calls ---

    const fetchSchoolYears = async () => {
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.get('/api/schoolYears/lists', {
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setSchoolYears(response.data.data);
            if (response.data.data.length > 0) {
                setSelectedSchoolYear(response.data.data[0].id);
            }
        } catch (error) {
            toastr.error('Failed to load school years');
        }
    };

    const fetchTeachers = async () => {
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.get('/api/seat-plan/teachers', {
                params: { school_year_id: selectedSchoolYear },
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setTeachers(response.data.data || []);
            setUserRole(response.data.role); 
        } catch (error) {
            console.error("Failed to load teachers.");
        }
    };

    const fetchTeacherClasses = async () => {
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.get('/api/teacher-classes', {
                params: { 
                    school_year_id: selectedSchoolYear,
                    teacher_id: selectedTeacher 
                },
                headers: { Authorization: `Bearer ${authToken}` },
            });
            
            const classes = response.data.data || [];
            setTeacherClasses(classes);

            if (classes.length > 0) {
                setLevel(classes[0].level);
                setGrade(classes[0].grade);
                setSection(classes[0].section);
            } else {
                setLevel("");
                setGrade("");
                setSection("");
            }
        } catch (error) {
            toastr.error('Failed to load classes.');
        }
    };

    const fetchClassData = async () => {
        try {
            const authToken = localStorage.getItem("token");
            const year = startDate.getFullYear();
            const month = startDate.getMonth() + 1;

            const response = await axios.get('/api/attendances/class-roster', {
                params: { 
                    schoolYear: selectedSchoolYear, year: year, month: month,
                    level: level, grade: grade, section: section,
                    teacher_id: selectedTeacher,
                    search: search 
                },
                headers: { Authorization: `Bearer ${authToken}` },
            });
            
            setStudents(response.data.data?.data || response.data.data || []);
        } catch (error) {
            toastr.error('Failed to load class roster');
        }
    };

    const fetchSeatPlan = async () => {
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.get('/api/seat-plan', {
                params: {
                    school_year_id: selectedSchoolYear, level: level, grade: grade, section: section,
                    teacher_id: selectedTeacher
                },
                headers: { Authorization: `Bearer ${authToken}` },
            });
            
            const layout = response.data.layout;
            if (layout) {
                setGridRows(layout.total_rows);
                setGridCols(layout.total_cols);
                const mappedAssignments = layout.seat_assignments.map(seat => ({
                    student_id: seat.student_id, row: seat.row_position, col: seat.col_position
                }));
                setSeatAssignments(mappedAssignments);
            }
        } catch (error) {}
    };

    const saveSeatPlan = async () => {
        try {
            const authToken = localStorage.getItem("token");
            await axios.post('/api/seat-plan', {
                school_year_id: selectedSchoolYear, level: level, grade: grade, section: section,
                total_rows: gridRows, total_cols: gridCols, assignments: seatAssignments,
                teacher_id: selectedTeacher
            }, {
                headers: { Authorization: `Bearer ${authToken}` },
            });
            toastr.success('Seating plan saved successfully!');
        } catch (error) {
            toastr.error('Failed to save seating plan.');
        }
    };

    // --- Drag and Drop Handlers ---
    const handleDragStart = (e, studentId) => {
        if (viewMode !== "seating") return;
        e.dataTransfer.setData("studentId", studentId);
    };
    
    const handleDropOnGrid = (e, row, col) => {
        e.preventDefault();
        if (viewMode !== "seating") return;

        const studentId = parseInt(e.dataTransfer.getData("studentId"));
        setSeatAssignments(prev => {
            const filtered = prev.filter(seat => seat.student_id !== studentId);
            const clearedTarget = filtered.filter(seat => !(seat.row === row && seat.col === col));
            return [...clearedTarget, { student_id: studentId, row, col }];
        });
    };

    const handleDropOnUnassigned = (e) => {
        e.preventDefault();
        if (viewMode !== "seating") return;

        const studentId = parseInt(e.dataTransfer.getData("studentId"));
        setSeatAssignments(prev => prev.filter(seat => seat.student_id !== studentId));
    };

    const handleDragOver = (e) => e.preventDefault();

    // --- UPDATED: Click Handler for Attendance Modal ---
    const handleStudentClick = (student) => {
        if (viewMode !== "attendance" || !isSingleDate || !startDate) return;

        // NO MORE TIMEZONE MATH. Just grab the local numbers and stitch them together.
        const y = startDate.getFullYear();
        const m = String(startDate.getMonth() + 1).padStart(2, '0');
        const d = String(startDate.getDate()).padStart(2, '0');
        const dateStr = `${y}-${m}-${d}`;

        const summary = student.attendanceDailySummary?.find(s => s.date === dateStr);
        const scan = student.attendances?.find(a => a.scanned_at.startsWith(dateStr));
        const absence = student.absences?.find(a => a.date === dateStr);

        let status = "absent"; // Default state for form
        
        if (scan || (summary && summary.is_absent !== 1)) {
            status = "present";
        } else if (absence || (summary && summary.is_absent === 1)) {
            status = "absent";
        }

        setForm({
            student_id: student.id,
            student_name: `${student.lastname}, ${student.firstname}`,
            date: dateStr,
            attendance_id: scan?.id || "",
            status: status,
            is_late: summary?.is_late ?? 0,
            is_undertime: summary?.is_undertime ?? 0,
            is_excused: summary?.is_excused ?? 0,
            remarks: summary?.remarks ?? ""
        });

        setFormModal(true);
    };

    // --- UPDATED: Attendance Status Helper ---
    const getStudentStatus = (student) => {
        if (!startDate || !student) return { present: 0, absent: 0, total: 0 };
        const targetEndDate = endDate || startDate;
        const datesToCheck = [];
        let currDate = new Date(startDate);
        
        while (currDate <= targetEndDate) {
            // NO MORE TIMEZONE MATH. Just grab the local numbers and stitch them together.
            const y = currDate.getFullYear();
            const m = String(currDate.getMonth() + 1).padStart(2, '0');
            const d = String(currDate.getDate()).padStart(2, '0');
            datesToCheck.push(`${y}-${m}-${d}`);
            
            currDate.setDate(currDate.getDate() + 1);
        }

        let presentCount = 0;
        let absentCount = 0;

        datesToCheck.forEach(dateStr => {
            const hasScanned = student.attendances?.some(att => att.scanned_at.startsWith(dateStr));
            const hasSummary = student.attendance_daily_summary?.some(sum => sum.date === dateStr && sum.is_absent !== 1);
            
            const hasExplicitAbsence = student.absences?.some(abs => abs.date === dateStr);
            const hasSummaryAbsence = student.attendance_daily_summary?.some(sum => sum.date === dateStr && sum.is_absent === 1);

            if (hasScanned || hasSummary) {
                presentCount++;
            } else if (hasExplicitAbsence || hasSummaryAbsence) {
                absentCount++;
            }
        });

        return { present: presentCount, absent: absentCount };
    };

    const getStudentInSeat = (row, col) => {
        const assignment = seatAssignments.find(seat => seat.row === row && seat.col === col);
        if (!assignment) return null;
        return students.find(s => s.id === assignment.student_id);
    };

    // --- Unassigned Students with Search Filter ---
    const unassignedStudents = students.filter(student => {
        const isUnassigned = !seatAssignments.some(seat => seat.student_id === student.id);
        if (!isUnassigned) return false;

        if (!unassignedSearch) return true;

        const term = unassignedSearch.toLowerCase();
        return (
            student.firstname?.toLowerCase().includes(term) ||
            student.lastname?.toLowerCase().includes(term) ||
            student.student_id?.toLowerCase().includes(term)
        );
    });

    // --- Dynamic Dropdown Options ---
    const availableLevels = LEVELS.filter(lvl => 
        teacherClasses.some(c => c.level === lvl)
    );

    const availableGrades = [...new Set(
        teacherClasses.filter(c => c.level === level).map(c => c.grade)
    )];
    
    const availableSections = [...new Set(
        teacherClasses
            .filter(c => c.level === level && (grade === "" || c.grade === grade))
            .map(c => c.section)
    )];

    return (
        <div className="border border-gray-300 shadow-xl rounded-lg p-6 bg-white mx-auto w-full mt-5">
            
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 border-b pb-4 gap-4">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-800">Class Seating View</h1>   
                    <p className={`text-sm ${viewMode === "attendance" ? "text-green-600 font-medium" : "text-gray-500"}`}>
                        {viewMode === "seating" 
                            ? "Select a class, then drag and drop students to assign seats."
                            : "Click on any student to update their attendance for the selected date."
                        }
                    </p>
                </div>
                
                <div className="flex items-center gap-4">
                    <div className="flex bg-gray-100 p-1 rounded-lg border border-gray-200">
                        <button 
                            onClick={() => setViewMode("seating")}
                            className={`flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium transition-all ${
                                viewMode === "seating" ? "bg-white shadow-sm text-blue-600 border border-gray-200" : "text-gray-500 hover:text-gray-700"
                            }`}
                        >
                            <Move size={16} /> Assign Seats
                        </button>
                        <button 
                            disabled={!isSingleDate}
                            onClick={() => setViewMode("attendance")}
                            title={!isSingleDate ? "Select a single date below to enable attendance mode" : "Take Attendance"}
                            className={`flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium transition-all ${
                                !isSingleDate ? "opacity-50 cursor-not-allowed text-gray-400" :
                                viewMode === "attendance" ? "bg-white shadow-sm text-green-600 border border-gray-200" : "text-gray-500 hover:text-gray-700 hover:bg-gray-200"
                            }`}
                        >
                            <UserCheck size={16} /> Take Attendance
                        </button>
                    </div>

                    {viewMode === "seating" && (
                        <button 
                            onClick={saveSeatPlan}
                            disabled={!students.length}
                            className={`flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition ${
                                students.length > 0 ? "bg-blue-600 hover:bg-blue-700 text-white shadow-md" : "bg-gray-300 text-gray-500 cursor-not-allowed"
                            }`}
                        >
                            <Save size={18} /> Save Layout
                        </button>  
                    )}              
                </div>
            </div>

            <div className="bg-white p-5 rounded-xl border border-gray-200 shadow-sm mb-6">
                <div className="flex flex-wrap items-center gap-3">
                    
                    <div className="relative flex-grow min-w-[220px]">
                        <Search size={18} className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                        <input 
                            type="text"
                            placeholder="Search student in roster..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-full border border-gray-300 pl-10 pr-3 py-2.5 rounded-lg text-sm text-gray-800 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all shadow-sm"
                        />
                    </div>

                    <select 
                        value={selectedSchoolYear}
                        onChange={(e) => setSelectedSchoolYear(e.target.value)}
                        className="flex-1 min-w-[140px] border border-gray-300 px-3 py-2.5 rounded-lg text-sm bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all shadow-sm cursor-pointer"
                    >
                        {schoolYears.map((sy) => (
                            <option key={sy.id} value={sy.id}>
                                S.Y. {sy.sy_from}-{sy.sy_to}
                            </option>
                        ))}
                    </select>

                    {(userRole === 1 || userRole === 2) && (
                        <select
                            value={selectedTeacher}
                            onChange={(e) => setSelectedTeacher(e.target.value)}
                            className="flex-1 min-w-[150px] border border-gray-300 px-3 py-2.5 rounded-lg text-sm bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all shadow-sm cursor-pointer"
                        >
                            <option value="">Select Teacher</option>
                            {teachers.length === 0 ? (
                                <option value="" disabled>No teachers assigned</option>
                            ) : (
                                teachers.map(t => (
                                    <option key={t.user_id} value={t.user_id}>
                                        {t.lastname}, {t.firstname}
                                    </option>
                                ))
                            )}
                        </select>
                    )}

                    <select
                        value={level}
                        onChange={(e) => {
                            const newLevel = e.target.value;
                            setLevel(newLevel);
                            
                            const filtered = teacherClasses.filter(c => c.level === newLevel);
                            if (filtered.length > 0) {
                                setGrade(filtered[0].grade);
                                setSection(filtered[0].section);
                            } else {
                                setGrade("");
                                setSection("");
                            }
                        }}
                        disabled={(userRole === 1 || userRole === 2) && !selectedTeacher} 
                        className="flex-1 min-w-[130px] border border-gray-300 px-3 py-2.5 rounded-lg text-sm bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-400 transition-all shadow-sm cursor-pointer"
                    >
                        {availableLevels.length === 0 && <option value="" disabled>No Levels</option>}
                        {availableLevels.map(lvl => (
                            <option key={lvl} value={lvl}>{lvl}</option>
                        ))}
                    </select>
                    
                    <select
                        value={grade}
                        onChange={(e) => {
                            const newGrade = e.target.value;
                            setGrade(newGrade);

                            const filtered = teacherClasses.filter(c => c.level === level && c.grade === newGrade);
                            if (filtered.length > 0) {
                                setSection(filtered[0].section);
                            } else {
                                setSection("");
                            }
                        }}
                        disabled={!level} 
                        className="flex-1 min-w-[120px] border border-gray-300 px-3 py-2.5 rounded-lg text-sm bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-400 transition-all shadow-sm cursor-pointer"
                    >
                        {availableGrades.length === 0 && <option value="" disabled>No Grades</option>}
                        {availableGrades.map(g => (
                            <option key={g} value={g}>{g}</option>
                        ))}
                    </select>

                    <select
                        value={section}
                        onChange={(e) => setSection(e.target.value)}
                        disabled={!level} 
                        className="flex-1 min-w-[130px] border border-gray-300 px-3 py-2.5 rounded-lg text-sm bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-400 transition-all shadow-sm cursor-pointer"
                    >
                        {availableSections.length === 0 && <option value="" disabled>No Sections</option>}
                        {availableSections.map(s => (
                            <option key={s} value={s}>{s}</option>
                        ))}
                    </select>

                    <div className="flex-1 min-w-[200px]">
                        <DatePicker
                            selected={startDate}
                            onChange={(update) => setDuration(update)}
                            startDate={startDate}
                            endDate={endDate}
                            selectsRange
                            isClearable={false}
                            placeholderText="Select Date"
                            className="w-full border border-gray-300 px-3 py-2.5 rounded-lg text-sm bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all shadow-sm cursor-pointer"
                        />
                    </div>
                </div>
            </div>

            {level && grade && section ? (
                <>
                    <div className="flex items-center gap-4 mb-6 bg-blue-50 p-3 rounded-lg border border-blue-100 w-max">
                        <span className="text-sm font-semibold text-blue-800">Grid Size:</span>
                        <div className="flex items-center gap-2">
                            <label className="text-sm text-gray-700">Rows</label>
                            <input 
                                type="number" min="1" max="20" 
                                value={gridRows} onChange={(e) => setGridRows(Number(e.target.value))}
                                className="w-16 border border-gray-300 px-2 py-1 rounded text-center focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <span className="text-gray-400">x</span>
                        <div className="flex items-center gap-2">
                            <label className="text-sm text-gray-700">Cols</label>
                            <input 
                                type="number" min="1" max="20" 
                                value={gridCols} onChange={(e) => setGridCols(Number(e.target.value))}
                                className="w-16 border border-gray-300 px-2 py-1 rounded text-center focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                    </div>

                    <div className="flex flex-col lg:flex-row gap-6">
                        {/* Unassigned Students Sidebar */}
                        <div 
                            className="w-full lg:w-1/4 bg-gray-50 border border-gray-200 rounded-lg p-4 h-[600px] flex flex-col shadow-inner"
                            onDrop={handleDropOnUnassigned}
                            onDragOver={handleDragOver}
                        >
                            <div className="flex items-center gap-2 mb-4 text-gray-700 font-semibold border-b border-gray-200 pb-2">
                                <Users size={18} className="text-blue-600" />
                                Unassigned ({unassignedStudents.length})
                            </div>

                            <div className="relative mb-4">
                                <Search size={16} className="absolute left-3 top-2.5 text-gray-400" />
                                <input 
                                    type="text"
                                    placeholder="Search unassigned..."
                                    value={unassignedSearch}
                                    onChange={(e) => setUnassignedSearch(e.target.value)}
                                    className="w-full border border-gray-300 pl-9 pr-3 py-2 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all shadow-sm"
                                />
                            </div>
                            
                            <div className="space-y-2 overflow-y-auto flex-1 pr-1 custom-scrollbar">
                                {students.length === 0 ? (
                                    <p className="text-sm text-gray-400 text-center italic mt-10">Loading students...</p>
                                ) : unassignedStudents.length === 0 ? (
                                    <p className="text-sm text-gray-400 text-center italic mt-10">
                                        {unassignedSearch ? "No students match your search." : "All students assigned!"}
                                    </p>
                                ) : (
                                    unassignedStudents.map(student => (
                                        <div 
                                            key={student.id}
                                            draggable={viewMode === "seating"}
                                            onDragStart={(e) => handleDragStart(e, student.id)}
                                            onClick={() => handleStudentClick(student)}
                                            className={`bg-white border border-gray-200 p-3 rounded-lg shadow-sm transition-all ${
                                                viewMode === "seating" 
                                                    ? "cursor-grab active:cursor-grabbing hover:border-blue-400 hover:shadow-md" 
                                                    : "cursor-pointer hover:border-green-400 hover:shadow-md"
                                            }`}
                                        >
                                            <p className="text-sm font-bold text-gray-800">{student.lastname}, {student.firstname}</p>
                                            <p className="text-xs text-gray-500 mt-0.5">{student.student_id}</p>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>

                        {/* Seating Grid */}
                        <div className="w-full lg:w-3/4 overflow-x-auto border border-gray-200 rounded-lg bg-gray-50 p-6 flex justify-center shadow-inner">
                            <div 
                                className="grid gap-4"
                                style={{
                                    gridTemplateColumns: `repeat(${gridCols}, minmax(130px, 1fr))`,
                                    gridTemplateRows: `repeat(${gridRows}, minmax(110px, 1fr))`
                                }}
                            >
                                {Array.from({ length: gridRows }).map((_, rIndex) => (
                                    Array.from({ length: gridCols }).map((_, cIndex) => {
                                        const row = rIndex + 1;
                                        const col = cIndex + 1;
                                        const student = getStudentInSeat(row, col);
                                        
                                        return (
                                            <div 
                                                key={`${row}-${col}`} 
                                                onClick={() => { if (student) handleStudentClick(student) }}
                                                onDrop={(e) => handleDropOnGrid(e, row, col)}
                                                onDragOver={handleDragOver}
                                                className={`border-2 rounded-xl flex flex-col items-center justify-center p-2 text-center h-full min-h-[110px] transition-all relative group
                                                    ${student 
                                                        ? (viewMode === "seating" 
                                                            ? "bg-white border-blue-300 shadow hover:shadow-md hover:border-blue-400" 
                                                            : "bg-white border-green-300 shadow hover:shadow-md hover:border-green-500 hover:ring-2 hover:ring-green-100") 
                                                        : "bg-transparent border-dashed border-gray-300"
                                                    }
                                                `}
                                            >
                                                <span className="absolute top-1.5 left-2 text-[10px] font-mono text-gray-400">R{row}C{col}</span>

                                                {student ? (
                                                    <div 
                                                        draggable={viewMode === "seating"}
                                                        onDragStart={(e) => handleDragStart(e, student.id)}
                                                        className={`w-full h-full flex flex-col items-center justify-center ${
                                                            viewMode === "seating" 
                                                                ? "cursor-grab active:cursor-grabbing" 
                                                                : "cursor-pointer"
                                                        }`}
                                                    >
                                                        <span className="font-bold text-gray-800 text-sm leading-tight mt-3">
                                                            {student.lastname}, {student.firstname}
                                                        </span>
                                                        
                                                        {/* UPDATED: UI Status Badge */}
                                                        {(() => {
                                                            const stats = getStudentStatus(student);
                                                            return isSingleDate ? (
                                                                stats.present > 0 ? (
                                                                    <span className="px-3 py-1 mt-2 rounded-full text-[11px] font-bold shadow-sm w-max bg-green-100 text-green-700 border border-green-200">Present</span>
                                                                ) : stats.absent > 0 ? (
                                                                    <span className="px-3 py-1 mt-2 rounded-full text-[11px] font-bold shadow-sm w-max bg-red-100 text-red-700 border border-red-200">Absent</span>
                                                                ) : (
                                                                    <span className="px-3 py-1 mt-2 rounded-full text-[11px] font-bold shadow-sm w-max bg-gray-100 text-gray-500 border border-gray-200">No Record</span>
                                                                )
                                                            ) : (
                                                                <div className="flex gap-2 mt-2 text-xs font-semibold w-full justify-center">
                                                                    <div className="flex flex-col bg-green-50 text-green-700 px-2 py-0.5 rounded shadow-sm border border-green-200">
                                                                        <span>P: {stats.present}</span>
                                                                    </div>
                                                                    <div className="flex flex-col bg-red-50 text-red-700 px-2 py-0.5 rounded shadow-sm border border-red-200">
                                                                        <span>A: {stats.absent}</span>
                                                                    </div>
                                                                </div>
                                                            );
                                                        })()}
                                                    </div>
                                                ) : (
                                                    <span className="text-gray-400 text-xs font-medium tracking-wide">Empty</span>
                                                )}
                                            </div>
                                        );
                                    })
                                ))}
                            </div>
                        </div>
                    </div>
                </>
            ) : (
                <div className="text-center py-16 px-4 bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl text-gray-500">
                    <p className="text-lg font-medium text-gray-600">Please select a class to view the seating plan.</p>
                    <p className="text-sm mt-2 text-gray-400">Use the filters above to load your class roster and visual layout.</p>
                </div>
            )}

            <AttendanceSelectedDay
                formModal={formModal}
                setFormModal={setFormModal}
                form={form}
                setForm={setForm}
                fetchAttendances={fetchClassData} 
            />
        </div>
    );
};

export default AttendanceClassView;