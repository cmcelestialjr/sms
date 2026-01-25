import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Layout from "./Layout";
import { Edit, Plus, X, User, CheckCircle, XCircle, Check, Link, NotebookText } from "lucide-react";
import { useNavigate } from 'react-router-dom';
import toastr from 'toastr';
import Swal from "sweetalert2";
import 'toastr/build/toastr.min.css';
import "react-datepicker/dist/react-datepicker.css";

const Teachers = () => {
    const navigate = useNavigate();
    const [meta, setMeta] = useState(null);
    const [search, setSearch] = useState("");
    const [page, setPage] = useState(1);
    const [teachers, setTeachers] = useState([]);
    const [searchTerm, setSearchTerm] = useState("");
    const [showTeacherModal, setShowTeacherModal] = useState(false);
    const [showStudentModal, setShowStudentModal] = useState(false);
    const [editingTeacher, setEditingTeacher] = useState(false);
    const [selectedStatus, setSelectedStatus] = useState("Active");
    const [totalActive, setTotalActive] = useState(0);
    const [totalInActive, setTotalInActive] = useState(0);
    const [newPhoto, setNewPhoto] = useState(null);
    const [students, setStudents] = useState([]);
    const [selectedStudents, setSelectedStudents] = useState([]);
    const [selectAll, setSelectAll] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [teacherSuggestions, setTeacherSuggestions] = useState([]);
    const [selectedTeacherSuggestion, setSelectedTeacherSuggestion] = useState(null);
    const [searchTeacherTerm, setSearchTeacherTerm] = useState("");
    const [searchTeacherId, setSearchTeacherId] = useState("");
    const [showTeacherForTranser, setShowTeacherForTranser] = useState(false);
    const [form, setForm] = useState({
        id_no: '',
        lastname: '',
        firstname: '',
        middlename: '',
        extname: '',
        level: 'Elementary',
        grade: '',
        section: '',
        status: 'Active',
        photo: '',
        email: '',
        contact_no: '',
        address: '',
        position: '',
        sex: 'Male',
    });
    const didFetch = useRef(false);

    useEffect(() => {
        if (didFetch.current) return;
        didFetch.current = true;

    }, []);

    useEffect(() => {
        fetchTeachers(selectedStatus);
    }, [page, search, selectedStatus]);

    useEffect(() => {
        fetchStatusTotal();
    }, [search]);

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

    const handleSearch = (e) => {
        setSearch(e.target.value);
        setPage(1);
    };

    const handleSelectedStatus = (status) => {
        setSelectedStatus(status);
        setPage(1);
        fetchTeachers(status);
    };

    const fetchTeachers = async (status) => {
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.get('/api/teachers', {
                params: { page, search, status },
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setTeachers(response.data.data);
            setMeta(response.data.meta);
        } catch (error) {

        } finally {

        }
    };

    const fetchStatusTotal = async () => {
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.get(`/api/teachers/status-total`, {
                params: {
                    search: search,
                },
                headers: { Authorization: `Bearer ${authToken}` },
            });
            const { totalActiveResponse, totalInActiveResponse } = response.data;

            setTotalActive(totalActiveResponse);
            setTotalInActive(totalInActiveResponse);
        } catch (error) {

        }
    };

    const handleSubmit = async () => {
        try {
            const authToken = localStorage.getItem("token");
            const url = editingTeacher
                ? `/api/users/update/${editingTeacher.id}`
                : '/api/users/store';
            const method = editingTeacher ? 'post' : 'post';
    
            const formData = new FormData();
    
            for (const key in form) {
                formData.append(key, form[key]);
            }

            formData.append('role', 3);

            if(!editingTeacher){
                const username = form.lastname
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/\s+/g, '')
                    .toLowerCase();
                formData.append('username', username);
                formData.append('password', username);
            }
    
            // if (editingTeacher) {
            //     formData.append('_method', 'PUT');
            // }

            formData.append('newPhoto', newPhoto);
    
            const response = await axios.post(url, formData, {
                headers: {
                    Authorization: `Bearer ${authToken}`,
                    'Content-Type': 'multipart/form-data',
                },
            });
        
            fetchTeachers();
            setShowTeacherModal(false);
            toastr.success("Saved successfully");
        } catch (error) {
            const errorMessage =
            error.response?.data?.message ||
            error.response?.data?.errors
                ? Object.values(error.response.data.errors).flat().join(', ')
                : 'An unexpected error occurred.';
            toastr.error(errorMessage, "Failed to save.");
        }
    };    
    
    const handleAdd = () => {
        setEditingTeacher(null);
        setForm({
            id_no: '',
            lastname: '',
            firstname: '',
            middlename: '',
            extname: '',
            level: 'Elementary',
            grade: '',
            section: '',
            status: 'Active',
            photo: '',
            email: '',
            contact_no: '',
            address: '',
            position: '',
            sex: 'Male',
        });
        setShowTeacherModal(true);
    };
    
    const handleEdit = (teacher) => {
        setEditingTeacher(teacher);

        setForm({
            id_no: teacher.id_no || '',
            lastname: teacher.lastname || '',
            firstname: teacher.firstname || '',
            middlename: teacher.middlename || '',
            extname: teacher.extname || '',
            level: teacher.teacher?.level || 'Elementary',
            grade: teacher.teacher?.grade || '',
            section: teacher.teacher?.section || '',
            status: teacher.teacher?.status || 'Active',
            photo: teacher.teacher?.photo || '',
            email: teacher.teacher?.email || '',
            contact_no: teacher.teacher?.contact_no || '',
            address: teacher.teacher?.address || '',
            position: teacher.teacher?.position || '',
            sex: teacher.teacher?.sex || 'Male',
        });
        setShowTeacherModal(true);
    };

    const handleStudents = (students) => {
        setShowTeacherForTranser(false);
        setSearchTeacherTerm("");
        setSearchTeacherId("");
        setTeacherSuggestions([]);
        setSelectedTeacherSuggestion([]);
        setSelectedStudents([]);
        setSelectAll(false);
        setSearchQuery("");
        setShowStudentModal(true);
        setStudents(students);
    };

    const handleCheckboxChange = (studentId) => {
        setSelectedStudents((prevSelected) => {
            if (prevSelected.includes(studentId)) {
                return prevSelected.filter(id => id !== studentId);
            } else {
                return [...prevSelected, studentId];
            }
        });
    };

    const handleCheckAll = () => {
        if (selectAll) {
            setSelectedStudents([]);
        } else {
            setSelectedStudents(students.map(student => student.id));
        }
        setSelectAll(!selectAll);
    };

    const filteredStudents = students.filter((student) => {
        const fullName = `${student.lastname} ${student.firstname} ${student.extname} ${student.middlename || ''}`;
        return fullName.toLowerCase().includes(searchQuery.toLowerCase());
    });

    const handleLiClick = (studentId) => {
        handleCheckboxChange(studentId);
    };

    const handleFormChange = (e) => {
        setForm({
            ...form,
            [e.target.name]: e.target.value,
        });
    };

    const handleImageChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setNewPhoto(file);
            setForm((prevForm) => ({
                    ...prevForm,
                    photo: URL.createObjectURL(file),
            }));
        }
    };

    const handleTransferStudent = async () => {
        if(selectedStudents.length==0){
            toastr.error("Please check at least 1 student.");
            return;
        }
        if(searchTeacherId==""){
            toastr.error("Please select a teacher");
            return;
        }

        try {
            const authToken = localStorage.getItem("token");
            const url = '/api/teachers/transfer';
    
            const formData = new FormData();

            formData.append('id', searchTeacherId);
            formData.append('students', selectedStudents);
    
            const response = await axios.post(url, formData, {
                headers: {
                    Authorization: `Bearer ${authToken}`,
                },
            });
        
            fetchTeachers();
            setShowStudentModal(false);
            toastr.success("Saved successfully");
        } catch (error) {
            const errorMessage =
            error.response?.data?.message ||
            error.response?.data?.errors
                ? Object.values(error.response.data.errors).flat().join(', ')
                : 'An unexpected error occurred.';
            toastr.error(errorMessage, "Failed to save.");
        }
    };

    return (
        <Layout>
            <div className="border border-gray-300 shadow-xl rounded-lg p-6 bg-white mx-auto w-full mt-10">
                {/* Header Section */}
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-semibold text-gray-800">Teachers</h1>    
                    <button
                        onClick={() => handleAdd()}
                        className="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition cursor-pointer"
                    >
                        <Plus size={18} /> New Teacher
                    </button>               
                </div>

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
                </div>
                
                <div>
                    {/* Search Input */}
                    <div className="grid grid-cols-1 md:grid-cols-1 gap-4 mb-4">
                        <input
                            type="text"
                            placeholder="Search teachers..."
                            value={search}
                            onChange={handleSearch}
                            className="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>                    

                    {/* Table */}
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm text-left border-collapse border border-gray-300">
                            <thead className="bg-gray-100 text-xs text-gray-700 uppercase">
                                <tr>
                                    <th className="border border-gray-300 px-4 py-2 text-center">ID</th>
                                    <th className="border border-gray-300 px-4 py-2 text-center">Photo</th>
                                    <th className="border border-gray-300 px-4 py-2 text-center">Name</th>
                                    <th className="border border-gray-300 px-4 py-2 text-center">Level</th>
                                    <th className="border border-gray-300 px-4 py-2 text-center">Grade</th>
                                    <th className="border border-gray-300 px-4 py-2 text-center">Section</th>
                                    <th className="border border-gray-300 px-4 py-2 text-center">Students</th>
                                    <th className="border border-gray-300 px-4 py-2 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {teachers?.map((teacher) => (
                                    <tr key={teacher.id} className="border-t">
                                        <td className="border border-gray-300 px-4 py-2 text-center">{teacher.id_no}</td>
                                        <td className="border border-gray-300 px-4 py-2">
                                            <div className="flex justify-center">
                                                <div className="w-14 h-14 rounded-full overflow-hidden border border-gray-300 shadow-sm">
                                                <img
                                                    src={teacher.teacher?.photo}
                                                    alt={teacher.lastname}
                                                    className="w-full h-full object-cover"
                                                />
                                                </div>
                                            </div>
                                        </td>
                                        <td className="border border-gray-300 px-4 py-2">
                                            {teacher.lastname}, {teacher.firstname} {teacher.extname || ''} {teacher.middlename || ''}
                                        </td>
                                        <td className="border border-gray-300 px-4 py-2">{teacher.teacher?.level}</td>
                                        <td className="border border-gray-300 px-4 py-2">{teacher.teacher?.grade}</td>
                                        <td className="border border-gray-300 px-4 py-2">{teacher.teacher?.section}</td>
                                        <td className="border border-gray-300 px-4 py-2">
                                            <div className="flex justify-center">
                                                <button
                                                    onClick={() => handleStudents(teacher.students)}
                                                    className="flex items-center gap-2 bg-purple-500 text-white px-4 py-2 rounded-lg shadow hover:bg-purple-600 transition cursor-pointer"
                                                >
                                                    {teacher.students_count}
                                                </button>
                                            </div>                                         
                                        </td>
                                        <td className="border border-gray-300 px-4 py-2 text-right">
                                            <div className="flex justify-end items-center space-x-2">
                                                {/* Edit Button */}
                                                <button
                                                    onClick={() => handleEdit(teacher)}
                                                    className="flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 border border-blue-600 rounded cursor-pointer hover:bg-blue-50 transition"
                                                >
                                                    <Edit size={16} className="mr-1" />
                                                    Edit
                                                </button>

                                                {/* DTR Button */}
                                                {/* <button
                                                    onClick={() => navigate(`/dtr/${teacher.id}/list`)}
                                                    className="flex items-center px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded cursor-pointer hover:bg-blue-700 transition"
                                                >
                                                    <NotebookText size={16} className="mr-1" />
                                                    DTR
                                                </button> */}
                                            </div>
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

                {showTeacherModal && (
                    <div className="fixed inset-0 bg-black/50 flex justify-center items-center z-50">
                        <div className="bg-white p-6 rounded-lg max-w-2xl w-full shadow-lg max-h-[90vh] overflow-y-auto relative">
                            <h2 className="text-xl font-semibold mb-6 text-center">{editingTeacher ? "Edit Teacher" : "Add Teacher"}</h2>

                            <div className="flex flex-col items-center justify-center mt-2 space-y-1">
                                <label
                                    className="relative w-36 h-36 rounded-full overflow-hidden shadow-md border border-gray-300 flex items-center justify-center bg-white hover:shadow-lg transition-all cursor-pointer group"
                                    title="Click to upload photo"
                                >
                                    {form.photo ? (
                                    <img
                                        src={form.photo}
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

                            <div className="space-y-3">
                                <div className="grid grid-cols-3 sm:grid-cols-2 gap-4">
                                {/* ID No */}
                                <div className="grid grid-cols-1">
                                    <label htmlFor="id_no" className="text-sm font-medium text-gray-700">ID No</label>
                                    <input 
                                        id="id_no"
                                        name="id_no"
                                        value={form.id_no}
                                        onChange={handleFormChange}
                                        placeholder="Enter ID No"
                                        className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>

                                {/* Last Name */}
                                <div className="grid grid-cols-1">
                                    <label htmlFor="lastname" className="text-sm font-medium text-gray-700">Last Name</label>
                                    <input 
                                        id="lastname"
                                        name="lastname"
                                        value={form.lastname}
                                        onChange={handleFormChange}
                                        placeholder="Enter Last Name"
                                        className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>

                                {/* First Name */}
                                <div className="grid grid-cols-1">
                                    <label htmlFor="firstname" className="text-sm font-medium text-gray-700">First Name</label>
                                    <input 
                                        id="firstname"
                                        name="firstname"
                                        value={form.firstname}
                                        onChange={handleFormChange}
                                        placeholder="First Name"
                                        className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>

                                {/* Middle Name */}
                                <div className="grid grid-cols-1">
                                    <label htmlFor="middlename" className="text-sm font-medium text-gray-700">Middle Name</label>
                                    <input 
                                        id="middlename"
                                        name="middlename"
                                        value={form.middlename}
                                        onChange={handleFormChange}
                                        placeholder="Middle Name"
                                        className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>

                                {/* Extension Name */}
                                <div className="grid grid-cols-1">
                                    <label htmlFor="extname" className="text-sm font-medium text-gray-700">Extension Name (e.g. Jr.)</label>
                                    <input 
                                        id="extname"
                                        name="extname"
                                        value={form.extname}
                                        onChange={handleFormChange}
                                        placeholder="Extension Name"
                                        className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>

                                {/* Contact No */}
                                <div className="flex flex-col">
                                    <label className="text-sm font-medium text-gray-700 mb-1">Contact No.</label>
                                    <input
                                        type="text"
                                        name="contact_no"
                                        placeholder="Enter Contact No."
                                        value={form.contact_no}
                                        onChange={(e) => {
                                            const value = e.target.value;
                                            // Only allow digits, up to 11 characters
                                            if (/^\d{0,11}$/.test(value)) {
                                                setForm({ ...form, contact_no: value });
                                            }
                                        }}
                                        maxLength={11}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                        required
                                    />
                                    {form.contact_no && (
                                        form.contact_no.length !== 11 || !form.contact_no.startsWith("09")
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
                                        name="email"
                                        placeholder="Enter Email"
                                        value={form.email || ''}
                                        onChange={(e) => setForm({ ...form, email: e.target.value })}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                    />
                                </div>
                                {/* Position */}
                                <div className="flex flex-col">
                                    <label className="text-sm font-medium text-gray-700 mb-1">Position</label>
                                    <input
                                        type="text"
                                        name="position"
                                        placeholder="Enter Position"
                                        value={form.position || ''}
                                        onChange={(e) => setForm({ ...form, position: e.target.value })}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                    />
                                </div>

                                {/* Sex */}
                                <div className="flex flex-col">
                                    <label className="text-sm font-medium text-gray-700 mb-1">Sex</label>
                                    <select
                                        value={form.sex}
                                        onChange={(e) => setForm({ ...form, sex: e.target.value })}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                        required
                                    >
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                </div>

                                {/* Address */}
                                <div className="flex flex-col col-span-2">
                                    <label className="text-sm font-medium text-gray-700 mb-1">Address</label>
                                    <input
                                        type="text"
                                        name="address"
                                        placeholder="Enter Address"
                                        value={form.address || ''}
                                        onChange={(e) => setForm({ ...form, address: e.target.value })}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                    />
                                </div>

                                {/* Level */}
                                <div className="grid grid-cols-1">
                                    <label htmlFor="level" className="text-sm font-medium text-gray-700">Level</label>
                                    <select 
                                        id="level"
                                        name="level"
                                        value={form.level}
                                        onChange={handleFormChange}
                                        className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    >
                                        <option value="Kinder">Kinder</option>
                                        <option value="Elementary">Elementary</option>
                                        <option value="Junior High School">Junior High School</option>
                                        <option value="Senior High School">Senior High School</option>
                                    </select>
                                </div>

                                {/* Grade */}
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label htmlFor="grade" className="text-sm font-medium text-gray-700">Grade</label>
                                        <input 
                                            id="grade"
                                            name="grade"
                                            value={form.grade}
                                            onChange={handleFormChange}
                                            placeholder="Enter Grade"
                                            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        />
                                    </div>

                                    {/* Section */}
                                    <div>
                                        <label htmlFor="section" className="text-sm font-medium text-gray-700">Section</label>
                                        <input 
                                            id="section"
                                            name="section"
                                            value={form.section}
                                            onChange={handleFormChange}
                                            placeholder="Enter Section"
                                            className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        />
                                    </div>
                                </div>

                                {/* Status */}
                                <div className="flex flex-col">
                                    <label className="text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select
                                        name="status"
                                        value={form.status}
                                        onChange={handleFormChange}
                                        className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                                        required
                                    >
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>

                                {/* Action Buttons */}
                                <div className="flex justify-end mt-6 space-x-3">
                                    <button
                                        onClick={() => setShowTeacherModal(false)}
                                        className="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 cursor-pointer"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        onClick={handleSubmit}
                                        className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 cursor-pointer"
                                    >
                                        {editingTeacher ? 'Update' : 'Add'} Teacher
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {showStudentModal && (
                    <div className="fixed inset-0 bg-black/50 flex justify-center items-center z-50">
                        <div className="bg-white p-6 rounded-lg max-w-2xl w-full shadow-lg max-h-[90vh] overflow-y-auto relative">
                            <div className="flex justify-between items-center mb-3">
                                <h2 className="text-xl font-semibold text-left">Students</h2>
                                <button 
                                    className="text-xl font-semibold text-gray-600 cursor-pointer"
                                    onClick={() => setShowStudentModal(false)}
                                >
                                    <X size={16} />
                                </button>
                            </div>

                            <p className="text-lg font-semibold mb-2">
                                Total Students: {students?.length || 0}
                            </p>

                            <div className="mb-2 flex items-center justify-between space-x-4">
                                {/* Search Input */}
                                <div className="flex-1">
                                    <input
                                        type="text"
                                        placeholder="Search students..."
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        className="w-full border border-gray-300 px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition ease-in-out duration-200"
                                    />
                                </div>

                                {/* Check All Checkbox */}
                                <div className="flex items-center space-x-2">
                                    <label className="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            checked={selectAll}
                                            onChange={handleCheckAll}
                                            className="form-checkbox h-5 w-5 text-blue-600 transition duration-200 ease-in-out"
                                        />
                                        <span className="text-sm font-semibold">Select All</span>
                                    </label>
                                </div>

                                {/* Transfer Button */}
                                <button
                                    onClick={() => setShowTeacherForTranser(true)}
                                    className="px-6 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-400 transition duration-200 ease-in-out cursor-pointer"
                                >
                                    Transfer
                                </button>
                            </div>

                            {showTeacherForTranser && (
                                <div className="flex flex-col">
                                    <label className="text-sm font-medium text-gray-700 mb-1">Teacher</label>

                                    {/* Input and Button Container */}
                                    <div className="flex items-center space-x-4">
                                        {/* Teacher Search Input */}
                                        <input
                                            type="text"
                                            placeholder="Search teacher"
                                            value={searchTeacherTerm}
                                            onChange={(e) => setSearchTeacherTerm(e.target.value)}
                                            className="p-3 border border-gray-300 rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />

                                        {/* Transfer Button */}
                                        <button
                                            onClick={handleTransferStudent}
                                            className="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-orange-400 transition duration-200 ease-in-out cursor-pointer"
                                        >
                                            <Check size={16} />
                                        </button>

                                        {/* Transfer Button */}
                                        <button
                                            onClick={() => {
                                                setShowTeacherForTranser(false);
                                                setSearchTeacherTerm("");
                                                setSearchTeacherId("");
                                                setTeacherSuggestions([]);
                                                setSelectedTeacherSuggestion([]);
                                            }}
                                            className="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-orange-400 transition duration-200 ease-in-out cursor-pointer"
                                        >
                                            <X size={16} />
                                        </button>
                                    </div>

                                    {/* Teacher Suggestions */}
                                    {teacherSuggestions.length > 0 && (
                                        <ul className="border border-gray-300 rounded-lg mt-1 max-h-48 overflow-y-auto bg-white z-10 relative">
                                            {teacherSuggestions.map((teacher) => (
                                                <li
                                                    key={teacher.user_id}
                                                    onClick={() => {
                                                        setSelectedTeacherSuggestion(teacher);
                                                        setSearchTeacherTerm(`${teacher.lastname}, ${teacher.firstname} ${teacher.extname || ''} ${teacher.middlename || ''}`);
                                                        setSearchTeacherId(teacher.user_id);
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


                            <div className="flex flex-col items-center justify-center mt-2 space-y-1 w-full"> 
                                {/* Student List */}
                                <ul className="w-full space-y-1">
                                    {filteredStudents.length > 0 ? (
                                        filteredStudents.map((student, index) => (
                                            <li
                                                key={student.id}
                                                className="flex justify-between items-center p-2 border rounded-lg cursor-pointer hover:bg-gray-100"
                                                onClick={() => handleLiClick(student.id)}
                                            >
                                                <div className="flex items-center">
                                                    <input
                                                        type="checkbox"
                                                        checked={selectedStudents.includes(student.id)}
                                                        onChange={() => handleCheckboxChange(student.id)}
                                                        className="mr-2"
                                                    />
                                                    <span className="font-semibold">
                                                        {index + 1}. {student.lastname}, {student.firstname} {student.extname}
                                                        {student.middlename && student.middlename[0] ? `${student.middlename[0]}.` : ''}
                                                    </span>
                                                </div>
                                                <p className="text-sm text-gray-500">{student.grade} - {student.section}</p>
                                            </li>
                                        ))
                                    ) : (
                                        <li className="text-gray-500 text-center">No students available.</li>
                                    )}
                                </ul>
                            </div>
                            
                            {/* Action Buttons */}
                            <div className="flex justify-end mt-6 space-x-3">
                                <button
                                    onClick={() => setShowStudentModal(false)}
                                    className="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 cursor-pointer"
                                >
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                )}

            </div>

        </Layout>
    );

};

export default Teachers;