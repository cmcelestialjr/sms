import React, { useState, useEffect } from 'react';

const SchoolYearModal = ({ show, onClose, onSave, schoolYear }) => {
    const currentYear = new Date().getFullYear();
    const startYear = 2024; 
    const endYear = currentYear + 1;

    const years = [];
    for (let year = startYear; year <= endYear; year++) {
        years.push(year);
    }    

    const [schoolYearData, setSchoolYearData] = useState({
        sy_from: currentYear,
        sy_to: endYear,
    });

    useEffect(() => {
        if (schoolYear) {
            setSchoolYearData({
                sy_from: schoolYear.sy_from,
                sy_to: schoolYear.sy_to,
            });
        }
    }, [schoolYear]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setSchoolYearData({
            ...schoolYearData,
            [name]: value,
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        onSave(schoolYearData);
        onClose();
    };

    

    if (!show) return null;

    return (
        <div className="fixed inset-0 bg-black/40 flex justify-center items-center z-50">
            <div className="bg-white p-8 rounded-lg max-w-lg w-full shadow-2xl max-h-[90vh] overflow-y-auto relative">
                <h3 className="text-2xl font-semibold mb-6 text-gray-800">
                    {schoolYear ? 'Edit School Year' : 'New School Year'}
                </h3>
                <form onSubmit={handleSubmit}>
                    <div className="mb-6">
                        <label className="block text-sm font-medium mb-2 text-gray-700">Year From</label>
                        <select
                            name="sy_from"
                            value={schoolYearData.sy_from}
                            onChange={handleChange}
                            className="w-full px-4 py-3 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                        {years.map((year) => (
                            <option key={year} value={year}>
                                {year}
                            </option>
                        ))}
                        </select>
                    </div>

                    <div className="mb-6">
                        <label className="block text-sm font-medium mb-2 text-gray-700">Year To</label>
                        <select
                            name="sy_to"
                            value={schoolYearData.sy_to}
                            onChange={handleChange}
                            className="w-full px-4 py-3 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                        {years.map((year) => (
                            <option key={year} value={year}>
                                {year}
                            </option>
                        ))}
                        </select>
                    </div>
                    <div className="flex justify-end space-x-4">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-6 py-3 border border-gray-400 rounded-lg text-gray-800 bg-gray-100 hover:bg-gray-200 focus:outline-none transition-colors cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            className="px-6 py-3 border rounded-lg bg-blue-600 text-white hover:bg-blue-700 focus:outline-none transition-colors cursor-pointer"
                        >
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>

    );
};

export default SchoolYearModal;
