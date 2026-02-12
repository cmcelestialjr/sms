import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Layout from "./Layout";
import { Edit, Plus, View, ViewIcon, X, } from "lucide-react";
import toastr from 'toastr';
import Swal from "sweetalert2";
import 'toastr/build/toastr.min.css';
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";
import AbsenceDatesModal from "./AbsenceDatesModal";

const Absences = () => {
    const userRole = localStorage.getItem("userRole");
    const [meta, setMeta] = useState(null);
    const [search, setSearch] = useState("");
    const [page, setPage] = useState(1);
    const [absences, setAbsences] = useState([]);
    const [grade, setGrade] = useState("");
    const [section, setSection] = useState("");
    const [grades, setGrades] = useState([]);
    const [sections, setSections] = useState([]);
    const [showModal, setShowModal] = useState(false);
    const [selectedStudent, setSelectedStudent] = useState(null);
    const [schoolYears, setSchoolYears] = useState([]);
    const [selectedSchoolYear, setSelectedSchoolYear] = useState("");
    const [duration, setDuration] = useState([
        new Date(),
        new Date(),
    ]);
    const [startDate, endDate] = duration;

    const didFetch = useRef(false);

    useEffect(() => {
        if (didFetch.current) return;
        didFetch.current = true;
        
        fetchSections();
        fetchGrades();
        fetchSchoolYears();
    }, []);

    useEffect(() => {
        fetchAbsences();
    }, [page, search, grade, section, startDate, endDate]);

    const handleSearch = (e) => {
        setSearch(e.target.value);
        setPage(1);
    };

    const fetchAbsences = async () => {
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.get('/api/absences/index', {
                params: { 
                    page: page, 
                    search: search, 
                    grade: grade,
                    section: section,
                    startDate: startDate, 
                    endDate: endDate,
                    schoolYear: selectedSchoolYear,
                },
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setAbsences(response.data.data);
            setMeta(response.data.meta);
        } catch (error) {
            // toastr.error('Failed to load students');
        } finally {

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

    const fetchGrades = async () => {
        try {
            const authToken = localStorage.getItem("token");
            const res = await axios.get("/api/grades", {
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setGrades(res.data);
        } catch (error) {
            
        }
    };

    const fetchSections = async () => {
        try {
            const authToken = localStorage.getItem("token");
            const res = await axios.get("/api/sections", {
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setSections(res.data);
        } catch (error) {
            
        }
    };

    const formatDateAndTime = (date) => {
        const validDate = new Date(date);

        const formattedDate = validDate.toLocaleDateString('en-US', {
            month: 'short',
            day: '2-digit',
            year: 'numeric',
        });

        const formattedTime = validDate.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true,
        }).toLowerCase();

        return `${formattedDate} ${formattedTime}`;
    };

    const openAbsenceDatesModal = (student) => {
        setSelectedStudent(student);
        setShowModal(true);
    };

    return (
        <Layout>
            <div className="border border-gray-300 shadow-xl rounded-lg p-6 bg-white mx-auto w-full mt-10">
                {/* Header Section */}
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-semibold text-gray-800">Absences</h1>                   
                </div>
                
                <div>
                    <div className="mb-4">
                        {/* Search Input */}
                        <input
                            type="text"
                            placeholder="Search absences..."
                            value={search}
                            onChange={handleSearch}
                            className="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    
                    <div className={`grid gap-4 mb-4 grid-cols-1 ${userRole < 3 ? 'md:grid-cols-4' : 'md:grid-cols-2'}`}>                        
                        {/* Single Calendar for Date Duration */}
                        <DatePicker
                            selected={startDate}
                            onChange={(update) => setDuration(update)}
                            startDate={startDate}
                            endDate={endDate}
                            selectsRange
                            isClearable
                            placeholderText="Select duration"
                            className="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />

                        <select 
                            value={selectedSchoolYear}
                            onChange={(e) => setSelectedSchoolYear(e.target.value)}
                            className="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            {schoolYears.map((sy) => (
                                <option key={sy.id} value={sy.id}>
                                    S.Y. {sy.sy_from}-{sy.sy_to}
                                </option>
                            ))}
                        </select>

                        {userRole<3 && ( <>
                        <select
                            value={grade}
                            onChange={(e) => setGrade(e.target.value)}
                            className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        >
                            <option value="">Select Grade</option>
                            {grades?.map((grade, i) => (
                                <option key={i} value={grade}>{grade}</option>
                            ))}
                        </select>
                        <select
                            value={section}
                            onChange={(e) => setSection(e.target.value)}
                            className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        >
                            <option value="">Select Section</option>
                            {sections?.map((section, i) => (
                                <option key={i} value={section}>{section}</option>
                            ))}
                        </select> 
                        
                        </> )}
                    </div>

                    <div className="flex justify-between items-center mb-3 mt-3">
                        <h4 className="font-semibold text-gray-800">
                            Total Students: {absences?.length}
                        </h4>
                        <h4 className="font-semibold text-gray-800">
                            Total Absence/s: {absences?.reduce((total, absence) => total + absence.absences.length, 0)}
                        </h4>
                    </div>

                    {/* Table */}
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm text-left border-collapse border border-gray-300">
                            <thead className="bg-gray-100 text-xs text-gray-700 uppercase">
                                <tr>
                                    <th className="border border-gray-300 px-4 py-2 text-center">Student ID</th>
                                    <th className="border border-gray-300 px-4 py-2">Student</th>
                                    <th className="border border-gray-300 px-4 py-2 text-center">Grade-Section</th>
                                    <th className="border border-gray-300 px-4 py-2 text-center">Count</th>
                                    <th className="border border-gray-300 px-4 py-2 text-center">Dates</th>
                                </tr>
                            </thead>
                            <tbody>
                                {absences?.map((absence) => (
                                    <tr key={absence.id} className="border-t">
                                        <td className="border border-gray-300 px-4 py-2 text-center">{absence.student_id}</td>
                                        <td className="border border-gray-300 px-4 py-2">{absence.lastname},  {absence.firstname}</td>
                                        <td className="border border-gray-300 px-4 py-2 text-center">{absence.grade}-{absence.section}</td>
                                        <td className="border border-gray-300 px-4 py-2 text-center">
                                            {absence.absences.length}
                                        </td>
                                        <td className="border border-gray-300 px-4 py-2 text-center">
                                            <button onClick={() => openAbsenceDatesModal(absence)} 
                                                className="text-blue-600 hover:underline mr-2 cursor-pointer">
                                                <ViewIcon size={16} />
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {meta && (
                        <div className="flex justify-center mt-4 space-x-2">
                            <button
                            disabled={meta.current_page === 1}
                            onClick={() => setPage(meta.current_page - 1)}
                            className="px-3 py-1 border rounded disabled:opacity-50 cursor-pointer"
                            >
                            Prev
                            </button>
                            <span className="px-3 py-1">
                            Page {meta.current_page} of {meta.last_page}
                            </span>
                            <button
                            disabled={meta.current_page === meta.last_page}
                            onClick={() => setPage(meta.current_page + 1)}
                            className="px-3 py-1 border rounded disabled:opacity-50 cursor-pointer"
                            >
                            Next
                            </button>
                        </div>
                    )}
                </div>
            </div>
            <AbsenceDatesModal
                show={showModal}
                onClose={() => setShowModal(false)}
                student={selectedStudent}
            />
        </Layout>
    );

};

export default Absences;