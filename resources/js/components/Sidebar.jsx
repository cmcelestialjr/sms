import React, { useState } from "react";
import { Link, useLocation } from "react-router-dom";
import { Menu, 
    LayoutDashboard,
    CalendarCheck,
    GraduationCap,
    Users,
    MapPin,
    Wallet,
    MessageSquare,
    Calendar,
    UserCog,
    LogOut,
    User, } 
        from "lucide-react";

const Sidebar = () => {
  const [isOpen, setIsOpen] = useState(false);
  const location = useLocation(); // Get current path
  const userRole = localStorage.getItem("userRole");
  const userName = localStorage.getItem("userName");
  const userPhoto = localStorage.getItem("userPhoto");

  // Function to check if a link is active
  const isActive = (path) => location.pathname === path;

  return (
    <>
      {/* Menu Button (for mobile) */}
      <button 
        className="md:hidden fixed top-4 left-4 z-50 bg-white p-2 rounded shadow-md"
        onClick={() => setIsOpen(!isOpen)}
      >
        <Menu size={24} />
      </button>

      {/* Sidebar */}
      <div
        className={`fixed top-0 left-0 h-screen w-64 bg-white text-black border-r shadow-md z-50 transform transition-transform duration-300 ${
          isOpen ? "translate-x-0" : "-translate-x-64"
        } md:translate-x-0`}
      >
        <div className="overflow-y-auto max-h-screen">
          <div className="text-center mb-5 mt-3">
            <img src="/images/clstldev2.png" alt="Logo" className="mx-auto w-20 h-20" />
          </div>
          <nav>
            <ul className="space-y-4 px-5">
              <li>
                <Link
                  className="flex items-center"
                >
                  <div className="flex justify-center mr-2">
                    <div className="w-14 h-14 rounded-full overflow-hidden border border-gray-300 shadow-sm">
                      <img
                          src={userPhoto}
                          alt={userName}
                          className="w-full h-full object-cover"
                      />
                    </div>
                  </div>
                  {userName}
                </Link>
              </li>
              <li>
                <Link 
                  to="/dashboard"
                  className={`flex items-center p-2 rounded transition ${
                    isActive("/dashboard") ? "bg-blue-500 text-white" : "hover:bg-gray-100"
                  }`}
                >
                  <LayoutDashboard size={20} className="mr-2" />
                  Dashboard
                </Link>
              </li>
              <li>
                <Link 
                  to="/attendances" 
                  className={`flex items-center p-2 rounded transition ${
                    isActive("/attendances") ? "bg-blue-500 text-white" : "hover:bg-gray-100"
                  }`}
                >
                  <CalendarCheck size={20} className="mr-2" />
                  Attendances
                </Link>
              </li>
              <li>
                <Link 
                  to="/students"
                  className={`flex items-center p-2 rounded transition ${
                    isActive("/students") ? "bg-blue-500 text-white" : "hover:bg-gray-100"
                  }`}
                >
                  <GraduationCap size={20} className="mr-2" />
                  Students
                </Link>
              </li>
              {userRole==3 && ( 
                <>
                <li>
                  <Link 
                    to="/messages" 
                    className={`flex items-center p-2 rounded transition ${
                      isActive("/messages") ? "bg-blue-500 text-white" : "hover:bg-gray-100"
                    }`}
                  >
                    <MessageSquare size={20} className="mr-2" />
                    Messages
                  </Link>
                </li>
                </>
              )}
              {userRole<3 && ( <>
                <li>
                  <Link 
                    to="/teachers"
                    className={`flex items-center p-2 rounded transition ${
                      isActive("/teachers") ? "bg-blue-500 text-white" : "hover:bg-gray-100"
                    }`}
                  >
                    <UserCog size={20} className="mr-2" />
                    Teachers
                  </Link>
                </li>              
                <li>
                  <Link
                    to="/stations"
                    className={`flex items-center p-2 rounded transition ${
                      isActive("/stations") ? "bg-blue-500 text-white" : "hover:bg-gray-100"
                    }`}
                  >
                    <MapPin size={20} className="mr-2" />
                    Stations
                  </Link>
                </li>
                <li>
                  <Link 
                    to="/messages" 
                    className={`flex items-center p-2 rounded transition ${
                      isActive("/messages") ? "bg-blue-500 text-white" : "hover:bg-gray-100"
                    }`}
                  >
                    <MessageSquare size={20} className="mr-2" />
                    Messages
                  </Link>
                </li>
                {/* <li>
                  <Link 
                    to="/schoolYears" 
                    className={`flex items-center p-2 rounded transition ${
                      isActive("/schoolYears") ? "bg-blue-500 text-white" : "hover:bg-gray-100"
                    }`}
                  >
                    <Calendar size={20} className="mr-2" />
                    School Years
                  </Link>
                </li> */}
                <li>
                  <Link 
                    to="/users" 
                    className={`flex items-center p-2 rounded transition ${
                      isActive("/users") ? "bg-blue-500 text-white" : "hover:bg-gray-100"
                    }`}
                  >
                    <Users size={20} className="mr-2" />
                    Users
                  </Link>
                </li>
                </> 
              )}
              <li>
                <Link
                  to="/logout" 
                  className={`flex items-center p-2 rounded transition text-red-500 ${
                    isActive("/logout") ? "bg-red-500 text-white" : "hover:bg-gray-100"
                  }`}
                >
                  <LogOut size={20} className="mr-2" />
                  Logout
                </Link>
              </li>
            </ul>
          </nav>
        </div>
      </div>

      {/* Overlay when sidebar is open */}
      {isOpen && (
        <div
          className="fixed inset-0 bg-black/50 bg-opacity-30 md:hidden"
          onClick={() => setIsOpen(false)}
        ></div>
      )}
    </>
  );
};

export default Sidebar;
