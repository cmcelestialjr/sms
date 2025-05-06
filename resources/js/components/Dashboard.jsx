import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Layout from "./Layout";
import { Edit, Plus, X, } from "lucide-react";
import toastr from 'toastr';
import Swal from "sweetalert2";
import 'toastr/build/toastr.min.css';
import "react-datepicker/dist/react-datepicker.css";

const Dashboard = () => {
    const [meta, setMeta] = useState(null);
    const [search, setSearch] = useState("");
    const [page, setPage] = useState(1);

    const didFetch = useRef(false);

    useEffect(() => {
        if (didFetch.current) return;
        didFetch.current = true;

    }, []);

    const handleSearch = (e) => {
        setSearch(e.target.value);
        setPage(1);
    };

    const handleDamageNew = () => {

    };


    return (
        <Layout>
            <div className="border border-gray-300 shadow-xl rounded-lg p-6 bg-white mx-auto w-full mt-10">
                {/* Header Section */}
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-semibold text-gray-800">Damaged</h1>
                    <button
                        onClick={handleDamageNew}
                        className="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition"
                    >
                        <Plus size={18} /> New Damage
                    </button>
                </div>

                {/* Filters */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    {/* Search Input */}
                    <input
                        type="text"
                        placeholder="Search damaged..."
                        value={search}
                        onChange={handleSearch}
                        className="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>
                
                {/* Damage Table */}
                <div className="overflow-x-auto">
                    <table className="w-full border-collapse border border-gray-300">
                        <thead>
                            <tr className="bg-gray-100 text-gray-700">
                                <th className="border border-gray-300 px-4 py-2 text-left">Code</th>
                                <th className="border border-gray-300 px-4 py-2 text-left">Image</th>
                                <th className="border border-gray-300 px-4 py-2 text-left">Name</th>
                                <th className="border border-gray-300 px-4 py-2 text-left">Price</th>
                                <th className="border border-gray-300 px-4 py-2 text-left">Qty</th>
                                <th className="border border-gray-300 px-4 py-2 text-left">Amount</th>
                                <th className="border border-gray-300 px-4 py-2 text-left">Status</th>
                                <th className="border border-gray-300 px-4 py-2 text-left">Remarks</th>
                                <th className="border border-gray-300 px-4 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        
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

        </Layout>
    );

};

export default Dashboard;