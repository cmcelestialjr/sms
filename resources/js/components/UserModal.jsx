import React, { useState, useEffect } from 'react';
import { Edit, Plus, X, Upload, User  } from "lucide-react";

const UserModal = ({
    userModal,
    handleUserModalClose,
    handleUserSubmit,
    setLastnameOfUser,
    setFirstnameOfUser,
    setExtnameOfUser,
    setMiddlenameOfUser,
    setUserNameOfUser,
    setPasswordOfUser,
    setRoleOfUser,
    setLevelOfUser,
    setGradeOfUser,
    setSectionOfUser,
    setStatus,
    setNewPhoto,
    setPhoto,
    setEmail,
    setContactNo,
    setAddress,
    setPosition,
    setSex,
    roles,
    isEditingPassword,
    setIsEditingPassword,
    lastnameOfUser,
    firstnameOfUser,
    middlenameOfUser,
    extnameOfUser,
    userNameOfUser,
    passwordOfUser,
    levelOfUser,
    gradeOfUser,
    sectionOfUser,
    roleOfUser,
    status,
    newPhoto,
    photo,
    email,
    contactNo,
    address,
    position,
    sex
}) => {

    const handleImageChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setNewPhoto(file);
            setPhoto(URL.createObjectURL(file));
        }
    };

    return (
        userModal && (
        <div className="fixed top-0 left-0 right-0 bottom-0 bg-black/50 bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white p-6 rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto relative">
            <div className="flex justify-between items-center">
                <h2 className="text-2xl font-semibold text-gray-800">User</h2>
                <button onClick={handleUserModalClose} className="text-gray-500 hover:text-gray-700 transition cursor-pointer">
                    <X size={24} />
                </button>
            </div>

            <div className="flex flex-col items-center justify-center mt-2 space-y-1">
                    <label
                        className="relative w-36 h-36 rounded-full overflow-hidden shadow-md border border-gray-300 flex items-center justify-center bg-white hover:shadow-lg transition-all cursor-pointer group"
                        title="Click to upload photo"
                    >
                    {photo ? (
                        <img
                            src={photo}
                            alt="Student"
                            className="w-full h-full object-cover"
                        />
                    ) : (
                        <div className="flex flex-col items-center text-gray-400 group-hover:text-blue-500 transition-colors">
                            <User className="w-12 h-12" />
                        </div>
                    )}
                    <input
                        type="file"
                        accept="image/*"
                        onChange={handleImageChange}
                        className="absolute inset-0 opacity-0 cursor-pointer"
                    />
                </label>
                <p className="text-sm text-gray-500">Upload photo</p>
            </div>

            <div className="mt-4 space-y-6">
                <div className="grid grid-cols-2 sm:grid-cols-1 gap-6">
                {/* Lastname */}
                <div className="flex flex-col">
                    <label htmlFor="lastname" className="text-sm font-medium text-gray-700">Lastname</label>
                    <input
                    id="lastname"
                    type="text"
                    value={lastnameOfUser}
                    onChange={(e) => setLastnameOfUser(e.target.value)}
                    className="w-full border border-gray-300 px-4 py-2 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter Last Name"
                    />
                </div>

                {/* Firstname */}
                <div className="flex flex-col">
                    <label htmlFor="firstname" className="text-sm font-medium text-gray-700">Firstname</label>
                    <input
                    id="firstname"
                    type="text"
                    value={firstnameOfUser}
                    onChange={(e) => setFirstnameOfUser(e.target.value)}
                    className="w-full border border-gray-300 px-4 py-2 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter First Name"
                    />
                </div>

                {/* Middlename */}
                <div className="flex flex-col">
                    <label htmlFor="middlename" className="text-sm font-medium text-gray-700">Middlename</label>
                    <input
                    id="middlename"
                    type="text"
                    value={middlenameOfUser}
                    onChange={(e) => setMiddlenameOfUser(e.target.value)}
                    className="w-full border border-gray-300 px-4 py-2 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter Middle Name"
                    />
                </div>

                {/* Extname */}
                <div className="flex flex-col">
                    <label htmlFor="extname" className="text-sm font-medium text-gray-700">Extension Name</label>
                    <input
                    id="extname"
                    type="text"
                    value={extnameOfUser}
                    onChange={(e) => setExtnameOfUser(e.target.value)}
                    className="w-full border border-gray-300 px-4 py-2 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter Extension Name (e.g., Jr.)"
                    />
                </div>

                {/* Username */}
                <div className="flex flex-col">
                    <label htmlFor="username" className="text-sm font-medium text-gray-700">Username</label>
                    <input
                    id="username"
                    type="text"
                    value={userNameOfUser}
                    onChange={(e) => setUserNameOfUser(e.target.value)}
                    className="w-full border border-gray-300 px-4 py-2 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter Username"
                    />
                </div>

                {/* Password */}
                <div className="flex flex-col">
                    <label htmlFor="password" className="text-sm font-medium text-gray-700">Password</label>
                    <input
                    id="password"
                    type="password"
                    value={passwordOfUser}
                    onChange={(e) => setPasswordOfUser(e.target.value)}
                    className="w-full border border-gray-300 px-4 py-2 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter Password"
                    disabled={!isEditingPassword && passwordOfUser === '************'}
                    />
                    {passwordOfUser === '************' && (
                    <div className="mt-2">
                        <label className="inline-flex items-center">
                        <input
                            type="checkbox"
                            checked={isEditingPassword}
                            onChange={(e) => {
                            const checked = e.target.checked;
                            setIsEditingPassword(checked);
                            if (checked) {
                                setPasswordOfUser(''); // clear password so user can type a new one
                            }
                            }}
                            className="mr-2"
                        />
                        <span className="text-sm text-gray-700">Edit Password?</span>
                        </label>
                    </div>
                    )}
                </div>               

                {/* Role */}
                <div className="flex flex-col">
                    <label htmlFor="role" className="text-sm font-medium text-gray-700">Role</label>
                    <select
                    id="role"
                    value={roleOfUser}
                    onChange={(e) => setRoleOfUser(e.target.value)}
                    className="w-full border border-gray-300 px-4 py-2 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500"
                    >
                    {roles.map((role) => (
                        <option key={role.id} value={role.id}>{role.name}</option>
                    ))}
                    </select>
                </div>

                {/* Level, Grade, Section - Conditional rendering for a specific role */}
                {(roleOfUser === 3 || roleOfUser === '3') && (
                    <>
                    {/* Contact No */}
                    <div className="flex flex-col">
                        <label className="text-sm font-medium text-gray-700 mb-1">Contact No.</label>
                        <input
                            type="text"
                            name="contact_no"
                            placeholder="Enter Contact No."
                            value={contactNo}
                            onChange={(e) => {
                                const value = e.target.value;
                                // Only allow digits, up to 11 characters
                                if (/^\d{0,11}$/.test(value)) {
                                    setContactNo(value);
                                    }
                                }}
                            maxLength={11}
                            className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                            required
                        />
                        {contactNo && (
                            contactNo.length !== 11 || !contactNo.startsWith("09")
                            ) && (
                                <span className="text-red-500 text-sm mt-1">
                                                Contact number must start with "09" and be exactly 11 digits.
                                </span>
                            )}
                        </div>

                    {/* Email */}
                    <div className="flex flex-col">
                        <label className="text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input
                            type="email"
                            name="email"
                            placeholder="Enter Email"
                            value={email || ''}
                            onChange={(e) => setEmail(e.target.value)}
                            className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        />
                    </div>

                    {/* Position */}
                    <div className="flex flex-col">
                        <label className="text-sm font-medium text-gray-700 mb-1">Position</label>
                        <input
                            type="text"
                            name="position"
                            placeholder="Enter Position"
                            value={position || ''}
                            onChange={(e) => setPosition(e.target.value)}
                            className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        />
                    </div>

                    {/* Sex */}
                    <div className="flex flex-col">
                        <label className="text-sm font-medium text-gray-700 mb-1">Sex</label>
                        <select
                            value={sex}
                            onChange={(e) => setSex(e.target.value)}
                            className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                            required
                        >
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    {/* Address */}
                    <div className="flex flex-col col-span-2">
                        <label className="text-sm font-medium text-gray-700 mb-1">Address</label>
                        <input
                            type="text"
                            name="address"
                            placeholder="Enter Address"
                            value={address || ''}
                            onChange={(e) => setAddress(e.target.value)}
                            className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        />
                    </div>
                    <div className="flex flex-col">
                        <label htmlFor="level" className="text-sm font-medium text-gray-700">Level</label>
                        <select
                        id="level"
                        value={levelOfUser}
                        onChange={(e) => setLevelOfUser(e.target.value)}
                        className="w-full border border-gray-300 px-4 py-2 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500"
                        >
                        <option value="Kinder">Kinder</option>
                        <option value="Elementary">Elementary</option>
                        <option value="Junior High School">Junior High School</option>
                        <option value="Senior High School">Senior High School</option>
                        </select>
                    </div>

                    <div className="flex flex-col">
                        <label htmlFor="grade" className="text-sm font-medium text-gray-700">Grade</label>
                        <input
                        id="grade"
                        type="text"
                        value={gradeOfUser}
                        onChange={(e) => setGradeOfUser(e.target.value)}
                        className="w-full border border-gray-300 px-4 py-2 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter Grade"
                        />
                    </div>

                    <div className="flex flex-col">
                        <label htmlFor="section" className="text-sm font-medium text-gray-700">Section</label>
                        <input
                        id="section"
                        type="text"
                        value={sectionOfUser}
                        onChange={(e) => setSectionOfUser(e.target.value)}
                        className="w-full border border-gray-300 px-4 py-2 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter Section"
                        />
                    </div>

                    {/* Status */}
                    <div className="flex flex-col">
                        <label className="text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select
                            name="status"
                            value={status}
                            onChange={(e) => setStatus(e.target.value)}
                            className="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                            required
                        >
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    </>
                )}
                </div>

                {/* Action Buttons */}
                <div className="flex justify-between mt-6">
                <button
                    onClick={handleUserModalClose}
                    className="flex items-center gap-2 bg-gray-600 text-white px-6 py-3 rounded-lg shadow hover:bg-gray-700 transition cursor-pointer"
                >
                    <X size={18} /> Close
                </button>
                <button
                    onClick={handleUserSubmit}
                    className="flex items-center gap-2 bg-blue-600 text-white px-6 py-3 rounded-lg shadow hover:bg-blue-700 transition cursor-pointer"
                >
                    <Plus size={18} /> Submit
                </button>
                </div>
            </div>
            </div>
        </div>
        )
    );
};

export default UserModal;
