import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Layout from "./Layout";
import { Edit, Plus, X, } from "lucide-react";
import toastr from 'toastr';
import Swal from "sweetalert2";
import 'toastr/build/toastr.min.css';
import "react-datepicker/dist/react-datepicker.css";

const Dashboard = () => {
    const userRole = localStorage.getItem("userRole");
    const authToken = localStorage.getItem("token");
    const [data, setData] = useState({
        totalStudentsActive: 0,
        totalStudentsInactive: 0,
        totalStudentsMaleActive: 0,
        totalStudentsFemaleActive: 0,
        totalStudentsMaleInactive: 0,
        totalStudentsFemaleInactive: 0,
        totalTeachersActive: 0,
        totalTeachersInactive: 0,
        grades: [],
    });
    const didFetch = useRef(false);

    const axiosInstance = axios.create({
        headers: {
        Authorization: `Bearer ${authToken}`,
        },
    });

    useEffect(() => {
        if (didFetch.current) return;
        didFetch.current = true;

    }, []);
    
    useEffect(() => {
        axiosInstance.get('/api/dashboard')
        .then(response => {
            setData(response.data);
        })
        .catch(error => {
            console.error('There was an error fetching the data!', error);
        });
    }, [authToken]);

    return (
        <Layout>
            <div className="border border-gray-300 shadow-xl rounded-lg p-6 bg-white mx-auto w-full mt-10">
                {/* Header Section */}
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-semibold text-gray-800">Dashboard</h1>                   
                </div>

                {/* Total Counts Section */}
                <div className="mb-6">
                    {/* Active Students Section */}
                    <div className="mb-6">
                        <h2 className="text-xl font-semibold text-gray-800 mb-4">Active Students</h2>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
                            {/* Active Students */}
                            <div className="bg-blue-100 p-4 rounded-lg shadow-md flex flex-col justify-center items-center">
                                <h3 className="text-lg font-semibold text-gray-700 text-center">Total</h3>
                                <p className="text-xl font-bold text-blue-600 text-center">{data.totalStudentsActive}</p>
                            </div>

                            {/* Active Male Students */}
                            <div className="bg-gray-100 p-4 rounded-lg shadow-md flex flex-col justify-center items-center">
                                <h3 className="text-lg font-semibold text-gray-700 text-center">Male</h3>
                                <p className="text-xl font-bold text-gray-600 text-center">{data.totalStudentsMaleActive}</p>
                            </div>

                            {/* Active Female Students */}
                            <div className="bg-pink-100 p-4 rounded-lg shadow-md flex flex-col justify-center items-center">
                                <h3 className="text-lg font-semibold text-gray-700 text-center">Female</h3>
                                <p className="text-xl font-bold text-pink-600 text-center">{data.totalStudentsFemaleActive}</p>
                            </div>
                        </div>
                    </div>

                    {/* Inactive Students Section */}
                    <div className="mb-6">
                        <h2 className="text-xl font-semibold text-gray-800 mb-4">Inactive Students</h2>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
                            {/* Inactive Students */}
                            <div className="bg-green-100 p-4 rounded-lg shadow-md flex flex-col justify-center items-center">
                                <h3 className="text-lg font-semibold text-gray-700 text-center">Total</h3>
                                <p className="text-xl font-bold text-green-600 text-center">{data.totalStudentsInactive}</p>
                            </div>

                            {/* Inactive Male Students */}
                            <div className="bg-yellow-100 p-4 rounded-lg shadow-md flex flex-col justify-center items-center">
                                <h3 className="text-lg font-semibold text-gray-700 text-center">Male</h3>
                                <p className="text-xl font-bold text-yellow-600 text-center">{data.totalStudentsMaleInactive}</p>
                            </div>

                            {/* Inactive Female Students */}
                            <div className="bg-orange-100 p-4 rounded-lg shadow-md flex flex-col justify-center items-center">
                                <h3 className="text-lg font-semibold text-gray-700 text-center">Female</h3>
                                <p className="text-xl font-bold text-orange-600 text-center">{data.totalStudentsFemaleInactive}</p>
                            </div>
                        </div>
                    </div>

                    {userRole<3 && (
                        <div className="mb-6">
                            <h2 className="text-xl font-semibold text-gray-800 mb-4">Teachers</h2>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
                                {/* Active Teachers */}
                                <div className="bg-purple-100 p-4 rounded-lg shadow-md flex flex-col justify-center items-center">
                                    <h3 className="text-lg font-semibold text-gray-700 text-center">Active</h3>
                                    <p className="text-xl font-bold text-purple-600 text-center">{data.totalTeachersActive}</p>
                                </div>

                                {/* Inactive Teachers */}
                                <div className="bg-teal-100 p-4 rounded-lg shadow-md flex flex-col justify-center items-center">
                                    <h3 className="text-lg font-semibold text-gray-700 text-center">Inactive</h3>
                                    <p className="text-xl font-bold text-teal-600 text-center">{data.totalTeachersInactive}</p>
                                </div>
                            </div>
                        </div>
                    )}
                </div>


                {userRole<3 && (
                    <div className="mb-6">
                        <h2 className="text-xl font-semibold text-gray-800 mb-4">Grade Breakdown</h2>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
                            {data.studentsPerGrade?.map((grade, index) => (
                                <div key={index} className="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300">
                                    <h3 className="text-lg font-semibold text-gray-700 text-center mb-2">Grade: {grade.grade}</h3>
                                    <p className="text-xl font-bold text-gray-600 text-center">{grade.total_students} students</p>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>

        </Layout>
    );

};

export default Dashboard;