import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Layout from "./Layout";
import { Edit, Plus } from "lucide-react";
import StationModal from './StationModal';
import toastr from 'toastr';
import Swal from "sweetalert2";
import 'toastr/build/toastr.min.css';
import "react-datepicker/dist/react-datepicker.css";

const Dtrs = () => {
    const [meta, setMeta] = useState(null);
    const [search, setSearch] = useState("");
    const [page, setPage] = useState(1);
    const [stations, setStations] = useState([]);
    const [showModal, setShowModal] = useState(false);
    const [currentStation, setCurrentStation] = useState(null);

    const didFetch = useRef(false);

    useEffect(() => {
        if (didFetch.current) return;
        didFetch.current = true;
    }, []);

    useEffect(() => {
        fetchStations();
    }, [page, search]);

    const handleSearch = (e) => {
        setSearch(e.target.value);
        setPage(1);
    };

    const fetchStations = async () => {
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.get('/api/stations-lists', {
                params: {
                    page: page,
                    search: search,
                },
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setStations(response.data.data);
            setMeta(response.data.meta);
        } catch (error) {
            // toastr.error('Failed to load stations');
        }
    };

    const handleAdd = () => {
        setCurrentStation(null); 
        setShowModal(true);
    };

    const handleEdit = (station) => {
        setCurrentStation(station);
        setShowModal(true);
    };

    const handleSaveStation = async (stationData) => {
        try {
            const authToken = localStorage.getItem("token");
            if (currentStation) {
                await axios.put(`/api/stations/${currentStation.id}`, stationData, {
                    headers: { Authorization: `Bearer ${authToken}` },
                });
                toastr.success('Station updated successfully');
            } else {
                await axios.post('/api/stations', stationData, {
                    headers: { Authorization: `Bearer ${authToken}` },
                });
                toastr.success('Station added successfully');
            }
            fetchStations();
        } catch (error) {
            toastr.error('Failed to save station');
        }
    };

    return (
        <Layout>
            <div className="border border-gray-300 shadow-xl rounded-lg p-6 bg-white mx-auto w-full mt-10">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-semibold text-gray-800">Stations</h1>
                    <button
                        onClick={handleAdd}
                        className="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition cursor-pointer"
                    >
                        <Plus size={18} /> New Station
                    </button>
                </div>

                {/* Search */}
                <div className="grid grid-cols-1 md:grid-cols-1 gap-4 mb-4">
                    <input
                        type="text"
                        placeholder="Search stations..."
                        value={search}
                        onChange={handleSearch}
                        className="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                {/* Stations Table */}
                <div className="overflow-x-auto">
                    <table className="min-w-full text-sm text-left border-collapse border border-gray-300">
                        <thead className="bg-gray-100 text-xs text-gray-700 uppercase">
                            <tr>
                                <th className="border border-gray-300 px-4 py-2">Station Name</th>
                                <th className="border border-gray-300 px-4 py-2">Location</th>
                                <th className="border border-gray-300 px-4 py-2">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {stations?.map((station) => (
                                <tr key={station.id} className="border-t">
                                    <td className="border border-gray-300 px-4 py-2">{station.station_name}</td>
                                    <td className="border border-gray-300 px-4 py-2 text-center">{station.location}</td>
                                    <td className="border border-gray-300 px-4 py-2 text-center">
                                        <button
                                            onClick={() => handleEdit(station)}
                                            className="flex items-center gap-1 text-blue-600 hover:underline cursor-pointer"
                                        >
                                            <Edit size={16} /> Edit
                                        </button>
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

            {/* Station Modal */}
            <StationModal
                show={showModal}
                onClose={() => setShowModal(false)}
                onSave={handleSaveStation}
                station={currentStation}
            />
        </Layout>
    );
};

export default Dtrs;
