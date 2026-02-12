import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Layout from "./Layout";
import { Edit, Plus, X, } from "lucide-react";
import toastr from 'toastr';
import Swal from "sweetalert2";
import 'toastr/build/toastr.min.css';
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";

const AttendanceLists = () => {
    const [meta, setMeta] = useState(null);
    const [search, setSearch] = useState("");
    const [page, setPage] = useState(1);
    const [attendaces, setAttendances] = useState([]);
    const [schoolYears, setSchoolYears] = useState([]);
    const [selectedSchoolYear, setSelectedSchoolYear] = useState("");
    const [type, setType] = useState("");
    const [duration, setDuration] = useState([
        new Date(),
        new Date(),
    ]);
    const [startDate, endDate] = duration;

    const didFetch = useRef(false);

    useEffect(() => {
        if (didFetch.current) return;
        didFetch.current = true;

    }, []);

    useEffect(() => {
        const timer = setTimeout(() => {
            fetchAttendances();
        }, 500);
        return () => clearTimeout(timer);
    }, [search, type, selectedSchoolYear, startDate, endDate]);

    useEffect(() => {
        fetchSchoolYears()
    }, []);

    const handleSearch = (e) => {
        setSearch(e.target.value);
        setPage(1);
    };

    const fetchAttendances = async () => {
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.get('/api/attendances/lists', {
                params: { 
                    page: page, 
                    search: search, 
                    schoolYear: selectedSchoolYear,
                    type: type,
                    startDate: startDate, 
                    endDate: endDate
                },
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setAttendances(response.data.data);
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



    return (
            <div className="border border-gray-300 shadow-xl rounded-lg p-6 bg-white mx-auto w-full mt-5">
                {/* Header Section */}
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-semibold text-gray-800">Attendances</h1>                   
                </div>
                
                <div>
                    {/* Search Input */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <input
                            type="text"
                            placeholder="Search attendances..."
                            value={search}
                            onChange={handleSearch}
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
                            value={type}
                            onChange={(e) => setType(e.target.value)}
                            className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        >
                            <option value="">Select Type</option>
                            <option value="In">In</option>
                            <option value="Out">Out</option>
                            <option value="InAm">In (AM)</option>
                            <option value="OutAm">Out (AM)</option>
                            <option value="InPm">In (PM)</option>
                            <option value="OutPm">Out (PM)</option>
                        </select>
                    </div>

                    {/* Table */}
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm text-left border-collapse border border-gray-300">
                            <thead className="bg-gray-100 text-xs text-gray-700 uppercase">
                                <tr>
                                    <th className="border border-gray-300 px-4 py-2">Date TIme</th>
                                    <th className="border border-gray-300 px-4 py-2">Type</th>
                                    <th className="border border-gray-300 px-4 py-2">Student ID</th>
                                    <th className="border border-gray-300 px-4 py-2">Student</th>
                                    <th className="border border-gray-300 px-4 py-2">Station</th>
                                </tr>
                            </thead>
                            <tbody>
                                {attendaces.map((attendance) => (
                                    <tr key={attendance.id} className="border-t">
                                        <td className="border border-gray-300 px-4 py-2">{formatDateAndTime(attendance.scanned_at)}</td>
                                        <td className="border border-gray-300 px-4 py-2 text-center">{attendance.type}</td>
                                        <td className="border border-gray-300 px-4 py-2 text-center">{attendance.student?.student_id}</td>
                                        <td className="border border-gray-300 px-4 py-2">
                                            {attendance.student?.lastname}, {attendance.student?.firstname} {attendance.student?.extname} {attendance.student?.middlename}
                                        </td>
                                        <td className="border border-gray-300 px-4 py-2 text-center">
                                            {attendance.station?.station_name}
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
    );

};

export default AttendanceLists;