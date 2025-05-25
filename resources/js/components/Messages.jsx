import React, { useEffect, useState } from "react";
import axios from "axios";
import { Plus, Users, X } from "lucide-react";
import Layout from "./Layout";
import toastr from "toastr";
import "toastr/build/toastr.min.css";

const Messages = () => {
    const authToken = localStorage.getItem("token");
    const userRole = localStorage.getItem("userRole");
    const [meta, setMeta] = useState(null);
    const [search, setSearch] = useState("");
    const [page, setPage] = useState(1);
    const [messages, setMessages] = useState([]);
    const [showModal, setShowModal] = useState(false);
    const [audienceType, setAudienceType] = useState("student");
    const [grades, setGrades] = useState([]);
    const [sections, setSections] = useState([]);
    const [recipients, setRecipients] = useState([]);
    const [message, setMessage] = useState("");
    const [suggestions, setSuggestions] = useState([]);
    const [showModalErrors, setShowModalErrors] = useState(false);
    const [errorData, setErrorData] = useState({ students: [], teachers: [], id:null });
    const [showModalUsers, setShowModalUsers] = useState(false);
    const [usersData, setUsersData] = useState({ students: [], teachers: [], id:null });

    useEffect(() => {
        fetchGrades();
        fetchSections();
    }, []);

    useEffect(() => {
        fetchMessages();
    }, [page, search]);
    
    const handleSearch = (e) => {
        setSearch(e.target.value);
        setPage(1);
    };

    const fetchMessages = async () => {
        try {            
            const res = await axios.get("/api/messages", {
                params: {
                    page: page,
                    search: search,
                },
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setMessages(res.data.data);
            setMeta(response.data.meta);                        
        } catch (error) {
            
        }
    };

    const fetchGrades = async () => {
        try {
            const res = await axios.get("/api/grades", {
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setGrades(res.data);
        } catch (error) {
            
        }
    };

    const fetchSections = async () => {
        try {
            const res = await axios.get("/api/sections", {
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setSections(res.data);
        } catch (error) {
            
        }
    };

    const handleSubmit = async () => {
        try {
            const finalRecipients = (audienceType === "student" || audienceType === "teacher")
                ? recipients.map(r => r.id)
                : recipients;
            await axios.post(
                "/api/messages",
                {
                    audience_type: audienceType,
                    recipients: finalRecipients,
                    message,
                },
                {
                    headers: {
                        Authorization: `Bearer ${authToken}`,
                    },
                }
            );
            toastr.success("Message sent successfully");
            setShowModal(false);
            fetchMessages();
        } catch (err) {
            toastr.error("Failed to send message");
        }
    };

    const handleSearchChange = async (e) => {
        const value = e.target.value;
        setSearch(value);

        if (value.length >= 2) {
            try {
                const res = await axios.get(`/api/${audienceType}s/search`, {
                    params: { q: value, f: 'message' },
                    headers: { Authorization: `Bearer ${authToken}` },
                });
                setSuggestions(res.data);
            } catch (err) {
                setSuggestions([]);
            }
        } else {
            setSuggestions([]);
        }
    };


    const renderAudienceInput = () => {
        if (audienceType === "student" || audienceType === "teacher") {
            return (
                <div>
                    <input
                        type="text"
                        placeholder={`Search ${audienceType}...`}
                        className="w-full border px-3 py-2 rounded"
                        value={search}
                        onChange={handleSearchChange}
                    />
                    {suggestions.length > 0 && (
                        <ul className="border mt-1 rounded shadow bg-white max-h-40 overflow-y-auto">
                            {suggestions.map((user) => (
                                <li
                                    key={user.id}
                                    className="px-3 py-2 hover:bg-gray-100 cursor-pointer flex justify-between items-center"
                                    onClick={() => {
                                            if (!recipients.some(r => r.id === user.id)) {
                                                setRecipients([...recipients, user]);
                                            }
                                            setSearch("");
                                            setSuggestions([]);
                                    }}
                                >
                                    <span>
                                        {user.lastname}, {user.firstname} {user.extname} {user.middlename}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}

                    {recipients.length > 0 && (
                        <div className="flex flex-wrap gap-2 mt-2">
                            {recipients.map((user) => (
                                <span
                                    key={user.id}
                                    className="bg-blue-100 text-blue-800 px-3 py-1 rounded-full flex items-center"
                                >
                                    {user.lastname}, {user.firstname} {user.extname} {user.middlename}
                                    <button
                                        className="ml-2 text-red-600 cursor-pointer"
                                        onClick={() =>
                                            setRecipients(recipients.filter(r => r.id !== user.id))
                                        }
                                    >
                                        &times;
                                    </button>
                                </span>
                            ))}
                        </div>
                    )}
                </div>
            );
        }

        if (audienceType === "by_grade") {
            return (
                <select
                    className="w-full border px-3 py-2 rounded"
                    onChange={(e) => setRecipients([e.target.value])}
                >
                    <option value="">Select Grade</option>
                    {grades.map((grade, i) => (
                        <option key={i} value={grade}>{grade}</option>
                    ))}
                </select>
            );
        }

        if (audienceType === "by_section") {
            return (
                <select
                    className="w-full border px-3 py-2 rounded"
                    onChange={(e) => setRecipients([e.target.value])}
                >
                    <option value="">Select Section</option>
                    {sections.map((section, i) => (
                        <option key={i} value={section}>{section}</option>
                    ))}
                </select>
            );
        }

        return null;
    };

    const ModalErrors = ({ students, teachers, id, onResend, onClose }) => (
        <div className="fixed inset-0 bg-black/50 flex justify-center items-center z-50">
            <div className="bg-white p-6 rounded-lg shadow-xl w-full max-w-lg">
                <h2 className="text-lg font-bold mb-2">Failed Deliveries</h2>

                {teachers?.length > 0 && (
                    <div className="mb-4">
                        <h3 className="font-semibold">Teachers</h3>
                        <button
                            onClick={() => onResend('teacher', teachers, id)}
                            className="mt-2 px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
                        >
                            Resend to Teachers
                        </button>
                        <ul className="list-disc list-inside">
                            {teachers.map((t, idx) => (
                                <li key={idx}>
                                    {t.teacher?.lastname}, {t.teacher?.firstname}
                                </li>
                            ))}
                        </ul>
                        
                    </div>
                )}

                {students?.length > 0 && (
                    <div>
                        <h3 className="font-semibold">Students</h3>
                        <button
                            onClick={() => onResend('student', students, id)}
                            className="mt-2 px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
                        >
                            Resend to Students
                        </button>
                        <ul className="list-disc list-inside">
                            {students.map((s, idx) => (
                                <li key={idx}>
                                    {s.student?.lastname}, {s.student?.firstname}
                                </li>
                            ))}
                        </ul>                        
                    </div>
                )}

                <button
                    onClick={onClose}
                    className="mt-4 px-4 py-2 bg-gray-300 rounded hover:bg-gray-400 cursor-pointer"
                >
                    Close
                </button>
            </div>
        </div>
    );

    const handleOnResend = async (type, recipients, id) => {
        try {
            await axios.post(
                "/api/messages/resend",
                {
                    id: id,
                    type: type,
                    recipients: recipients,
                },
                {
                    headers: {
                        Authorization: `Bearer ${authToken}`,
                    },
                }
            );
            toastr.success("Message sent successfully");
            setShowModal(false);
            fetchMessages();
        } catch (err) {
            toastr.error("Failed to send message");
        }
    };

    const ModalUsers = ({ students, teachers, id, onClose }) => (
        <div className="fixed inset-0 bg-black/50 flex justify-center items-center z-50">
            <div className="bg-white p-6 rounded-lg shadow-xl w-full max-w-lg">
                <h2 className="text-lg font-bold mb-2">Success Deliveries</h2>

                {teachers?.length > 0 && (
                    <div className="mb-4">
                        <h3 className="font-semibold">Teachers</h3>
                        <ul className="list-disc list-inside">
                            {teachers.map((t, idx) => (
                                <li key={idx}>
                                    {t.teacher?.lastname}, {t.teacher?.firstname}
                                </li>
                            ))}
                        </ul>
                        
                    </div>
                )}

                {students?.length > 0 && (
                    <div>
                        <h3 className="font-semibold">Students</h3>
                        <ul className="list-disc list-inside">
                            {students.map((s, idx) => (
                                <li key={idx}>
                                    {s.student?.lastname}, {s.student?.firstname}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                <button
                    onClick={onClose}
                    className="mt-4 px-4 py-2 bg-gray-300 rounded hover:bg-gray-400 cursor-pointer"
                >
                    Close
                </button>
            </div>
        </div>
    );

    return (
        <Layout>
            <div className="border border-gray-300 shadow-xl rounded-lg p-6 bg-white mx-auto w-full mt-10">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-semibold text-gray-800">Messages</h1>
                    <button
                        onClick={() => setShowModal(true)}
                        className="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition cursor-pointer"
                    >
                        <Plus size={18} /> Create Message
                    </button>
                </div>

                {/* Messages Table */}
                <div className="overflow-x-auto">
                    <table className="min-w-full text-sm text-left border-collapse border border-gray-300">
                        <thead className="bg-gray-100 text-xs text-gray-700 uppercase">
                            <tr>
                                <th className="border border-gray-300 px-4 py-2">Send By</th>
                                <th className="border border-gray-300 px-4 py-2">Audience</th>
                                <th className="border border-gray-300 px-4 py-2">Message</th>
                                {/* <th className="border border-gray-300 px-4 py-2">Recipient</th> */}
                                <th className="border border-gray-300 px-4 py-2">Options</th>
                            </tr>
                        </thead>
                        <tbody>
                            {messages?.map((msg) => {
                                const students = msg.targets?.filter(t => t.student);
                                const teachers = msg.targets?.filter(t => t.teacher);
                                const errorStudents = msg.target_errors?.filter(t => t.student);
                                const errorTeachers = msg.target_errors?.filter(t => t.teacher);
                                
                                return (
                                <tr key={msg.id}>
                                    <td className="border border-gray-300 px-4 py-2">
                                        {msg.user?.lastname}, {msg.user?.firstname} {msg.user?.extname} {msg.user?.middlename}
                                    </td>
                                    <td className="border border-gray-300 px-4 py-2">{msg.audience_type}</td>
                                    <td className="border border-gray-300 px-4 py-2">{msg.message}</td>
                                    {/* <td className="border border-gray-300 px-4 py-2">{msg.message}</td> */}
                                    <td className="border border-gray-300 px-4 py-2">
                                        <div className="flex items-center gap-2">
                                            <button
                                                onClick={() => {
                                                    setErrorData({ students: errorStudents, teachers: errorTeachers, id:msg.id });
                                                    setShowModalErrors(true);
                                                }}
                                                className="flex items-center gap-1 text-red-600 hover:underline cursor-pointer"
                                            >
                                                <X size={16} />
                                            </button>
                                            <button
                                                onClick={() => {
                                                    console.log(teachers);
                                                    setUsersData({ students: students, teachers: teachers, id: msg.id });
                                                    setShowModalUsers(true);
                                                }}
                                                className="flex items-center gap-1 text-blue-600 hover:underline cursor-pointer"
                                            >
                                                <Users size={16} />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                )}
                            )}
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

            {/* Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black/50 flex justify-center items-center z-50">
                    <div className="bg-white p-6 rounded-lg shadow-xl w-full max-w-lg">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="text-lg font-semibold">Create Message</h2>
                            <button onClick={() => setShowModal(false)}>
                                <X size={20} />
                            </button>
                        </div>

                        <div className="mb-4">
                            <label className="block font-medium mb-1">Audience Type</label>
                            <select
                                className="w-full border px-3 py-2 rounded"
                                value={audienceType}
                                onChange={(e) => {
                                    setAudienceType(e.target.value);
                                    setSearch("");
                                    setSuggestions([]);
                                    setRecipients([]);
                                }}
                            >
                                {userRole == 3 && (
                                    <>
                                    <option value="student">Search Student</option>
                                    <option value="students">All Students</option>
                                    </>
                                )}

                                {userRole < 3 && (
                                    <>
                                    <option value="student">Search Student</option>
                                    <option value="teacher">Search Teacher</option>
                                    <option value="all">All</option>
                                    <option value="students">All Students</option>
                                    <option value="teachers">All Teachers</option>
                                    <option value="by_grade">By Grade</option>
                                    <option value="by_section">By Section</option>
                                    </>
                                )}
                            </select>
                        </div>

                        <div className="mb-4">
                            <label className="block font-medium mb-1">Recipient</label>
                            {renderAudienceInput()}
                        </div>

                        <div className="mb-4">
                            <label className="block font-medium mb-1">Message</label>
                            <textarea
                                rows="4"
                                className="w-full border px-3 py-2 rounded"
                                value={message}
                                onChange={(e) => setMessage(e.target.value)}
                            ></textarea>
                        </div>

                        <div className="flex justify-end">
                            <button
                                onClick={handleSubmit}
                                className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                            >
                                Send Message
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {showModalErrors && (
                <ModalErrors
                    students={errorData.students}
                    teachers={errorData.teachers}
                    id={errorData.id}
                    onResend={handleOnResend}
                    onClose={() => setShowModalErrors(false)}
                />
            )}

            {showModalUsers && (
                <ModalUsers
                    students={usersData.students}
                    teachers={usersData.teachers}
                    id={usersData.id}
                    onClose={() => setShowModalUsers(false)}
                />
            )}
        </Layout>
    );
};

export default Messages;
