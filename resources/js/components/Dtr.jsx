import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Layout from "./Layout";
import 'toastr/build/toastr.min.css';
import "react-datepicker/dist/react-datepicker.css";
import DtrView from "./DtrView";
import { useParams } from 'react-router-dom';

const Dtr = () => {
    const { id } = useParams();
    const { from } = useParams();
    const didFetch = useRef(false);

    useEffect(() => {
        if (didFetch.current) return;
        didFetch.current = true;
    }, []);

    return (
        <Layout>
            <div className="border border-gray-300 shadow-xl rounded-lg p-6 bg-white mx-auto w-full mt-10">
                <div className="overflow-x-auto">
                    <DtrView
                        id={id}
                        from={from}
                    />
                </div>

            </div>

            
        </Layout>
    );
};

export default Dtr;
