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

            const formData = form

            const config = {
                headers: {
                    Authorization: `Bearer ${authToken}`,
                },
            };

            await axios.put(`/api/attendances/${form.employee_id}`, formData, config);

            setForm({
                student_id: "",
                date: "",
                is_late: 0,
                is_undertime: 0,
                is_excused: 0
            });
            
            fetchAttendances();
            setFormModal(false);
        } catch (err) {
            toastr.error("Error saving data"+err);
        }
    };
    

    return (
        <div className="fixed top-0 left-0 right-0 bottom-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
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
                    <div className="mt-3 grid grid-cols-1 gap-2">
                        <div className="mt-4 flex items-center">
                            <div className="flex items-center space-x-1">
                                <label className="text-sm font-medium text-gray-700">
                                    Daily Schedule
                                </label>
                            </div>
                        </div>
                        <div className="mt-1 flex justify-between items-center">
                            <div>
                                <label className="block mb-1">
                                    <strong>Schedule Time In:</strong>
                                </label>
                                <DatePicker
                                    selected={regularTimeIn}
                                    onChange={(time) => setRegularTimeIn(time)}
                                    showTimeSelect
                                    showTimeSelectOnly
                                    timeIntervals={15}
                                    timeCaption="Time In"
                                    dateFormat="hh:mm aa"
                                    className="border px-2 py-2 rounded-lg w-full"
                                    customInput={
                                        <InputMask
                                            mask="99:99 aa"
                                            maskChar=" "
                                            value={regularTimeIn ? regularTimeIn.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'}) : ''}
                                            onChange={(e) => {
                                                const value = e.target.value;
                                                const date = new Date();
                                                const [hours, minutes] = value.split(':');
                                                if (hours && minutes) {
                                                    date.setHours(parseInt(hours));
                                                    date.setMinutes(parseInt(minutes));
                                                    setRegularTimeIn(date);
                                                }
                                            }}
                                        >
                                            {(inputProps) => <input {...inputProps} />}
                                        </InputMask>
                                    }
                                />
                            </div>
                            <div>
                                <label className="block mb-1">
                                    <strong>Schedule Time Out:</strong>
                                </label>
                                <DatePicker
                                    selected={regularTimeOut}
                                    onChange={(time) => setRegularTimeOut(time)}
                                    showTimeSelect
                                    showTimeSelectOnly
                                    timeIntervals={15}
                                    timeCaption="Time Out"
                                    dateFormat="hh:mm aa"
                                    className="border px-2 py-2 rounded-lg w-full"
                                    customInput={
                                        <InputMask
                                            mask="99:99 aa"
                                            maskChar=" "
                                            value={regularTimeOut ? regularTimeOut.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'}) : ''}
                                            onChange={(e) => {
                                                const value = e.target.value;
                                                const date = new Date();
                                                const [hours, minutes] = value.split(':');
                                                if (hours && minutes) {
                                                    date.setHours(parseInt(hours));
                                                    date.setMinutes(parseInt(minutes));
                                                    setRegularTimeOut(date);
                                                }
                                            }}
                                        >
                                            {(inputProps) => <input {...inputProps} />}
                                        </InputMask>
                                    }
                                />
                            </div>
                        </div>
                        <div className="mt-4 flex items-center">
                            <div className="flex items-center space-x-3">
                                <label className="text-sm font-medium text-gray-700">
                                    OverTime Schedule
                                </label>
                            </div>
                        </div>
                        <div className="mt-1 flex justify-between items-center">
                            <div>
                                <label className="block mb-1">
                                    <strong>Over-Time In:</strong>
                                </label>
                                <DatePicker
                                    selected={overTimeIn || null}
                                    onChange={(time) => setOverTimeIn(time)}
                                    showTimeSelect
                                    showTimeSelectOnly
                                    timeIntervals={15}
                                    timeCaption="Time In"
                                    dateFormat="hh:mm aa"
                                    className="border px-3 py-2 rounded-lg w-full"
                                    customInput={
                                        <InputMask
                                            mask="99:99 aa"
                                            maskChar=" "
                                            value={overTimeIn ? overTimeIn.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'}) : ''}
                                            onChange={(e) => {
                                                const value = e.target.value;
                                                const date = new Date();
                                                const [hours, minutes] = value.split(':');
                                                if (hours && minutes) {
                                                    date.setHours(parseInt(hours));
                                                    date.setMinutes(parseInt(minutes));
                                                    setOverTimeIn(date);
                                                }
                                            }}
                                        >
                                            {(inputProps) => <input {...inputProps} />}
                                        </InputMask>
                                    }
                                />
                            </div>
                            <div>
                                <label className="block mb-1">
                                    <strong>Over-Time Out:</strong>
                                </label>
                                <DatePicker
                                    selected={overTimeOut || null}
                                    onChange={(time) => setOverTimeOut(time)}
                                    showTimeSelect
                                    showTimeSelectOnly
                                    timeIntervals={15}
                                    timeCaption="Time Out"
                                    dateFormat="hh:mm aa"
                                    className="border px-3 py-2 rounded-lg w-full"
                                    customInput={
                                        <InputMask
                                            mask="99:99 aa"
                                            maskChar=" "
                                            value={overTimeOut ? overTimeOut.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'}) : ''}
                                            onChange={(e) => {
                                                const value = e.target.value;
                                                const date = new Date();
                                                const [hours, minutes] = value.split(':');
                                                if (hours && minutes) {
                                                    date.setHours(parseInt(hours));
                                                    date.setMinutes(parseInt(minutes));
                                                    setOverTimeOut(date);
                                                }
                                            }}
                                        >
                                            {(inputProps) => <input {...inputProps} />}
                                        </InputMask>
                                    }
                                />
                            </div>
                        </div>
                        <div className="mt-4 flex items-center">
                            <div className="flex items-center space-x-3">
                                <input
                                    type="checkbox"
                                    checked={sameTimeChecked}
                                    onChange={handleCheckboxChange}
                                    id="same-time-checkbox"
                                    className="h-5 w-5 text-blue-600 border-gray-300 rounded-md focus:ring-blue-500"
                                />
                                <label htmlFor="same-time-checkbox" className="text-sm font-medium text-gray-700">
                                    Set Actual Time Same as Schedule Time
                                </label>
                            </div>
                        </div>
                        <div className="mt-2 mb-4 flex justify-between items-center">
                            <div>
                                <label className="block mb-1">
                                    <strong>Actual Time In:</strong>
                                </label>
                                <DatePicker
                                    selected={actualTimeIn || null}
                                    onChange={(time) => setActualTimeIn(time)}
                                    showTimeSelect
                                    showTimeSelectOnly
                                    timeIntervals={15}
                                    timeCaption="Time In"
                                    dateFormat="hh:mm aa"
                                    className="border px-3 py-2 rounded-lg w-full"
                                    customInput={
                                        <InputMask
                                            mask="99:99 aa"
                                            maskChar=" "
                                            value={actualTimeIn ? actualTimeIn.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'}) : ''}
                                            onChange={(e) => {
                                                const value = e.target.value;
                                                const date = new Date();
                                                const [hours, minutes] = value.split(':');
                                                if (hours && minutes) {
                                                    date.setHours(parseInt(hours));
                                                    date.setMinutes(parseInt(minutes));
                                                    setActualTimeIn(date);
                                                }
                                            }}
                                        >
                                            {(inputProps) => <input {...inputProps} />}
                                        </InputMask>
                                    }
                                />
                            </div>
                            <div>
                                <label className="block mb-1">
                                    <strong>Actual Time Out:</strong>
                                </label>
                                <DatePicker
                                    selected={actualTimeOut || null}
                                    onChange={(time) => setActualTimeOut(time)}
                                    showTimeSelect
                                    showTimeSelectOnly
                                    timeIntervals={15}
                                    timeCaption="Time Out"
                                    dateFormat="hh:mm aa"
                                    className="border px-3 py-2 rounded-lg w-full"
                                    customInput={
                                        <InputMask
                                            mask="99:99 aa"
                                            maskChar=" "
                                            value={actualTimeOut ? actualTimeOut.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'}) : ''}
                                            onChange={(e) => {
                                                const value = e.target.value;
                                                const date = new Date();
                                                const [hours, minutes] = value.split(':');
                                                if (hours && minutes) {
                                                    date.setHours(parseInt(hours));
                                                    date.setMinutes(parseInt(minutes));
                                                    setActualTimeOut(date);
                                                }
                                            }}
                                        >
                                            {(inputProps) => <input {...inputProps} />}
                                        </InputMask>
                                    }
                                />
                            </div>
                        </div>                        
                    </div>
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