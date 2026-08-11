import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { EyeIcon, EyeOffIcon, LockIcon, UserIcon } from 'lucide-react';
import { LogInIcon } from 'lucide-react';
import axios from 'axios';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

const LoginPage = () => {
  const navigate = useNavigate();
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [errors, setErrors] = useState({ username: false, password: false });
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const token = localStorage.getItem('token');
    const userRole = localStorage.getItem('userRole');
    if (token) {   
      navigate('/dashboard');
    }
  }, [navigate]);

  const handleUsernameChange = (e) => {
    setUsername(e.target.value);
    if (e.target.value) setErrors((prev) => ({ ...prev, username: false }));
  };

  const handlePasswordChange = (e) => {
    setPassword(e.target.value);
    if (e.target.value) setErrors((prev) => ({ ...prev, password: false }));
  };

  const toggleShowPassword = () => setShowPassword(!showPassword);

  const handleLogin = async (e) => {
    e.preventDefault();

    // Validate the inputs
    const newErrors = {
      username: username.trim() === '',
      password: password.trim() === '',
    };

    setErrors(newErrors);
    
    if (!newErrors.username && !newErrors.password) {
      setLoading(true);
        try {
          
          const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

          const response = 
            await axios.post('/api/login', {
                username,
                password,
              },
              {
                headers: {
                  'X-CSRF-TOKEN': csrfToken,
                },
              }
            );

            if(response.data.message=="success"){
              localStorage.removeItem("token");
              localStorage.removeItem("userRole");
              localStorage.setItem('token', response.data.token);
              localStorage.setItem('userId', response.data.userId);
              localStorage.setItem('userRole', response.data.userRole);
              localStorage.setItem('userName', response.data.userName);
              localStorage.setItem('userPhoto', response.data.userPhoto);
              toastr.success('Login successful!');
            
              navigate('/dashboard', { replace: true });
              
            }else{
              toastr.error(response.data.message);
            }
      
            
        } catch (error) {
          toastr.error('An unexpected error occurred. Please try again later.');
        } finally {
          setLoading(false);
        }
    }else{
      toastr.warning('Please fill in both fields.');
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-100">
      <div className="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">
        {/* Logo & Header */}
        <div className="text-center mb-8">
          <img
            src="/images/clstldev2.png"
            alt="Attendify Logo"
            className="mx-auto h-20 w-20 object-contain"
          />

          <h1 className="mt-5 text-3xl font-bold text-gray-900 tracking-tight">
            Attendify
          </h1>

          <p className="mt-2 text-gray-500 leading-relaxed">
            Smart Student Attendance Management with
            <span className="font-medium text-blue-600">
              {" "}QR Code Tracking
            </span>
            {" "}and{" "}
            <span className="font-medium text-blue-600">
              Instant Notifications
            </span>.
          </p>
        </div>

        {/* Login Form */}
        <form onSubmit={handleLogin} className="space-y-5">

          {/* Username */}
          <div className="relative">
            <UserIcon className="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />

            <input
              type="text"
              placeholder="Username"
              value={username}
              onChange={handleUsernameChange}
              className={`w-full rounded-xl border bg-gray-50 py-3 pl-11 pr-4 transition
                ${
                  errors.username
                    ? "border-red-500 focus:ring-red-500"
                    : "border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                }
                focus:outline-none`}
            />
          </div>

          {/* Password */}
          <div className="relative">
            <LockIcon className="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />

            <input
              type={showPassword ? "text" : "password"}
              placeholder="Password"
              value={password}
              onChange={handlePasswordChange}
              className={`w-full rounded-xl border bg-gray-50 py-3 pl-11 pr-12 transition
                ${
                  errors.password
                    ? "border-red-500 focus:ring-red-500"
                    : "border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                }
                focus:outline-none`}
            />

            <button
              type="button"
              onClick={toggleShowPassword}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-blue-600 transition"
            >
              {showPassword ? (
                <EyeIcon className="h-5 w-5" />
              ) : (
                <EyeOffIcon className="h-5 w-5" />
              )}
            </button>
          </div>

          {/* Login Button */}
          <button
            type="submit"
            disabled={loading}
            className="flex w-full items-center justify-center rounded-xl bg-blue-600 py-3 font-semibold text-white transition duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
          >
            {loading ? (
              <>
                <div className="mr-2 h-5 w-5 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                Signing In...
              </>
            ) : (
              <>
                <LogInIcon className="mr-2 h-5 w-5" />
                Sign In
              </>
            )}
          </button>
        </form>

        {/* Footer */}
        <div className="mt-8 border-t pt-5 text-center">
          <p className="text-xs text-gray-400">
            © 2026 Attendify. All rights reserved.
          </p>
        </div>
      </div>
    </div>
  );
};

export default LoginPage;
