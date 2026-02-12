import React, { useState, useEffect } from "react";
import axios from "axios";
import { Check, CheckCircle, Plus, Save, X, XCircle } from "lucide-react";
import Swal from "sweetalert2";
import toastr from "toastr";
import 'toastr/build/toastr.min.css';
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";
import InputMask from "react-input-mask";

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
                <div className="flex justify-between">
                    <h2 className="text-xl font-semibold">
                        {form.date
                        ? new Intl.DateTimeFormat("en-US", {
                            year: "numeric",
                            month: "long",
                            day: "2-digit",
                        }).format(new Date(form.date))
                        : ""}
                    </h2>
                    <button
                        onClick={() => setFormModal(false)}
                        className="text-gray-500 hover:text-gray-700 transition"
                    >
                        <X size={24} />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid grid-cols-2 gap-3 mt-4">
                        <button
                            type="button"
                            onClick={() => handleStatusChange("present")}
                            className={`flex items-center justify-center gap-2 p-3 rounded-lg border
                                ${form.status === "present"
                                    ? "bg-green-600 text-white border-green-600"
                                    : "bg-white text-gray-700 border-gray-300"
                                }`}
                        >
                            <CheckCircle size={18} />
                            Present
                        </button>

                        <button
                            type="button"
                            onClick={() => handleStatusChange("absent")}
                            className={`flex items-center justify-center gap-2 p-3 rounded-lg border
                                ${form.status === "absent"
                                    ? "bg-red-600 text-white border-red-600"
                                    : "bg-white text-gray-700 border-gray-300"
                                }`}
                        >
                            <XCircle size={18} />
                            Absent
                        </button>
                    </div>

                    {form.status === "present" && (
                        <div className="mt-4 space-y-3">
                            <label className="flex items-center gap-4 p-3 border border-gray-300 rounded-lg cursor-pointer
                                hover:bg-gray-50 transition"
                                onClick={() => handleCheckbox("is_late")}
                            >
                                
                                <div
                                    
                                    className={`w-6 h-6 flex items-center justify-center rounded-md border-2
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

                            <label className="flex items-center gap-4 p-3 border border-gray-300 rounded-lg cursor-pointer
                                hover:bg-gray-50 transition"
                                onClick={() => handleCheckbox("is_undertime")}
                            >
                                
                                <div                                    
                                    className={`w-6 h-6 flex items-center justify-center rounded-md border-2
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

                            <label className="flex items-center gap-4 p-3 border border-gray-300 rounded-lg cursor-pointer
                                hover:bg-gray-50 transition"
                                onClick={() => handleCheckbox("is_excused")}
                            >
                                
                                <div                                    
                                    className={`w-6 h-6 flex items-center justify-center rounded-md border-2
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
                                <label className="block text-sm font-medium mb-1">
                                    Remarks
                                </label>
                                <textarea
                                    name="remarks"
                                    value={form.remarks}
                                    onChange={handleChange}
                                    rows={3}
                                    className="w-full border border-gray-300 rounded-lg p-2"
                                    placeholder="..."
                                />
                            </div>
                        </div>
                    )}
                    <div className="flex justify-between">
                        <button 
                            type="button"
                            onClick={() => setFormModal(false)}
                            className="px-3 py-2 bg-gray-200 text-md text-gray-800 rounded-lg flex items-center gap-1
                                hover:bg-white hover:text-gray-800 hover:border hover:border-gray-800 transition"
                        >
                            <X size={14} />
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            className="px-3 py-2 bg-blue-600 text-md text-white rounded-lg flex items-center gap-1
                                hover:bg-white hover:text-blue-600 hover:border hover:border-blue-600 transition"
                        >
                            <Save size={14} />
                            Save
                        </button>
                    </div>
                </form>

            </div>            
        </div>
    );
};

export default AttendanceSelectedDay;