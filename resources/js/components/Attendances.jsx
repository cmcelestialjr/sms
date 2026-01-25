import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Layout from "./Layout";
import { Edit, Plus, X, } from "lucide-react";
import toastr from 'toastr';
import Swal from "sweetalert2";
import 'toastr/build/toastr.min.css';
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";
import AttendanceLists from "./AttendanceLists";
import AttendanceCalendar from "./AttendanceCalendar";

const Attendances = () => {
    const [activeTab, setActiveTab] = useState("Lists");
    

    const didFetch = useRef(false);

    useEffect(() => {
        if (didFetch.current) return;
        didFetch.current = true;

    }, []);

    return (
        <Layout>
            <div className="w-full mt-11 mx-auto">
                {/* Tabs */}
                <div className="mt-16 flex gap-4 mb-4">
                {["Lists", "Calendar"].map((tab) => (
                    <button
                        key={tab}
                        onClick={() => setActiveTab(tab)}
                        className={`px-4 py-2 rounded-lg font-medium capitalize transition ${
                        activeTab === tab
                            ? "bg-blue-600 text-white shadow"
                            : "bg-gray-100 text-gray-700 hover:bg-gray-200"
                        }`}
                    >
                    {tab}
                    </button>
                ))}
                </div>

                {activeTab === "Lists" && <AttendanceLists />}
                {activeTab === "Calendar" && <AttendanceCalendar />}
                
            </div>
        </Layout>
    );

};

export default Attendances;