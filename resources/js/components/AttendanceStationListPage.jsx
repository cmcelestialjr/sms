import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

const AttendanceStationListPage = () => {
  const [stations, setStations] = useState([]);
  const navigate = useNavigate();

  useEffect(() => {
    fetchStations();
  }, []);

  const fetchStations = async () => {
    try {
      const response = await axios.get('/api/stations');
      setStations(response.data);
    } catch (error) {
      console.error('Error fetching stations:', error);
    }
  };

  const handleStationClick = (stationId) => {
    navigate(`/attendance/${stationId}`);
  };

  return (
    <div className="min-h-screen bg-gray-100 p-8">
      <h1 className="text-3xl font-bold mb-6 text-center">Select Attendance Station</h1>

      {stations?.length === 0 ? (
        <p className="text-center text-gray-500">No attendance stations available.</p>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {stations?.map((station) => (
            <div
              key={station.id}
              className="bg-white p-6 rounded-lg shadow-md hover:shadow-xl cursor-pointer transition"
              onClick={() => handleStationClick(station.id)}
            >
              <h2 className="text-xl font-semibold">{station.station_name}</h2>
              <p className="text-gray-500">{station.location ?? 'No location set'}</p>
              <p className="text-gray-400 text-sm">IP: {station.ipaddress}</p>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default AttendanceStationListPage;
