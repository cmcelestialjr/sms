import React, { useState, useEffect, useRef } from "react";
import { useReactToPrint } from 'react-to-print';
import { useNavigate } from "react-router-dom";
import axios from "axios";
import { Edit, FileText, List, Plus, Printer } from "lucide-react";
import toastr from 'toastr';
import Swal from "sweetalert2";
import 'toastr/build/toastr.min.css';
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";

const DtrView = ({ id, from }) => {
    const navigate = useNavigate();
    const didFetch = useRef(false);
    const now = new Date();
    const firstDayOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDayOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    const [duration, setDuration] = useState([
        firstDayOfMonth,
        lastDayOfMonth,
    ]);
    const [selectedMonth, setSelectedMonth] = useState('');
    const [monthList, setMonthList] = useState([]);
    const [dtr, setDtr] = useState([]);
    const [dtrRaw, setDtrRaw] = useState([]);    
    const [startDate, endDate] = duration;
    const [viewMode, setViewMode] = useState('dtr');
    const [name, setName] = useState('');
    const dtrRef = useRef();
    const rawRef = useRef();

    useEffect(() => {
        if (didFetch.current) return;
        didFetch.current = true;
    }, []);

    useEffect(() => {
        fetchMonths();
    }, [startDate, endDate]);

    const fetchMonths = () => {
        if (!startDate || !endDate) {
        setMonthList([]);
        setSelectedMonth('');
        return;
        }

        const months = [];
        const current = new Date(startDate.getFullYear(), startDate.getMonth(), 1);
        const end = new Date(endDate.getFullYear(), endDate.getMonth(), 1);

        while (current <= end) {
            const year = current.getFullYear();
            const month = current.getMonth();

            const isFirstMonth = year === startDate.getFullYear() && month === startDate.getMonth();
            const isLastMonth = year === endDate.getFullYear() && month === endDate.getMonth();

            const startOfMonth = isFirstMonth
                ? startDate
                : new Date(year, month, 1);

            const endOfMonth = isLastMonth
                ? endDate
                : new Date(year, month + 1, 0);

            const startDay = startOfMonth.getDate();
            const endDay = endOfMonth.getDate();
            const monthName = startOfMonth.toLocaleString('default', { month: 'long' });
            const fullYear = startOfMonth.getFullYear();

            months.push({
                display: startOfMonth.toLocaleString('default', { month: 'long', year: 'numeric' }),
                value: `${year}-${String(month + 1).padStart(2, '0')}`,
                start: startOfMonth.toLocaleDateString('en-CA'),
                end: endOfMonth.toLocaleDateString('en-CA'),
                duration: `${monthName} ${startDay}–${endDay}, ${fullYear}`
            });

            current.setMonth(current.getMonth() + 1);
        }
        
        const firstMonth = months[0];
        setMonthList(months);
        setSelectedMonth(firstMonth);
        fetchDtr(firstMonth);
    }

    const fetchDtr = async (month) => {
        try {
            const authToken = localStorage.getItem("token");
            const response = await axios.get('/api/dtr', {
                params: { 
                    id: id,
                    value: month.value,
                    start: month.start,
                    end: month.end,
                    duration: month.duration,
                    display: month.display
                },
                headers: { Authorization: `Bearer ${authToken}` },
            });
            setDtr(response.data.data);
            setDtrRaw(response.data.raw);
            setName(response.data.name);
        } catch (error) {
            // toastr.error('Failed to load students');
        } finally {

        }
    };

    const handlePrint = useReactToPrint({
        contentRef: viewMode === 'dtr' ? dtrRef : rawRef,
        removeAfterPrint: true,
        onBeforeGetContent: () =>
            new Promise((resolve) => {
                if (viewMode === 'dtr' && !dtrRef.current.innerHTML) {
                    toastr.error('There is nothing to print');
                    resolve(); // Prevent print if content is empty
                } else if (viewMode === 'raw' && !rawRef.current.innerHTML) {
                    toastr.error('There is nothing to print');
                    resolve(); // Prevent print if content is empty
                } else {
                    setTimeout(resolve, 200); // Delay helps ensure refs are attached
                }
            }),
    });


    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                {from === 'my' ? (
                    <h1 className="text-2xl font-semibold text-gray-800">
                    My DTR
                    </h1>
                ) : (
                    <>
                    <h1 className="text-2xl font-semibold text-gray-800">
                        DTR - {name}
                    </h1>
                    <button
                        onClick={() => navigate(-1)}
                        className="text-sm text-blue-600 hover:underline cursor-pointer"
                    >
                        ← Back
                    </button>                 
                    </>
                )}
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                {/* Single Calendar for Date Duration */}
                <DatePicker
                    selected={startDate}
                    onChange={(update) => setDuration(update)}
                    startDate={startDate}
                    endDate={endDate}
                    selectsRange
                    isClearable
                    placeholderText="Select duration"
                    className="w-full border border-gray-300 px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
            </div>

            {monthList.length > 0 && (
                <div className="flex flex-wrap gap-2 mb-2">
                    {monthList.map((month) => (
                    <button
                        key={month.value}
                        onClick={() => {
                            setSelectedMonth(month);
                            fetchDtr(month);
                        }}
                        className={`px-4 py-2 rounded-lg border cursor-pointer ${
                        selectedMonth?.value === month.value
                            ? 'bg-blue-400 text-white hover:bg-blue-600'
                            : 'bg-blue-800 text-white hover:bg-blue-400'
                        }`}
                    >
                        {month.display}
                    </button>
                    ))}
                </div>
            )}

            {selectedMonth && (
                <p className="text-gray-700 text-center text-xl"><strong>{selectedMonth.duration}</strong></p>
            )}

            {/* ✅ Action Buttons (DTR, Raw, Print) */}
            <div className="flex justify-center gap-4 my-4">
                <button
                    onClick={() => setViewMode('dtr')}
                    className={`flex items-center gap-2 px-4 py-2 rounded-lg cursor-pointer 
                        ${viewMode === 'dtr' ? 'bg-blue-600' : 'bg-blue-500 hover:bg-blue-600'} text-white`}
                >
                    <FileText className="w-4 h-4" />
                    DTR
                </button>

                <button
                    onClick={() => setViewMode('raw')}
                    className={`flex items-center gap-2 px-4 py-2 rounded-lg cursor-pointer 
                        ${viewMode === 'raw' ? 'bg-yellow-600' : 'bg-yellow-500 hover:bg-yellow-600'} text-white`}
                >
                    <List className="w-4 h-4" />
                    Raw
                </button>

                <button
                    onClick={handlePrint}
                    className="flex items-center gap-2 bg-green-500 text-white px-4 py-2 rounded-lg cursor-pointer hover:bg-green-600"
                >
                    <Printer className="w-4 h-4" />
                    Print
                </button>
            </div>

            <div className="overflow-x-auto">
                    <div ref={dtrRef} style={{ display: viewMode === 'dtr' ? 'block' : 'none' }} className="print-container">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-8">
                            <div>
                                <div className="text-center w-full dtr-info hidden">
                                    <p className="text-xs">Civil Service Form 48</p>
                                    <p className="text-sm font-bold mt-2">DAILY TIME RECORD</p>
                                    <p className="text-sm mt-3 border-b border-gray-300 w-full">{name}</p>
                                    <p className="text-xs">(NAME)</p>
                                    <p className="text-xs mt-1">Official Hours for arrival and departure</p>
                                    <p className="text-xs text-left">For the month of {selectedMonth.duration}</p>
                                </div>
                                <table className="min-w-full text-xs text-left border-collapse border border-gray-300">
                                    <thead className="bg-gray-100 text-xs text-gray-700">
                                        <tr>
                                            <th className="border border-gray-300 px-2 py-1 text-center" rowSpan={2}>Day</th>
                                            <th className="border border-gray-300 px-2 py-1 text-center" colSpan={2}>A.M.</th>
                                            <th className="border border-gray-300 px-2 py-1 text-center" colSpan={2}>P.M.</th>
                                            <th className="border border-gray-300 px-2 py-1 text-center" colSpan={2}>Undertime</th>
                                        </tr>
                                        <tr>
                                            <th className="border border-gray-300 px-2 py-1 text-center">Arrival</th>
                                            <th className="border border-gray-300 px-2 py-1 text-center">Departure</th>
                                            <th className="border border-gray-300 px-2 py-1 text-center">Arrival</th>
                                            <th className="border border-gray-300 px-2 py-1 text-center">Departure</th>
                                            <th className="border border-gray-300 px-2 py-1 text-center">Hours</th>
                                            <th className="border border-gray-300 px-2 py-1 text-center">Minutes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {Object.entries(dtr).map(([date, record]) => {
                                            const isSpecial = record.name && record.name !== 'time';
                                            const isAmHalfDay = record.half_day === 'am' && !record.am_in && !record.am_out;
                                            const isPmHalfDay = record.half_day === 'pm' && !record.pm_in && !record.pm_out;

                                            return (
                                                <tr key={date}>
                                                    <td className="border border-gray-300 px-2 py-1 text-center">
                                                        {record.day}
                                                    </td>

                                                    {/* Special Row: Weekend or Holiday */}
                                                    {isSpecial ? (
                                                        <td className="border border-gray-300 px-2 py-1 text-center text-red-500 font-semibold" colSpan={4}>
                                                            {record.name}
                                                        </td>
                                                    ) : (
                                                        <>
                                                            {/* AM */}
                                                            {isAmHalfDay ? (
                                                                <td className="border border-gray-300 px-2 py-1 text-center text-red-500 font-semibold" colSpan={2}>
                                                                    {record.name}
                                                                </td>
                                                            ) : (
                                                                <>
                                                                    <td className="border border-gray-300 px-2 py-1 text-center">
                                                                        {record.am_in || ''}
                                                                    </td>
                                                                    <td className="border border-gray-300 px-2 py-1 text-center">
                                                                        {record.am_out || ''}
                                                                    </td>
                                                                </>
                                                            )}

                                                            {/* PM */}
                                                            {isPmHalfDay ? (
                                                                <td className="border border-gray-300 px-2 py-1 text-center text-red-500 font-semibold" colSpan={2}>
                                                                    {record.name}
                                                                </td>
                                                            ) : (
                                                                <>
                                                                    <td className="border border-gray-300 px-2 py-1 text-center">
                                                                        {record.pm_in || ''}
                                                                    </td>
                                                                    <td className="border border-gray-300 px-2 py-1 text-center">
                                                                        {record.pm_out || ''}
                                                                    </td>
                                                                </>
                                                            )}
                                                        </>
                                                    )}
                                                    <td className="border border-gray-300 px-2 py-1 text-center">
                                                    
                                                    </td>
                                                    <td className="border border-gray-300 px-2 py-1 text-center">
                                                    
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                        <tr>
                                            <td className="border border-gray-300 px-2 py-1 text-right" colSpan={4}>
                                                TOTAL
                                            </td>
                                            <td className="border border-gray-300 px-2 py-1 text-center">
                                                    
                                            </td>
                                            <td className="border border-gray-300 px-2 py-1 text-center">
                                                    
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div className="text-center w-full dtr-info hidden">
                                    <p className="text-xs ml-1 text-left italic">
                                        I CERTIFY on my honor that the above is a true and correct
                                        report of the hours of work performed records of which was
                                        made daily at the time of arrival at and departure from office.
                                    </p>
                                    <p className="text-xs font-bold mt-7 border-t border-gray-300 w-full">Verified as to the prescribed office hours</p>
                                    <p className="text-xs font-bold mt-7 border-t border-gray-300 w-full">Authorized signature</p>
                                </div>
                            </div>
                            <div>
                                <div className="text-center w-full dtr-info hidden">
                                    <p className="text-xs">Civil Service Form 48</p>
                                    <p className="text-sm font-bold mt-2">DAILY TIME RECORD</p>
                                    <p className="text-sm mt-3 border-b border-gray-300 w-full">{name}</p>
                                    <p className="text-xs">(NAME)</p>
                                    <p className="text-xs mt-1">Official Hours for arrival and departure</p>
                                    <p className="text-xs text-left">For the month of {selectedMonth.duration}</p>
                                </div>
                                <table className="min-w-full text-xs text-left border-collapse border border-gray-300">
                                    <thead className="bg-gray-100 text-xs text-gray-700">
                                        <tr>
                                            <th className="border border-gray-300 px-2 py-1 text-center" rowSpan={2}>Day</th>
                                            <th className="border border-gray-300 px-2 py-1 text-center" colSpan={2}>A.M.</th>
                                            <th className="border border-gray-300 px-2 py-1 text-center" colSpan={2}>P.M.</th>
                                            <th className="border border-gray-300 px-2 py-1 text-center" colSpan={2}>Undertime</th>
                                        </tr>
                                        <tr>
                                            <th className="border border-gray-300 px-2 py-1 text-center">Arrival</th>
                                            <th className="border border-gray-300 px-2 py-1 text-center">Departure</th>
                                            <th className="border border-gray-300 px-2 py-1 text-center">Arrival</th>
                                            <th className="border border-gray-300 px-2 py-1 text-center">Departure</th>
                                            <th className="border border-gray-300 px-2 py-1 text-center">Hours</th>
                                            <th className="border border-gray-300 px-2 py-1 text-center">Minutes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {Object.entries(dtr).map(([date, record]) => {
                                            const isSpecial = record.name && record.name !== 'time';
                                            const isAmHalfDay = record.half_day === 'am' && !record.am_in && !record.am_out;
                                            const isPmHalfDay = record.half_day === 'pm' && !record.pm_in && !record.pm_out;

                                            return (
                                                <tr key={date}>
                                                    <td className="border border-gray-300 px-2 py-1 text-center">
                                                        {record.day}
                                                    </td>

                                                    {/* Special Row: Weekend or Holiday */}
                                                    {isSpecial ? (
                                                        <td className="border border-gray-300 px-2 py-1 text-center text-red-500 font-semibold" colSpan={4}>
                                                            {record.name}
                                                        </td>
                                                    ) : (
                                                        <>
                                                            {/* AM */}
                                                            {isAmHalfDay ? (
                                                                <td className="border border-gray-300 px-2 py-1 text-center text-red-500 font-semibold" colSpan={2}>
                                                                    {record.name}
                                                                </td>
                                                            ) : (
                                                                <>
                                                                    <td className="border border-gray-300 px-2 py-1 text-center">
                                                                        {record.am_in || ''}
                                                                    </td>
                                                                    <td className="border border-gray-300 px-2 py-1 text-center">
                                                                        {record.am_out || ''}
                                                                    </td>
                                                                </>
                                                            )}

                                                            {/* PM */}
                                                            {isPmHalfDay ? (
                                                                <td className="border border-gray-300 px-2 py-1 text-center text-red-500 font-semibold" colSpan={2}>
                                                                    {record.name}
                                                                </td>
                                                            ) : (
                                                                <>
                                                                    <td className="border border-gray-300 px-2 py-1 text-center">
                                                                        {record.pm_in || ''}
                                                                    </td>
                                                                    <td className="border border-gray-300 px-2 py-1 text-center">
                                                                        {record.pm_out || ''}
                                                                    </td>
                                                                </>
                                                            )}
                                                        </>
                                                    )}
                                                    <td className="border border-gray-300 px-2 py-1 text-center">
                                                    
                                                    </td>
                                                    <td className="border border-gray-300 px-2 py-1 text-center">
                                                    
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                        <tr>
                                            <td className="border border-gray-300 px-2 py-1 text-right" colSpan={4}>
                                                TOTAL
                                            </td>
                                            <td className="border border-gray-300 px-2 py-1 text-center">
                                                    
                                            </td>
                                            <td className="border border-gray-300 px-2 py-1 text-center">
                                                    
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div className="text-center w-full dtr-info hidden">
                                    <p className="text-xs ml-1 text-left italic">
                                        I CERTIFY on my honor that the above is a true and correct
                                        report of the hours of work performed records of which was
                                        made daily at the time of arrival at and departure from office.
                                    </p>
                                    <p className="text-xs font-bold mt-7 border-t border-gray-300 w-full">Verified as to the prescribed office hours</p>
                                    <p className="text-xs font-bold mt-7 border-t border-gray-300 w-full">Authorized signature</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div ref={rawRef} style={{ display: viewMode === 'raw' ? 'block' : 'none' }} className="print-container">
                        <div className="text-left w-full dtr-info hidden">
                            <p className="text-sm font-bold mt-2"> TIME LOGS</p>
                            <p className="text-sm mt-3 w-full"> {name}</p>
                            <p className="text-xs text-left mb-1"> {selectedMonth.duration}</p>
                        </div>
                        <table className="min-w-full text-sm text-left border-collapse border border-gray-300">
                            <thead className="bg-gray-100 text-xs text-gray-700 uppercase">
                                <tr>
                                    <th className="border border-gray-300 px-2 py-1 text-center">Date</th>
                                    <th className="border border-gray-300 px-2 py-1 text-center">Time</th>
                                    <th className="border border-gray-300 px-2 py-1 text-center">Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                {dtrRaw.length === 0 ? (
                                    <tr>
                                    <td colSpan={3} className="border border-gray-300 px-2 py-2 text-center text-gray-500">
                                        No records found.
                                    </td>
                                    </tr>
                                ) : (
                                    dtrRaw.map((record, index) => (
                                    <tr key={index}>
                                        <td className="border border-gray-300 px-2 py-1 text-center">{record.date}</td>
                                        <td className="border border-gray-300 px-2 py-1 text-center">{record.time}</td>
                                        <td className="border border-gray-300 px-2 py-1 text-center">{record.type}</td>
                                    </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
            </div>
        </div>
    );
};

export default DtrView;
