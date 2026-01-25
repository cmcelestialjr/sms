import React, { useState, useEffect } from 'react';
import { Plus, Users, X } from "lucide-react";

const SchoolYearModal = ({ show, onClose, student }) => {
    

    if (!show) return null;

    return (
        <div className="fixed inset-0 bg-black/50 flex justify-center items-center z-50">
            <div className="bg-white p-6 sm:p-8 rounded-lg max-w-xl w-full shadow-2xl max-h-[90vh] overflow-y-auto relative">
                
                {/* Header */}
                <div className="flex justify-between items-center border-b pb-4 mb-4">
                    <h2 className="text-xl font-semibold text-gray-800">Student Absence Details</h2>
                    <button 
                        className="text-gray-500 cursor-pointer hover:text-gray-700 transition"
                        onClick={onClose}
                        aria-label="Close modal"
                    >
                        <X size={20} />
                    </button>
                </div>

                {/* Student Info */}
                <div className="space-y-2 mb-6">
                    <h3 className="text-gray-700"><span className="font-semibold">Student ID:</span> {student.student_id}</h3>
                    <h3 className="text-gray-700">
                        <span className="font-semibold">Student Name:</span> {student.lastname}, {student.firstname}
                    </h3>
                    <h3 className="text-gray-700">
                        <span className="font-semibold">Total Absence/s:</span> {student.absences.length}
                    </h3>
                </div>

                {/* Absence Dates Grid */}
                <div className="mb-6">
                    <h4 className="font-semibold text-gray-800 mb-3">Absence Dates:</h4>
                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 bg-gray-50 p-4 rounded border border-gray-200 max-h-60 overflow-y-auto">
                        {student.absences.map((absence, index) => (
                            <div
                                key={index}
                                className="text-sm text-gray-700 bg-white border border-gray-300 rounded p-2 text-center shadow-sm"
                            >
                                {new Date(absence.date).toLocaleDateString(undefined, {
                                    year: 'numeric',
                                    month: 'short',
                                    day: 'numeric',
                                })}
                            </div>
                        ))}
                    </div>
                </div>

                {/* Footer */}
                <div className="flex justify-end">
                    <button
                        type="button"
                        onClick={onClose}
                        className="px-5 py-2.5 border border-gray-300 rounded-md text-gray-700 bg-white cursor-pointer hover:bg-gray-100 focus:outline-none transition"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>


    );
};

export default SchoolYearModal;
