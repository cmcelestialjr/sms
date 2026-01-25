import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Layout from "./Layout";
import { Edit, Plus } from "lucide-react";
import toastr from 'toastr';
import Swal from "sweetalert2";
import 'toastr/build/toastr.min.css';
import "react-datepicker/dist/react-datepicker.css";
import ReportLists from "./ReportLists";
import ReportSf2 from "./ReportSf2";

const Reports = () => {
    const authToken = localStorage.getItem("token");
    const [activeTab, setActiveTab] = useState("lists");

    const didFetch = useRef(false);

    useEffect(() => {
        if (didFetch.current) return;
        didFetch.current = true;
    }, []);

    return (
        <Layout>
            <div className="border border-gray-300 shadow-xl rounded-lg p-6 bg-white mx-auto w-full mt-10">
                {activeTab === "lists" && <ReportLists setActiveTab={setActiveTab}  />}
                {activeTab === "sf2" && <ReportSf2 setActiveTab={setActiveTab}  />}
            </div>
        </Layout>
    );
};

export default Reports;
