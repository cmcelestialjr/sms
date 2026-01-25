import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Layout from "./Layout";
import { User, Edit, Trash, Plus, X, CheckCircle, XCircle, Clock, Hourglass, CheckCircle2 } from "lucide-react";
import toastr from 'toastr';
import Swal from "sweetalert2";
import 'toastr/build/toastr.min.css';
import DatePicker from 'react-datepicker';
import "react-datepicker/dist/react-datepicker.css";

const Students = () => {
    const userRole = localStorage.getItem("userRole");
    const [meta, setMeta] = useState(null);
    const [search, setSearch] = useState("");
    const [page, setPage] = useState(1);
    const [students, setStudents] = useState([]);
    const [loading, setLoading] = useState(true);
    const [studentSuggestions, setStudentSuggestions] = useState([]);
    const [selectedSuggestion, setSelectedSuggestion] = useState(null);
    const [searchTerm, setSearchTerm] = useState("");
    const [teacherSuggestions, setTeacherSuggestions] = useState([]);
    const [selectedTeacherSuggestion, setSelectedTeacherSuggestion] = useState(null);
    const [searchTeacherTerm, setSearchTeacherTerm] = useState("");
    const [studentModalOpen, setStudentModalOpen] = useState(false);
    const [newPhoto, setNewPhoto] = useState(null);
    const [selectedStatus, setSelectedStatus] = useState("Active");
    const [totalActive, setTotalActive] = useState(0);
    const [totalInActive, setTotalInActive] = useState(0);
    const [totalApproved, setTotalApproved] = useState(0);
    const [totalRequested, setTotalRequested] = useState(0);
    const [totalPending, setTotalPending] = useState(0);
    const [studentForm, setStudentForm] = useState({
        search_student_id: "",
        student_id: "",
        firstname: "",
        lastname: "",
        middlename: "",
        extname: "",
        contact_no: "",
        qr_code: "",
        rfid_tag: "",
        sex: "Male",
        birthdate: "",
        address: "",
        level: "Kinder",
        grade: "",
        section: "",
        photo: null,
        teachers_id: null,
        status: 'Active',
        lrn_no: "",
        email: "",
    });
    const [isEditMode, setIsEditMode] = useState(false);
    const openAddModal = () => {
        setStudentForm({
            search_student_id: "",
            student_id: "",
            firstname: "",
            lastname: "",
            middlename: "",
            extname: "",
            contact_no: "",
            qr_code: "",
            rfid_tag: "",
            sex: "Male",
            birthdate: "",
            address: "",
            level: "Kinder",
            grade: "",
            section: "",
            photo: null,
            teachers_id: null,
            status: 'Active',
            lrn_no: "",
            email: "",
        });
        setStudentSuggestions([]);
        setSelectedSuggestion(null);
        setSearchTerm("");
        setTeacherSuggestions([]);
        setSelectedTeacherSuggestion(null);
        setSearchTeacherTerm("");
        setNewPhoto(null);
        setIsEditMode(false);
        setStudentModalOpen(true);
    };
    const openEditModal = (student) => {
        setStudentForm(student);
        setStudentSuggestions([]);
        setSelectedSuggestion(null);
        setSearchTerm("");
        setTeacherSuggestions([]);
        setSelectedTeacherSuggestion(null);
        setNewPhoto(null);
        setSearchTeacherTerm(
            student.teacher
            ? `${student.teacher.lastname}, ${student.teacher.firstname} ${student.teacher.extname || ''} ${student.teacher.middlename || ''}`
            : ''
        );
        setIsEditMode(true);
        setStudentModalOpen(true);
    };

    const didFetch = useRef(false);

    useEffect(() => {
        if (didFetch.current) return;
        didFetch.current = true;

    }, []);

    useEffect(() => {
        fetchStudents(selectedStatus);
    }, [page, search, selectedStatus]);

    useEffect(() => {
        fetchStatusTotal();
    }, [search]);
    
    useEffect(() => {
        if (searchTerm.length < 1) return;
        const delayDebounce = setTimeout(async () => {
            try {
                const authToken = localStorage.getItem("token");
                const response = await axios.get('/api/students/search', {
                    params: { search : searchTerm },
                    headers: { Authorization: `Bearer ${authToken}` },
                });
                setStudentSuggestions(response.data);
            } catch (err) {
                // console.error("Failed to fetch suggestions", err);
            }
        }, 300);
        return () => clearTimeout(delayDebounce);
    }, [searchTerm]);

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
        }, 300);
        return () => clearTimeout(delayDebounce);
    }, [searchTeacherTerm]);

    const handleSelectedStatus = (status) => {
        setSelectedStatus(status);
        setPage(1);
        fetchStudents(status);
    };

    const fetchStudents = async (status) => {
        setLoading(true);
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.get('/api/students', {
                params: { page, search, status },
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setStudents(response.data.data);
            setMeta(response.data.meta);
        } catch (error) {
            // toastr.error('Failed to load students');
        } finally {
            setLoading(false);
        }
    };

    const handleSearch = (e) => {
        setSearch(e.target.value);
        setPage(1);
    };

    const fetchStatusTotal = async () => {
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.get(`/api/students/status-total`, {
                params: {
                    search: search,
                },
                headers: { Authorization: `Bearer ${authToken}` },
            });
            const { totalActiveResponse, totalInActiveResponse, totalApprovedResponse, totalRequestedResponse, totalPendingResponse } = response.data;

            setTotalActive(totalActiveResponse);
            setTotalInActive(totalInActiveResponse);
            setTotalApproved(totalApprovedResponse);
            setTotalRequested(totalRequestedResponse);
            setTotalPending(totalPendingResponse);
        } catch (error) {

        }
    };

    const createStudent = async () => {
        try {
            const formData = new FormData();
            for (let key in studentForm) {
                formData.append(key, studentForm[key]);
            }
            const authToken = localStorage.getItem("token");
            const response = await axios.post('/api/students', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    Authorization: `Bearer ${authToken}`,                    
                }
            });
            toastr.success("Student added!");
            fetchStudents(selectedStatus);
            fetchStatusTotal();
            setStudentModalOpen(false);
        } catch (error) {
            const errorMessage =
            error.response?.data?.message ||
            error.response?.data?.errors
                ? Object.values(error.response.data.errors).flat().join(', ')
                : 'An unexpected error occurred.';
            toastr.error(errorMessage, "Failed to add student.");
        }
    };

    const updateStudent = async () => {
        try {
            const formData = new FormData();
            for (let key in studentForm) {
                formData.append(key, studentForm[key]);
            }
            formData.append('newPhoto', newPhoto);
            const authToken = localStorage.getItem("token");
            const response = await axios.post(`/api/students/${studentForm.id}`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    Authorization: `Bearer ${authToken}`,
                }
            });

            toastr.success("Student updated!");
            fetchStudents(selectedStatus);
            fetchStatusTotal();
            setStudentModalOpen(false);
        } catch (error) {
            const errorMessage =
            error.response?.data?.message ||
            error.response?.data?.errors
                ? Object.values(error.response.data.errors).flat().join(', ')
                : 'An unexpected error occurred.';
            toastr.error(errorMessage, "Failed to update student.");
        }
    };

    const handleApproved = async (id) => {
        try {
            const data = { 
                id: id
            };
            const authToken = localStorage.getItem("token");
            const response = await axios.post("/api/students/approved/request", data, {
                headers: { Authorization: `Bearer ${authToken}` },
            });
            toastr.success("Successfuly Approved!");
            fetchStudents(selectedStatus);
            fetchStatusTotal();
        } catch (error) {
            const errorMessage =
            error.response?.data?.message ||
            error.response?.data?.errors
                ? Object.values(error.response.data.errors).flat().join(', ')
                : 'An unexpected error occurred.';
            toastr.error(errorMessage, "Failed to approve.");
        }
    };

    const handleDelete = async (id) => {
        const result = await Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, cancel!',
            reverseButtons: true
        });
    
        if (!result.isConfirmed) return;
    
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.delete(`/api/students/${id}`, {
                headers: {
                    Authorization: `Bearer ${authToken}`
                }
            });
            if(response.data.message=='Deleted'){
                Swal.fire('Deleted!', 'The student has been deleted.', 'success'); 
                fetchStudents(selectedStatus);
                fetchStatusTotal();
            }else{
                Swal.fire('Error!', response.data.message, 'error'); 
            }
            
        } catch (error) {
            Swal.fire('Error!', 'Failed to delete the student.', 'error');
        }
    };

    const handleImageChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setNewPhoto(file);
            setStudentForm((prevForm) => ({
                    ...prevForm,
                    photo: URL.createObjectURL(file),
            }));
        }
    };

    return (
        <Layout>
            <div className="border border-gray-300 shadow-xl rounded-lg p-6 bg-white mx-auto w-full mt-10">
                {/* Header Section */}
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-semibold text-gray-800">Students</h1>
                    <button
                        onClick={() => openAddModal()}
                        className="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition cursor-pointer"
                    >
                        <Plus size={18} /> New Student
                    </button>
                </div>

                {/* Summary Section (Sales Options) */}
                <div className="grid grid-cols-5 gap-6 mb-8">
                    <button
                        onClick={() => handleSelectedStatus("Active")}
                        className={`flex flex-col items-center p-4 rounded-xl shadow-md transition-all duration-300 transform hover:scale-105 cursor-pointer ${
                            selectedStatus === "Active" ? "bg-blue-600 text-white" : "bg-white border border-gray-300"
                        }`}
                    >
                        <CheckCircle size={24} className={`${selectedStatus === "Active" ? "text-white" : "text-blue-600"}`} />
                        <span className="text-sm font-semibold">Active</span>
                        <span className="text-lg font-bold">{totalActive}</span>
                    </button>
                
                    <button
                        onClick={() => handleSelectedStatus("InActive")}
                        className={`flex flex-col items-center p-4 rounded-xl shadow-md transition-all duration-300 transform hover:scale-105 cursor-pointer ${
                            selectedStatus === "InActive" ? "bg-red-600 text-white" : "bg-white border border-gray-300"
                        }`}
                    >
                        <XCircle size={24} className={`${selectedStatus === "InActive" ? "text-white" : "text-red-600"}`} />
                        <span className="text-sm font-semibold">InActive</span>
                        <span className="text-lg font-bold">{totalInActive}</span>
                    </button>
                    {userRole==3 && ( <>
                        <button
                            onClick={() => handleSelectedStatus("Approved")}
                            className={`flex flex-col items-center p-4 rounded-xl shadow-md transition-all duration-300 transform hover:scale-105 cursor-pointer ${
                                selectedStatus === "Approved" ? "bg-green-600 text-white" : "bg-white border border-gray-300"
                            }`}
                        >
                            <CheckCircle size={24} className={`${selectedStatus === "Approved" ? "text-white" : "text-green-600"}`} />
                            <span className="text-sm font-semibold">Approved</span>
                            <span className="text-lg font-bold">{totalApproved}</span>
                        </button>

                        <button
                            onClick={() => handleSelectedStatus("Pending")}
                            className={`flex flex-col items-center p-4 rounded-xl shadow-md transition-all duration-300 transform hover:scale-105 cursor-pointer ${
                                selectedStatus === "Pending" ? "bg-orange-600 text-white" : "bg-white border border-gray-300"
                            }`}
                        >
                            <Hourglass size={24} className={`${selectedStatus === "Pending" ? "text-white" : "text-orange-600"}`} />
                            <span className="text-sm font-semibold">Pending</span>
                            <span className="text-lg font-bold">{totalPending}</span>
                        </button>

                        <button
                            onClick={() => handleSelectedStatus("Requested")}
                            className={`flex flex-col items-center p-4 rounded-xl shadow-md transition-all duration-300 transform hover:scale-105 cursor-pointer ${
                                selectedStatus === "Requested" ? "bg-purple-600 text-white" : "bg-white border border-gray-300"
                            }`}
                        >
                            <Clock size={24} className={`${selectedStatus === "Requested" ? "text-white" : "text-purple-600"}`} />
                            <span className="text-sm font-semibold">Requested</span>
                            <span className="text-lg font-bold">{totalRequested}</span>
                        </button>
                        </>
                    )}
                </div>
                
                <div>
                    <div className="grid grid-cols-1 md:grid-cols-1 gap-4 mb-4">
                        {/* Search Input */}
                        <input
                            type="text"
                            placeholder="Search students..."
                            value={search}
                            onChange={handleSearch}
                            className="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm text-left border-collapse border border-gray-300">
                        <thead className="bg-gray-100 text-xs text-gray-700 uppercase">
                            <tr>
                                <th className="border border-gray-300 px-4 py-2 text-left">Student ID</th>
                                <th className="border border-gray-300 px-4 py-2 text-left">Photo</th>
                                <th className="border border-gray-300 px-4 py-2 text-left">Name</th>
                                <th className="border border-gray-300 px-4 py-2 text-left">Sex</th>
                                <th className="border border-gray-300 px-4 py-2 text-left">Grade</th>
                                <th className="border border-gray-300 px-4 py-2 text-left">Section</th>
                                <th className="border border-gray-300 px-4 py-2 text-left">Teacher</th>
                                {selectedStatus!='Approved' && selectedStatus!='Requested' && selectedStatus!='Pending' && (
                                    <th className="border border-gray-300 px-4 py-2 text-left">Status</th>  
                                )}
                                {selectedStatus!='Approved' && selectedStatus!='Requested' && (
                                    <th className="border border-gray-300 px-4 py-2 text-left text-right">Actions</th>
                                )}
                            </tr>
                        </thead>
                        <tbody>
                            {students?.map((student) => (
                            <tr key={student.id} className="border-t">
                                <td className="border border-gray-300 px-4 py-2">{student.student_id}</td>
                                <td className="border border-gray-300 px-4 py-2">
                                    <div className="flex justify-center">
                                        <div className="w-14 h-14 rounded-full overflow-hidden border border-gray-300 shadow-sm">
                                        <img
                                            src={student.photo}
                                            alt={student.lastname}
                                            className="w-full h-full object-cover"
                                        />
                                        </div>
                                    </div>
                                </td>
                                <td className="border border-gray-300 px-4 py-2">
                                    {student.lastname}, {student.firstname} {student.extname} {student.middlename && student.middlename[0] ? `${student.middlename[0]}.` : ''}
                                </td>
                                <td className="border border-gray-300 px-4 py-2">{student.sex}</td>                                
                                <td className="border border-gray-300 px-4 py-2">{student.grade}</td>
                                <td className="border border-gray-300 px-4 py-2">{student.section}</td>
                                <td className="border border-gray-300 px-4 py-2">
                                    {student.teacher?.lastname}, {student.teacher?.firstname} {student.teacher?.extname} {student.teacher?.middlename && student.teacher?.middlename[0] ? `${student.teacher?.middlename[0]}.` : ''}
                                </td>
                                {selectedStatus!='Approved' && selectedStatus!='Requested' && selectedStatus!='Pending' && (
                                    <>
                                    <td className="border border-gray-300 px-4 py-2">
                                        <span
                                            className={`px-3 py-1 rounded-full text-sm font-semibold 
                                            ${student.status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}
                                        >
                                            {student.status}
                                        </span>
                                    </td>
                                    </>
                                )}
                                {selectedStatus!='Approved' && selectedStatus!='Requested' && (
                                <>
                                <td className="border border-gray-300 px-4 py-2 text-center">
                                    {selectedStatus!='Pending' && (
                                        <>
                                        <button onClick={() => openEditModal(student)} className="text-blue-600 hover:underline mr-2 cursor-pointer">
                                            <Edit size={16} />
                                        </button>
                                        <button onClick={() => handleDelete(student.id)} className="text-red-600 hover:underline cursor-pointer">
                                            <Trash size={16} />
                                        </button>
                                        </>
                                    )}
                                    {selectedStatus=='Pending' && (
                                        <button onClick={() => handleApproved(student.id)} className="text-green-600 hover:underline cursor-pointer flex items-center">
                                            <CheckCircle size={16} />
                                            <span>Approve</span>
                                        </button>
                                    )}
                                </td>
                                </>
                                )}
                            </tr>
                            ))}
                        </tbody>
                        </table>
                    </div>
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
            
            {studentModalOpen && (
                <div className="fixed top-0 left-0 right-0 bottom-0 bg-black/50 bg-opacity-50 flex justify-center items-center z-50">
                    <div className="bg-white p-8 rounded-lg w-full max-w-3xl shadow-lg max-h-[90vh] overflow-y-auto relative">
                        <h2 className="text-2xl font-semibold mb-6 text-center">
                            {isEditMode ? "Edit Student" : "Add Student"}
                        </h2>
                            
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                isEditMode ? updateStudent() : createStudent();
                            }}
                        >
                            {!isEditMode && (
                            <div className="mb-6">
                                <label className="text-sm font-medium text-gray-700 mb-1 block">Search Existing Student</label>
                                <input
                                type="text"
                                placeholder="Type name"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="p-3 border border-gray-300 rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                                {studentSuggestions.length > 0 && (
                                <ul className="border border-gray-300 rounded-lg mt-1 max-h-48 overflow-y-auto bg-white z-10 relative">
                                    {studentSuggestions.map((student) => (
                                    <li
                                        key={student.id}
                                        onClick={() => {
                                        setStudentForm({
                                            ...student,
                                            search_student_id: student.id,
                                            birthdate: student.birthdate,
                                            photo: null,
                                        });
                                        setSelectedSuggestion(student);
                                        setSearchTerm(`${student.student_id} - ${student.lastname}, ${student.firstname} ${student.middlename || ''} ${student.extname || ''}`);
                                        setStudentSuggestions([]);
                                        }}
                                        className="p-2 hover:bg-gray-100 cursor-pointer"
                                    >
                                        {student.student_id} - {student.lastname}, {student.firstname} {student.middlename || ''} {student.extname || ''}
                                    </li>
                                    ))}
                                </ul>
                                )}
                                <p className="text-xs text-gray-500 mt-1">
                                If not found, leave blank and fill in manually.
                                </p>
                            </div>
                            )}

                            <div className="flex flex-col items-center justify-center mt-2 space-y-1">
                                <label
                                    className="relative w-36 h-36 rounded-full overflow-hidden shadow-md border border-gray-300 flex items-center justify-center bg-white hover:shadow-lg transition-all cursor-pointer group"
                                    title="Click to upload photo"
                                >
                                    {studentForm.photo ? (
                                    <img
                                        src={studentForm.photo}
                                        alt="Student"
                                        className="w-full h-full object-cover"
                                    />
                                    ) : (
                                    <div className="flex flex-col items-center text-gray-400 group-hover:text-blue-500 transition-colors">
                                        <User className="w-12 h-12" />
                                    </div>
                                    )}
                                    <input
                                    type="file"
                                    accept="image/*"
                                    onChange={handleImageChange}
                                    className="absolute inset-0 opacity-0 cursor-pointer"
                                    />
                                </label>
                                <p className="text-sm text-gray-500">Upload photo</p>
                            </div>


                            {/* Photo Upload */}
                            {/* <div className="mt-6">
                                <label className="block text-sm font-medium text-gray-700 mb-1">Upload Photo</label>
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) => setStudentForm({ ...studentForm, photo: e.target.files[0] })}
                                    className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                />
                            </div> */}

                            <div className="mt-2 grid grid-cols-1 md:grid-cols-3 gap-6">
                                {/* RFID Tag */}
                                <div className="flex flex-col"> 
                                    <label className="text-sm font-medium text-gray-700 mb-1">RFID TAG</label>
                                    <input
                                        type="text"
                                        placeholder="Enter RFID TAG"
                                        value={studentForm.rfid_tag}
                                        onChange={(e) => setStudentForm({ ...studentForm, rfid_tag: e.target.value })}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        disabled={userRole > 2 && isEditMode}
                                    />
                                </div>

                                {/* QR CODE */}
                                <div className="flex flex-col"> 
                                    <label className="text-sm font-medium text-gray-700 mb-1">QR CODE</label>
                                    <input
                                        type="text"
                                        placeholder="Enter QR CODE"
                                        value={studentForm.qr_code}
                                        onChange={(e) => setStudentForm({ ...studentForm, qr_code: e.target.value })}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        disabled={userRole > 2 && isEditMode}
                                    />
                                </div>
                                
                                {/* Student ID */}
                                {isEditMode && (                                
                                <div className="flex flex-col">
                                    <label className="text-sm font-medium text-gray-700 mb-1">Student ID</label>
                                    <input
                                        type="text"
                                        placeholder="Enter Student ID"
                                        value={studentForm.student_id}
                                        onChange={(e) => setStudentForm({ ...studentForm, student_id: e.target.value })}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        disabled
                                    />
                                </div>
                                )}

                                {/* LRN */}
                                <div className="flex flex-col">
                                    <label className="text-sm font-medium text-gray-700 mb-1">LRN No:</label>
                                    <input
                                        type="text"
                                        placeholder="Enter LRN No"
                                        value={studentForm.lrn_no}
                                        onChange={(e) => setStudentForm({ ...studentForm, lrn_no: e.target.value })}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                        required
                                    />
                                </div>
                
                                {/* Lastname */}
                                <div className="flex flex-col">
                                    <label className="text-sm font-medium text-gray-700 mb-1">Lastname</label>
                                    <input
                                        type="text"
                                        placeholder="Enter Last Name"
                                        value={studentForm.lastname}
                                        onChange={(e) => setStudentForm({ ...studentForm, lastname: e.target.value })}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                        required
                                    />
                                </div>
                
                                {/* Firstname */}
                                <div className="flex flex-col">
                                    <label className="text-sm font-medium text-gray-700 mb-1">Firstname</label>
                                    <input
                                        type="text"
                                        placeholder="Enter First Name"
                                        value={studentForm.firstname}
                                        onChange={(e) => setStudentForm({ ...studentForm, firstname: e.target.value })}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                        required
                                    />
                                </div>
                
                                {/* Middlename */}
                                <div className="flex flex-col">
                                    <label className="text-sm font-medium text-gray-700 mb-1">Middlename</label>
                                    <input
                                        type="text"
                                        placeholder="Enter Middlename"
                                        value={studentForm.middlename || ''}
                                        onChange={(e) => setStudentForm({ ...studentForm, middlename: e.target.value })}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                    />
                                </div>
                
                                {/* Extname */}
                                <div className="flex flex-col">
                                    <label className="text-sm font-medium text-gray-700 mb-1">Extname</label>
                                    <input
                                        type="text"
                                        placeholder="Enter Extname"
                                        value={studentForm.extname || ''}
                                        onChange={(e) => setStudentForm({ ...studentForm, extname: e.target.value })}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                    />
                                </div>  
                
                                {/* Sex */}
                                <div className="flex flex-col">
                                    <label className="text-sm font-medium text-gray-700 mb-1">Sex</label>
                                    <select
                                        value={studentForm.sex}
                                        onChange={(e) => setStudentForm({ ...studentForm, sex: e.target.value })}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                        required
                                    >
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                
                                {/* Birthdate */}
                                <div className="flex flex-col">
                                    <label className="text-sm font-medium text-gray-700 mb-1">Birthdate</label>
                                    <DatePicker
                                        selected={studentForm.birthdate ? new Date(studentForm.birthdate) : null}
                                        onChange={(date) => setStudentForm({ ...studentForm, birthdate: date.toISOString().split('T')[0] })}
                                        className="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                        wrapperClassName="w-full"
                                        placeholderText="Select Birthdate"
                                        dateFormat="yyyy-MM-dd"
                                        showYearDropdown
                                        scrollableYearDropdown
                                    />
                                </div>

                                {/* Contact No */}
                                <div className="flex flex-col">
                                    <label className="text-sm font-medium text-gray-700 mb-1">Contact No.</label>
                                    <input
                                        type="text"
                                        placeholder="Enter Contact No."
                                        value={studentForm.contact_no}
                                        onChange={(e) => {
                                            const value = e.target.value;
                                            // Only allow digits, up to 11 characters
                                            if (/^\d{0,11}$/.test(value)) {
                                                setStudentForm({ ...studentForm, contact_no: value });
                                            }
                                        }}
                                        maxLength={11}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                        required
                                    />
                                    {studentForm.contact_no && (
                                        studentForm.contact_no.length !== 11 || !studentForm.contact_no.startsWith("09")
                                    ) && (
                                        <span className="text-red-500 text-sm mt-1">
                                            Contact number must start with "09" and be exactly 11 digits.
                                        </span>
                                    )}
                                </div>

                                {/* Email */}
                                <div className="flex flex-col">
                                    <label className="text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input
                                        type="email"
                                        placeholder="Enter Email"
                                        value={studentForm.email || ''}
                                        onChange={(e) => setStudentForm({ ...studentForm, email: e.target.value })}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                    />
                                </div>

                                {/* Status */}
                                <div className="flex flex-col">
                                    <label className="text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select
                                        value={studentForm.status}
                                        onChange={(e) => setStudentForm({ ...studentForm, status: e.target.value })}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                        required
                                    >
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>

                                {/* Address */}
                                <div className="flex flex-col col-span-2">
                                    <label className="text-sm font-medium text-gray-700 mb-1">Address</label>
                                    <input
                                        type="text"
                                        placeholder="Enter Address"
                                        value={studentForm.address || ''}
                                        onChange={(e) => setStudentForm({ ...studentForm, address: e.target.value })}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                    />
                                </div>

                                {userRole<3 && (
                                    <div className="flex flex-col">
                                        <label className="text-sm font-medium text-gray-700 mb-1">Teacher</label>
                                        <input
                                        type="text"
                                        placeholder="Type name"
                                        value={searchTeacherTerm}
                                        onChange={(e) => setSearchTeacherTerm(e.target.value)}
                                        className="p-3 border border-gray-300 rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                        />
                                        {teacherSuggestions.length > 0 && (
                                        <ul className="border border-gray-300 rounded-lg mt-1 max-h-48 overflow-y-auto bg-white z-10 relative">
                                            {teacherSuggestions.map((teacher) => (
                                            <li
                                                key={teacher.user_id}
                                                onClick={() => {
                                                setStudentForm({ ...studentForm, teachers_id: teacher.user_id })
                                                setSelectedTeacherSuggestion(teacher);
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
                                )}                               
                            </div>                            
                
                            {/* Action Buttons */}
                            <div className="mt-8 flex justify-end space-x-4">
                                <button
                                    type="button"
                                    onClick={() => setStudentModalOpen(false)}
                                    className="px-6 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-500 transition-colors cursor-pointer"
                                >
                                    {isEditMode ? "Update" : "Add"}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            
            )}

        </Layout>
    );

};

export default Students;