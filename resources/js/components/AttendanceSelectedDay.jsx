import React, { useState } from "react";
import axios from "axios";
import { Check, CheckCircle, Save, X, XCircle } from "lucide-react";
import toastr from "toastr";
import 'toastr/build/toastr.min.css';

const AttendanceSelectedDay = ({ formModal, setFormModal, form, setForm, fetchAttendances }) => {
    if (!formModal) return null;

    const [errors, setErrors] = useState({});

    const handleChange = (e) => {
        const { name, value } = e.target;
        setForm(prevForm => ({
            ...prevForm,
            [name]: value
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        const newErrors = {};
        if (!form.student_id) newErrors.student_id = true;
        if (!form.date) newErrors.date = true;

        setErrors(newErrors);

        if (Object.keys(newErrors).length > 0) {
            toastr.error("Please fill in all required fields.");
            return;
        }

        try {
            const authToken = localStorage.getItem("token");

            const config = {
                headers: {
                    Authorization: `Bearer ${authToken}`,
                },
            };

            const formData = {
                student_id: form.student_id,
                status: form.status,
                date: form.date,
                is_late: form.is_late,
                is_undertime: form.is_undertime,
                is_excused: form.is_excused,
                remarks: form.remarks,
                attendance_id: form.attendance_id,
            };

            await axios.put(
                `/api/attendances/daily`,
                formData,
                config
            );

            fetchAttendances();
            setFormModal(false);

        } catch (err) {
            toastr.error("Error saving data");
            console.error(err);
        }
    };

    const handleStatusChange = (status) => {
        setForm(prev => ({
            ...prev,
            status: status,
            is_late: status === "present" ? prev.is_late : 0,
            is_undertime: status === "present" ? prev.is_undertime : 0,
            is_excused: status === "present" ? prev.is_excused : 0,
        }));
    };

    const handleCheckbox = (name) => {
        setForm(prev => ({
            ...prev,
            [name]: prev[name] ? 0 : 1
        }));
    };
    
    return (
        <div className="fixed inset-0 bg-gray-900/40 backdrop-blur-sm flex justify-center items-center z-50">
            <div className="bg-white rounded-lg p-6 shadow-lg max-w-lg w-full max-h-[90vh] overflow-y-auto relative">
                
                {/* Header Section */}
                <div className="flex justify-between items-start border-b border-gray-100 pb-4">
                    <div>
                        <h2 className="text-xl font-bold text-gray-800 tracking-tight">
                            {form.student_name || "Unknown Student"}
                        </h2>
                        <p className="text-sm font-medium text-gray-500 mt-1">
                            {form.date
                                ? new Intl.DateTimeFormat("en-US", {
                                    weekday: "short",
                                    year: "numeric",
                                    month: "long",
                                    day: "2-digit",
                                }).format(new Date(form.date))
                                : "No date selected"}
                        </p>
                    </div>
                    <button
                        onClick={() => setFormModal(false)}
                        className="text-gray-400 hover:text-gray-700 hover:bg-gray-100 p-1 rounded-full transition-colors"
                        aria-label="Close modal"
                    >
                        <X size={24} />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4 pt-2">
                    <div className="grid grid-cols-2 gap-3 mt-4">
                        <button
                            type="button"
                            onClick={() => handleStatusChange("present")}
                            className={`flex items-center justify-center gap-2 p-3 rounded-lg border transition-colors
                                ${form.status === "present"
                                    ? "bg-green-600 text-white border-green-600 shadow-sm"
                                    : "bg-white text-gray-700 border-gray-300 hover:bg-gray-50"
                                }`}
                        >
                            <CheckCircle size={18} />
                            Present
                        </button>

                        <button
                            type="button"
                            onClick={() => handleStatusChange("absent")}
                            className={`flex items-center justify-center gap-2 p-3 rounded-lg border transition-colors
                                ${form.status === "absent"
                                    ? "bg-red-600 text-white border-red-600 shadow-sm"
                                    : "bg-white text-gray-700 border-gray-300 hover:bg-gray-50"
                                }`}
                        >
                            <XCircle size={18} />
                            Absent
                        </button>
                    </div>

                    {form.status === "present" && (
                        <div className="mt-4 space-y-3">
                            <label className="flex items-center gap-4 p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                <div
                                    className={`w-6 h-6 flex items-center justify-center rounded-md border-2 transition-colors
                                        ${form.is_late === 1
                                            ? "bg-blue-600 border-blue-600"
                                            : "border-gray-300 bg-white"
                                        }`}
                                >
                                    {form.is_late === 1 && (
                                        <Check size={16} className="text-white" />
                                    )}
                                </div>
                                <span className="text-sm font-medium text-gray-800">
                                    Late
                                </span>
                            </label>

                            <label className="flex items-center gap-4 p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                <div                    
                                    className={`w-6 h-6 flex items-center justify-center rounded-md border-2 transition-colors
                                        ${form.is_undertime === 1
                                            ? "bg-blue-600 border-blue-600"
                                            : "border-gray-300 bg-white"
                                        }`}
                                >
                                    {form.is_undertime === 1 && (
                                        <Check size={16} className="text-white" />
                                    )}
                                </div>
                                <span className="text-sm font-medium text-gray-800">
                                    Undertime
                                </span>
                            </label>

                            <label className="flex items-center gap-4 p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                <div                    
                                    className={`w-6 h-6 flex items-center justify-center rounded-md border-2 transition-colors
                                        ${form.is_excused === 1
                                            ? "bg-blue-600 border-blue-600"
                                            : "border-gray-300 bg-white"
                                        }`}
                                >
                                    {form.is_excused === 1 && (
                                        <Check size={16} className="text-white" />
                                    )}
                                </div>
                                <span className="text-sm font-medium text-gray-800">
                                    Excused
                                </span>
                            </label>
                            
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Remarks
                                </label>
                                <textarea
                                    name="remarks"
                                    value={form.remarks}
                                    onChange={handleChange}
                                    rows={3}
                                    className="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-shadow"
                                    placeholder="Add any notes here..."
                                />
                            </div>
                        </div>
                    )}
                    
                    <div className="flex justify-between items-center mt-6 pt-4 border-t border-gray-100">
                        <button 
                            type="button"
                            onClick={() => setFormModal(false)}
                            className="px-4 py-2 bg-gray-100 text-sm font-medium text-gray-700 rounded-lg flex items-center gap-2 hover:bg-gray-200 transition"
                        >
                            <X size={16} />
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            className="px-4 py-2 bg-blue-600 text-sm font-medium text-white rounded-lg flex items-center gap-2 hover:bg-blue-700 transition shadow-sm"
                        >
                            <Save size={16} />
                            Save Attendance
                        </button>
                    </div>
                </form>

            </div>            
        </div>
    );
};

export default AttendanceSelectedDay;