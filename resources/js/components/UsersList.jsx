import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Layout from "./Layout";
import UserModal from './UserModal';
import { Edit, Plus, X, Upload } from "lucide-react";
import Swal from "sweetalert2";
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

const UsersList = () => {  
    const [users, setUsers] = useState([]);
    const [meta, setMeta] = useState(null);
    const [search, setSearch] = useState("");
    const [page, setPage] = useState(1);
    const [userModal, setUserModal] = useState(false);
    const [lastnameOfUser, setLastnameOfUser] = useState("");
    const [firstnameOfUser, setFirstnameOfUser] = useState("");
    const [extnameOfUser, setExtnameOfUser] = useState("");
    const [middlenameOfUser, setMiddlenameOfUser] = useState("");
    const [userNameOfUser, setUserNameOfUser] = useState("");
    const [passwordOfUser, setPasswordOfUser] = useState("");
    const [roleOfUser, setRoleOfUser] = useState(2);
    const [levelOfUser, setLevelOfUser] = useState("Elementary");
    const [gradeOfUser, setGradeOfUser] = useState("");
    const [sectionOfUser, setSectionOfUser] = useState("");
    const [newPhoto, setNewPhoto] = useState(null);
    const [photo, setPhoto] = useState(null);
    const [email, setEmail] = useState("");
    const [contactNo, setContactNo] = useState("");
    const [address, setAddress] = useState("");
    const [idOfUser, setIdOfUser] = useState("");
    const [roles, setRoles] = useState([]);
    const [file, setFile] = useState(null);
    const [isEditingPassword, setIsEditingPassword] = useState(false);
    const [status, setStatus] = useState("Active");
    const [position, setPosition] = useState("");
    const [schoolYears, setSchoolYears] = useState([]);
    const [schoolYearId, setSchoolYearId] = useState("");
    const [sex, setSex] = useState("Male");
    const [idNo, setIdNo] = useState("");

    useEffect(() => {
        fetchUsers();
    }, [search, page]);

    useEffect(() => {
        fetchRoles();
        fetchSchoolYears();
    }, []);

    const fetchUsers = async () => {
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.get(`/api/users`, {
                params: {
                    search,
                    page
                },
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setUsers(response.data.data);
            setMeta(response.data.meta);
        } catch (error) {
            // console.error("Error fetching sales:", error);
        }
    };

    const fetchRoles = async () => {
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.get(`/api/users/roles`, {
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setRoles(response.data.data);
        } catch (error) {
            // console.error("Error fetching sales:", error);
        }
    };

    const fetchSchoolYears = async () => {
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.get('/api/schoolYears/lists', {
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setSchoolYears(response.data.data);
            setSchoolYearId(response.data.data.length > 0 ? response.data.data[0].id : "");
        } catch (error) {
            toastr.error('Failed to load school years');
            setSchoolYears([]);
            setSchoolYearId("");
        }
    };

    const handleSearch = (e) => {
        setSearch(e.target.value);
        setPage(1);
    };

    const handleFileChange = (e) => {
        setFile(e.target.files[0]);
    };

    const handleUpload = async () => {
        if (!file) return alert('Please select a file.');
        const formData = new FormData();
        formData.append('file', file);

        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.post('/api/products/upload', formData, {
                headers: { 'Content-Type': 'multipart/form-data',
                    Authorization: `Bearer ${authToken}`
                },
            });
            toastr.success("Success!");
        } catch (error) {
            console.error('Upload error:', error);
        }
    };
    
    const handleEditUser = (user) => {
        setIdNo(user.teacher?.id_no);
        setLastnameOfUser(user.lastname);
        setFirstnameOfUser(user.firstname);
        setExtnameOfUser(user.extname);
        setMiddlenameOfUser(user.middlename);
        setUserNameOfUser(user.username);
        setPasswordOfUser("************");
        setRoleOfUser(user.role_id);
        setIdOfUser(user.id);
        setLevelOfUser(user.teacher?.level);
        setGradeOfUser(user.teacher?.grade);
        setSectionOfUser(user.teacher?.section);
        setStatus(user.teacher?.status || 'Active');
        setEmail(user.teacher?.email);
        setContactNo(user.teacher?.contact_no);
        setAddress(user.teacher?.address);
        setPosition(user.teacher?.position);
        setSex(user.teacher?.sex || 'Male');
        setSchoolYearId(user.teacher?.school_year_id || (schoolYears.length > 0 ? schoolYears[0]?.id : ""));
        setUserModal(true);
    };

    const handleUserSubmit = async () => {
        const isFormValid = validateForm();

        if (isFormValid) {
            if (!idOfUser || idOfUser === "") {
                handleNewUserSubmit();
            } else {
                handleUpdateUserSubmit();
            }
        }
    };

    const validateForm = () => {
        let isValid = true;

        if (!lastnameOfUser || lastnameOfUser === "") {
            toastr.error("Lastname is required!");
            isValid = false;
        }
        if (!firstnameOfUser || firstnameOfUser === "") {
            toastr.error("Firstname is required!");
            isValid = false;
        }
        if (!userNameOfUser || userNameOfUser === "") {
            toastr.error("Username is required!");
            isValid = false;
        }
        if (!passwordOfUser || passwordOfUser === "") {
            toastr.error("Password is required!");
            isValid = false;
        }

        if (Number(roleOfUser) === 3) {
            if (!idNo || idNo === "") {
                toastr.error("ID No. is required!");
                isValid = false;
            }
            if (!contactNo || contactNo === "") {
                toastr.error("Contact No. is required!");
                isValid = false;
            }
            if (!levelOfUser || levelOfUser === "") {
                toastr.error("Level is required!");
                isValid = false;
            }
            if (!gradeOfUser || gradeOfUser === "") {
                toastr.error("Grade is required!");
                isValid = false;
            }
            if (!sectionOfUser || sectionOfUser === "") {
                toastr.error("Section is required!");
                isValid = false;
            }
        }

        return isValid;
    };

    const handleNewUserSubmit = async () => {
        try {
            const formData = new FormData();

            formData.append('id_no', idNo);
            formData.append('lastname', lastnameOfUser);
            formData.append('firstname', firstnameOfUser);
            formData.append('extname', extnameOfUser);
            formData.append('middlename', middlenameOfUser);
            formData.append('username', userNameOfUser);
            formData.append('password', passwordOfUser);
            formData.append('role', roleOfUser);
            formData.append('level', levelOfUser);
            formData.append('grade', gradeOfUser);
            formData.append('section', sectionOfUser);
            formData.append('status', status);
            formData.append('contact_no', contactNo);
            formData.append('email', email);
            formData.append('address', address);
            formData.append('photo', photo);
            formData.append('newPhoto', newPhoto);
            formData.append('position', position);
            formData.append('sex', sex);
            formData.append('school_year_id', schoolYearId);

            const authToken = localStorage.getItem("token");
            const response = await axios.post("/api/users/store", formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    Authorization: `Bearer ${authToken}`,                    
                }
            });

            if (response.status === 200 || response.status === 201) {
                toastr.success("User added successfully!"); 
                setIdNo("");
                setLastnameOfUser("");
                setFirstnameOfUser("");
                setExtnameOfUser("");
                setMiddlenameOfUser("");
                setUserNameOfUser("");
                setPasswordOfUser("");
                setRoleOfUser(2);
                setIdOfUser("");     
                setStatus("Active");
                setEmail("");
                setContactNo("");
                setAddress("");      
                setPosition("");     
                setSex("Male");
                fetchUsers();
                setUserModal(false);
                setGradeOfUser("");
                setSectionOfUser("");
                setSchoolYearId(schoolYears.length > 0 ? schoolYears[0].id : "");
            } else {
                toastr.error("Unexpected response");
            }
        } catch (error) {
            // console.error("Request failed:", error.response?.data?.message || error.message);
            toastr.error("Failed to add user.");
        }
    };

    const handleUpdateUserSubmit = async () => {
        try {
            const formData = new FormData();

            formData.append('id_no', idNo);
            formData.append('lastname', lastnameOfUser);
            formData.append('firstname', firstnameOfUser);
            formData.append('extname', extnameOfUser);
            formData.append('middlename', middlenameOfUser);
            formData.append('username', userNameOfUser);
            formData.append('password', passwordOfUser);
            formData.append('role', roleOfUser);
            formData.append('level', levelOfUser);
            formData.append('grade', gradeOfUser);
            formData.append('section', sectionOfUser);
            formData.append('status', status);
            formData.append('contact_no', contactNo);
            formData.append('email', email);
            formData.append('address', address);
            formData.append('photo', photo);
            formData.append('newPhoto', newPhoto);
            formData.append('position', position);
            formData.append('sex', sex);
            formData.append('school_year_id', schoolYearId);

            const authToken = localStorage.getItem("token");
            const response = await axios.post(`/api/users/update/${idOfUser}`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    Authorization: `Bearer ${authToken}`,
                }
            });

            if (response.status === 200 || response.status === 201) {
                toastr.success("User updated successfully!"); 
                setIdNo("");
                setLastnameOfUser("");
                setFirstnameOfUser("");
                setExtnameOfUser("");
                setMiddlenameOfUser("");
                setUserNameOfUser("");
                setPasswordOfUser("");
                setRoleOfUser(2);
                setIdOfUser("");
                setStatus("Active");
                setEmail("");
                setContactNo("");
                setAddress("");     
                setPosition("");
                setSex("Male");
                setGradeOfUser("");
                setSectionOfUser("");
                fetchUsers();
                setUserModal(false);
                setSchoolYearId(schoolYears.length > 0 ? schoolYears[0].id : "");
            } else {
                toastr.error("Unexpected response");
            }
        } catch (error) {
            const errorMessage =
            error.response?.data?.message ||
            error.response?.data?.errors
                ? Object.values(error.response.data.errors).flat().join(', ')
                : 'An unexpected error occurred.';
            toastr.error(errorMessage, "Failed to update student.");
        }
    };

    const handleUserModalClose  = async () => {
        setIdNo("");
        setLastnameOfUser("");
        setFirstnameOfUser("");
        setExtnameOfUser("");
        setMiddlenameOfUser("");
        setUserNameOfUser("");
        setPasswordOfUser("");
        setStatus("Active");
        setEmail("");
        setContactNo("");
        setAddress("");     
        setPosition("");
        setGradeOfUser("");
        setSectionOfUser(""); 
        setSex("Male");
        setRoleOfUser(2);
        setIdOfUser("");
        setUserModal(false);
        setSchoolYearId(schoolYears.length > 0 ? schoolYears[0].id : "");
    };

    return (
        <Layout>
            <div className="border border-gray-300 shadow-xl rounded-lg p-6 bg-white mx-auto w-full mt-10">
                {/* Header Section */}
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-semibold text-gray-800">Users</h1>
                    <button
                        onClick={() => setUserModal(true)}
                        className="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition"
                    >
                        <Plus size={18} /> New User
                    </button>
                </div>

                <div className="mt-4">
                    {/* Search Input */}
                    <input
                        type="text"
                        placeholder="Search users..."
                        value={search}
                        onChange={handleSearch}
                        className="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full border-collapse border border-gray-300">
                        <thead>
                            <tr className="bg-gray-100 text-gray-700">
                                <th className="border border-gray-300 px-4 py-2 text-left">Name</th>
                                <th className="border border-gray-300 px-4 py-2 text-left">Username</th>
                                <th className="border border-gray-300 px-4 py-2 text-left">Role</th>
                                <th className="border border-gray-300 px-4 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.length > 0 ? (
                                users.map((user, index) => (
                                    <tr key={user.id}>
                                        <td className="border border-gray-300 px-4 py-2">{user.name}</td>
                                        <td className="border border-gray-300 px-4 py-2">{user.username}</td>
                                        <td className="border border-gray-300 px-4 py-2">{user.user_role?.name}</td>
                                        <td className="border border-gray-300 px-4 py-2 gap-2">
                                            <button 
                                                onClick={(e) => handleEditUser(user)}
                                                className="flex items-center gap-1 text-blue-600 hover:underline cursor-pointer">
                                                <Edit size={16} /> Edit
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan="10" className="border border-gray-300 px-4 py-2 text-center cursor-pointer">
                                        No Users found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                
                {/* Pagination Controls */}
                {meta && (
                    <div className="flex justify-between items-center mt-4">
                        <button
                            disabled={!meta.prev}
                            onClick={() => setPage(page - 1)}
                            className={`px-4 py-2 rounded-lg ${meta.prev ? "text-white bg-blue-600 hover:bg-blue-500" : "bg-gray-200 cursor-not-allowed"}`}
                        >
                            Previous
                        </button>
                        <span>
                            Page {meta.current_page} of {meta.last_page}
                        </span>
                        <button
                            disabled={!meta.next}
                            onClick={() => setPage(page + 1)}
                            className={`px-4 py-2 rounded-lg ${meta.next ? "text-white bg-blue-600 hover:bg-blue-500" : "bg-gray-200 cursor-not-allowed"}`}
                        >
                            Next
                        </button>
                    </div>
                )}
            </div>
            
            {/* User Modal Component */}
            <UserModal
                userModal={userModal}
                handleUserModalClose={handleUserModalClose}
                handleUserSubmit={handleUserSubmit}
                idNo={idNo}
                setIdNo={setIdNo}
                setLastnameOfUser={setLastnameOfUser}
                setFirstnameOfUser={setFirstnameOfUser}
                setExtnameOfUser={setExtnameOfUser}
                setMiddlenameOfUser={setMiddlenameOfUser}
                setUserNameOfUser={setUserNameOfUser}
                setPasswordOfUser={setPasswordOfUser}
                setRoleOfUser={setRoleOfUser}
                setLevelOfUser={setLevelOfUser}
                setGradeOfUser={setGradeOfUser}
                setSectionOfUser={setSectionOfUser}
                setStatus={setStatus}
                setNewPhoto={setNewPhoto}
                setPhoto={setPhoto}
                setEmail={setEmail}
                setContactNo={setContactNo}
                setAddress={setAddress}
                setPosition={setPosition}
                setSex={setSex}
                roles={roles}
                isEditingPassword={isEditingPassword}
                setIsEditingPassword={setIsEditingPassword}
                lastnameOfUser={lastnameOfUser}
                firstnameOfUser={firstnameOfUser}
                middlenameOfUser={middlenameOfUser}
                extnameOfUser={extnameOfUser}
                userNameOfUser={userNameOfUser}
                passwordOfUser={passwordOfUser}
                levelOfUser={levelOfUser}
                gradeOfUser={gradeOfUser}
                sectionOfUser={sectionOfUser}
                roleOfUser={roleOfUser}
                status={status}
                newPhoto={newPhoto}
                photo={photo}
                email={email}
                contactNo={contactNo}
                address={address}
                position={position}
                sex={sex}
                schoolYears={schoolYears}
                schoolYearId={schoolYearId}
                setSchoolYearId={setSchoolYearId}
            />
        </Layout>
    );
};

export default UsersList;