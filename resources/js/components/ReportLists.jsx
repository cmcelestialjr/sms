import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Layout from "./Layout";
import { Edit, Plus } from "lucide-react";
import toastr from 'toastr';
import Swal from "sweetalert2";
import 'toastr/build/toastr.min.css';
import "react-datepicker/dist/react-datepicker.css";

const ReportLists = ({setActiveTab}) => {

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-semibold text-gray-800">Reports</h1>
            </div>
            <div className="grid grid-cols-2 md:grid-cols-2 gap-4 mb-4">
                <h4 
                    onClick={() => setActiveTab('sf2')}
                    className="text-blue-500 hover:text-blue-700 font-medium cursor-pointer">
                    School Form 2 (SF2) Daily Attendance Report of Learners
                </h4>
            </div>
        </div>        
    );
};

export default ReportLists;
