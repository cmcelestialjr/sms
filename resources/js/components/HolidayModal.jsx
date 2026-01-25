import { X } from 'lucide-react';
import React, { useState, useEffect } from 'react';

const HolidayModal = ({ show, onClose, onSave, holiday }) => {
    const [name, setName] = useState(null);
    const [date_from, setDateFrom] = useState(null);
    const [date_to, setDateTo] = useState(null);
    const [type, setType] = useState("Regular");
    const [repeat, setRepeat] = useState("No");
    const [half_day, setHalfDay] = useState("");

    const [holidayData, setHolidayData] = useState({
        name: name,
        date_from: date_from,
        date_to: date_to,
        type: type,
        repeat: repeat,
        half_day: half_day,
    });

    useEffect(() => {
        if (holiday) {
            setHolidayData({
                name: holiday.name,
                date_from: holiday.date_from,
                date_to: holiday.date_to,
                type: holiday.type,
                repeat: holiday.repeat,
                half_day: holiday.half_day,
            });
        }
    }, [holiday]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setHolidayData({
            ...holidayData,
            [name]: value,
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        onSave(holidayData);
        onClose();
    };    

    if (!show) return null;

    return (
        <div className="fixed inset-0 bg-black/40 flex justify-center items-center z-50">
            <div className="bg-white p-8 rounded-lg max-w-lg w-full shadow-2xl max-h-[90vh] overflow-y-auto relative">
                <div className="flex justify-between items-center mb-3">
                    <h3 className="text-xl font-semibold mb-6 text-gray-800">
                        {holiday ? 'Edit Holiday' : 'New Holiday'}
                    </h3>
                    <button 
                        className="text-xl font-semibold text-gray-600 cursor-pointer"
                        onClick={onClose}
                    >
                        <X size={16} />
                    </button>
                </div>
                <form onSubmit={handleSubmit}>
                    {/* Name Input */}
                    <div className="mb-6">
                        <label className="block text-sm font-medium mb-1 text-gray-700">Name</label>
                        <input
                            type="text"
                            name="name"
                            value={holidayData.name}
                            onChange={handleChange}
                            className="w-full px-4 py-3 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        />
                    </div>

                    {/* Date From Input */}
                    <div className="mb-6">
                        <label className="block text-sm font-medium mb-1 text-gray-700">Date From</label>
                        <input
                            type="date"
                            name="date_from"
                            value={holidayData.date_from}
                            onChange={handleChange}
                            className="w-full px-4 py-3 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        />
                    </div>

                    {/* Date To Input */}
                    <div className="mb-6">
                        <label className="block text-sm font-medium mb-1 text-gray-700">Date To</label>
                        <input
                            type="date"
                            name="date_to"
                            value={holidayData.date_to}
                            onChange={handleChange}
                            className="w-full px-4 py-3 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        />
                    </div>

                    {/* Type Select */}
                    <div className="mb-6">
                        <label className="block text-sm font-medium mb-1 text-gray-700">Type</label>
                        <select
                            name="type"
                            value={holidayData.type}
                            onChange={handleChange}
                            className="w-full px-4 py-3 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                            <option key="0" value="Regular">
                                Regular
                            </option>
                            <option key="1" value="Special">
                                Special
                            </option>
                        </select>
                    </div>

                    {/* Repeat Yearly Select */}
                    <div className="mb-6">
                        <label className="block text-sm font-medium mb-1 text-gray-700">Repeat Yearly?</label>
                        <select
                            name="repeat"
                            value={holidayData.repeat}
                            onChange={handleChange}
                            className="w-full px-4 py-3 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                            <option key="0" value="Yes">
                                Yes
                            </option>
                            <option key="1" value="No">
                                No
                            </option>
                        </select>
                    </div>

                    {/* Half Day Select */}
                    <div className="mb-6">
                        <label className="block text-sm font-medium mb-1 text-gray-700">Half Day?</label>
                        <select
                            name="half_day"
                            value={holidayData.half_day}
                            onChange={handleChange}
                            className="w-full px-4 py-3 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option key="0" value="">
                                Whole Day
                            </option>
                            <option key="1" value="am">
                                AM
                            </option>
                            <option key="2" value="pm">
                                PM
                            </option>
                        </select>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex justify-end gap-3 pt-4">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-5 py-2 border border-gray-300 text-gray-700 bg-white rounded-md hover:bg-gray-100 transition-colors cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            className="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors cursor-pointer"
                        >
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>


    );
};

export default HolidayModal;
