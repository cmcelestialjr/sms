import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Layout from "./Layout";
import { Edit, Plus } from "lucide-react";
import toastr from 'toastr';
import Swal from "sweetalert2";
import 'toastr/build/toastr.min.css';
import "react-datepicker/dist/react-datepicker.css";
import SchoolYearModal from "./SchoolYearModal";

const SchoolYears = () => {
    const [meta, setMeta] = useState(null);
    const [search, setSearch] = useState("");
    const [page, setPage] = useState(1);
    const [schoolYears, setSchoolYears] = useState([]);
    const [showModal, setShowModal] = useState(false);
    const [currentSchoolYear, setCurrentSchoolYear] = useState(null);

    const didFetch = useRef(false);

    useEffect(() => {
        if (didFetch.current) return;
        didFetch.current = true;
    }, []);

    useEffect(() => {
        fetchSchoolYears();
    }, [page, search]);

    const handleSearch = (e) => {
        setSearch(e.target.value);
        setPage(1);
    };

    const fetchSchoolYears = async () => {
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.get('/api/schoolYears', {
                params: {
                    page: page,
                    search: search,
                },
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setSchoolYears(response.data.data);
            setMeta(response.data.meta);
        } catch (error) {
            // toastr.error('Failed to load schoolYears');
        }
    };

    const handleAdd = () => {
        setCurrentSchoolYear(null); 
        setShowModal(true);
    };

    const handleEdit = (schoolYear) => {
        setCurrentSchoolYear(schoolYear);
        setShowModal(true);
    };

    const handleSaveSchoolYear = async (data) => {
        try {
            const authToken = localStorage.getItem("token");
            if (currentSchoolYear) {
                await axios.put(`/api/schoolYears/${currentSchoolYear.id}`, data, {
                    headers: { Authorization: `Bearer ${authToken}` },
                });
                toastr.success('Shool Year updated successfully');
            } else {
                await axios.post('/api/schoolYears', data, {
                    headers: { Authorization: `Bearer ${authToken}` },
                });
                toastr.success('Shool Year added successfully');
            }
            fetchSchoolYears();
        } catch (error) {
            toastr.error('Failed to save school year');
        }
    };

    const handleTeachers = async (data) => {

    };

    return (
        <Layout>
            <div className="border border-gray-300 shadow-xl rounded-lg p-6 bg-white mx-auto w-full mt-10">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-semibold text-gray-800">SchoolYears</h1>
                    <button
                        onClick={handleAdd}
                        className="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition cursor-pointer"
                    >
                        <Plus size={18} /> New School Year
                    </button>
                </div>

                {/* Search */}
                <div className="grid grid-cols-1 md:grid-cols-1 gap-4 mb-4">
                    <input
                        type="text"
                        placeholder="Search schoolYears..."
                        value={search}
                        onChange={handleSearch}
                        className="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                {/* SchoolYears Table */}
                <div className="overflow-x-auto">
                    <table className="min-w-full text-sm text-left border-collapse border border-gray-300">
                        <thead className="bg-gray-100 text-xs text-gray-700 uppercase">
                            <tr>
                                <th className="border border-gray-300 px-4 py-2">School Year</th>
                                <th className="border border-gray-300 px-4 py-2">Duration</th>
                                <th className="border border-gray-300 px-4 py-2">Teachers</th>
                                <th className="border border-gray-300 px-4 py-2">Students</th>
                                <th className="border border-gray-300 px-4 py-2">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {schoolYears?.map((schoolYear) => (
                                <tr key={schoolYear.id} className="border-t">
                                    <td className="border border-gray-300 px-4 py-2">{schoolYear.sy_from}-{schoolYear.sy_to}</td>
                                    <td className="border border-gray-300 px-4 py-2">
                                        {new Date(schoolYear.date_from).toLocaleDateString('en-US', {
                                            month: 'short',
                                            day: 'numeric',
                                            year: 'numeric',
                                        })} - {new Date(schoolYear.date_to).toLocaleDateString('en-US', {
                                            month: 'short',
                                            day: 'numeric',
                                            year: 'numeric',
                                        })}
                                    </td>
                                    <td className="border border-gray-300 px-4 py-2 text-center">
                                        <div className="flex justify-center">
                                            <button
                                                onClick={() => handleTeachers(schoolYear)}
                                                className="flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-600 transition cursor-pointer"
                                            >
                                                {schoolYear.teachers_count}
                                            </button>
                                        </div>
                                    </td>
                                    <td className="border border-gray-300 px-4 py-2 text-center">
                                        <div className="flex justify-center">
                                            <button
                                                // onClick={handleAdd}
                                                className="flex items-center gap-2 bg-purple-500 text-white px-4 py-2 rounded-lg shadow hover:bg-purple-600 transition cursor-pointer"
                                            >
                                                {schoolYear.students_count}
                                            </button>
                                        </div>
                                    </td>
                                    <td className="border border-gray-300 px-4 py-2 text-center">
                                        <button
                                            onClick={() => handleEdit(schoolYear)}
                                            className="flex items-center gap-1 text-blue-600 hover:underline cursor-pointer"
                                        >
                                            <Edit size={16} /> Edit
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

            <SchoolYearModal
                show={showModal}
                onClose={() => setShowModal(false)}
                onSave={handleSaveSchoolYear}
                schoolYear={currentSchoolYear}
            />
        </Layout>
    );
};

export default SchoolYears;
