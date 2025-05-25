import React, { useState, useEffect } from 'react';

const StationModal = ({ show, onClose, onSave, station }) => {
    const [stationData, setStationData] = useState({
        station_name: '',
        location: '',
    });

    useEffect(() => {
        if (station) {
            setStationData({
                station_name: station.station_name,
                location: station.location,
            });
        }
    }, [station]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setStationData((prev) => ({
            ...prev,
            [name]: value,
        }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        onSave(stationData);
        onClose();
    };

    if (!show) return null;

    return (
        <div className="fixed inset-0 bg-black/40 flex justify-center items-center z-50">
            <div className="bg-white p-8 rounded-lg max-w-lg w-full shadow-2xl max-h-[90vh] overflow-y-auto relative">
                <h3 className="text-2xl font-semibold mb-6 text-gray-800">
                    {station ? 'Edit Station' : 'Add Station'}
                </h3>
                <form onSubmit={handleSubmit}>
                    <div className="mb-6">
                        <label className="block text-sm font-medium mb-2 text-gray-700">Station Name</label>
                        <input
                            type="text"
                            name="station_name"
                            value={stationData.station_name}
                            onChange={handleChange}
                            className="w-full px-4 py-3 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        />
                    </div>
                    <div className="mb-6">
                        <label className="block text-sm font-medium mb-2 text-gray-700">Location</label>
                        <input
                            type="text"
                            name="location"
                            value={stationData.location}
                            onChange={handleChange}
                            className="w-full px-4 py-3 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        />
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

export default StationModal;
