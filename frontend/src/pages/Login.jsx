import React, { useState } from 'react';
import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';
import { ShieldCheck, Sparkles, Lock, Mail, User, ArrowRight, Loader2 } from 'lucide-react';

export default function Login() {
  const { login, register } = useAuth();
  const navigate = useNavigate();

  const [isRegister, setIsRegister] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [fullName, setFullName] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const fillDemo = (demoEmail, demoPassword) => {
    setEmail(demoEmail);
    setPassword(demoPassword);
    setError('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      if (isRegister) {
        await register({ email, password, full_name: fullName, role: 'student' });
      } else {
        await login(email, password);
      }
      navigate('/overview');
    } catch (err) {
      setError(err.message || 'Authentication failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#070D1E] flex flex-col justify-center items-center p-4">
      <div className="max-w-md w-full space-y-6">
        {/* Brand Header */}
        <div className="text-center space-y-2">
          <div className="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center mx-auto shadow-xl shadow-blue-500/20">
            <ShieldCheck className="w-6 h-6 text-white" />
          </div>
          <h2 className="text-xl font-bold text-white tracking-tight">ResolveAI</h2>
          <p className="text-xs text-slate-400">
            AI-Powered Issue Detection and Resolution Platform
          </p>
        </div>

        {/* 1-Click Demo Personas */}
        <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-4 space-y-3 shadow-sm">
          <div className="flex items-center justify-between">
            <p className="text-[10px] font-bold uppercase tracking-wider text-slate-400">
              1-Click Demo Logins
            </p>
            <span className="text-[10px] text-blue-400 font-mono">Emails Match Dept Code</span>
          </div>
          
          <div className="grid grid-cols-3 gap-2">
            <button
              type="button"
              onClick={() => fillDemo('admin@smartcampus.local', 'Admin@123')}
              className="py-2 px-2 text-center rounded-xl bg-[#111C3A] hover:bg-blue-600 text-slate-300 hover:text-white border border-slate-800 hover:border-blue-500 transition text-[11px] font-medium"
            >
              Admin
            </button>
            <button
              type="button"
              onClick={() => fillDemo('cse.faculty@smartcampus.local', 'Staff@123')}
              className="py-2 px-2 text-center rounded-xl bg-[#111C3A] hover:bg-blue-600 text-slate-300 hover:text-white border border-slate-800 hover:border-blue-500 transition text-[11px] font-medium"
            >
              CSE Faculty
            </button>
            <button
              type="button"
              onClick={() => fillDemo('cse.student@smartcampus.local', 'Student@123')}
              className="py-2 px-2 text-center rounded-xl bg-[#111C3A] hover:bg-blue-600 text-slate-300 hover:text-white border border-slate-800 hover:border-blue-500 transition text-[11px] font-medium"
            >
              CSE Student
            </button>
          </div>

          {/* Department Code Quick Select */}
          <div className="pt-2 border-t border-slate-800/60 flex items-center justify-between gap-2">
            <span className="text-[11px] text-slate-400 shrink-0">Other Depts:</span>
            <select
              onChange={(e) => {
                const [role, code] = e.target.value.split(':');
                if (code) {
                  const email = `${code.toLowerCase()}.${role}@smartcampus.local`;
                  const pwd = role === 'student' ? 'Student@123' : 'Staff@123';
                  fillDemo(email, pwd);
                }
              }}
              defaultValue=""
              className="bg-[#111C3A] text-slate-200 text-[11px] rounded-lg px-2.5 py-1.5 border border-slate-800 focus:outline-none focus:border-blue-500 w-full"
            >
              <option value="" disabled>Select Department Faculty / Student...</option>
              <optgroup label="Faculty by Dept Code (Staff@123)">
                <option value="faculty:cse">CSE Faculty (cse.faculty@...)</option>
                <option value="faculty:chemical">Chemical Faculty (chemical.faculty@...)</option>
                <option value="faculty:biomedical">Biomedical Faculty (biomedical.faculty@...)</option>
                <option value="faculty:civil">Civil Faculty (civil.faculty@...)</option>
                <option value="faculty:mechanical">Mechanical Faculty (mechanical.faculty@...)</option>
                <option value="faculty:csbs">CSBS Faculty (csbs.faculty@...)</option>
                <option value="faculty:mechatronics">Mechatronics Faculty (mechatronics.faculty@...)</option>
                <option value="faculty:aids">AIDS Faculty (aids.faculty@...)</option>
                <option value="faculty:ee">EE Faculty (ee.faculty@...)</option>
              </optgroup>
              <optgroup label="Students by Dept Code (Student@123)">
                <option value="student:cse">CSE Student (cse.student@...)</option>
                <option value="student:chemical">Chemical Student (chemical.student@...)</option>
                <option value="student:biomedical">Biomedical Student (biomedical.student@...)</option>
                <option value="student:civil">Civil Student (civil.student@...)</option>
                <option value="student:mechanical">Mechanical Student (mechanical.student@...)</option>
                <option value="student:csbs">CSBS Student (csbs.student@...)</option>
                <option value="student:mechatronics">Mechatronics Student (mechatronics.student@...)</option>
                <option value="student:aids">AIDS Student (aids.student@...)</option>
                <option value="student:ee">EE Student (ee.student@...)</option>
              </optgroup>
            </select>
          </div>
        </div>

        {/* Auth Card */}
        <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-6 shadow-xl space-y-4">
          <div className="flex border-b border-slate-800 pb-3">
            <button
              type="button"
              onClick={() => { setIsRegister(false); setError(''); }}
              className={`flex-1 text-center text-xs font-semibold pb-2 transition ${
                !isRegister ? 'text-blue-400 border-b-2 border-blue-500' : 'text-slate-500 hover:text-slate-300'
              }`}
            >
              Sign In
            </button>
            <button
              type="button"
              onClick={() => { setIsRegister(true); setError(''); }}
              className={`flex-1 text-center text-xs font-semibold pb-2 transition ${
                isRegister ? 'text-blue-400 border-b-2 border-blue-500' : 'text-slate-500 hover:text-slate-300'
              }`}
            >
              Register Student
            </button>
          </div>

          {error && (
            <div className="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs">
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-3.5">
            {isRegister && (
              <div>
                <label className="block text-xs font-medium text-slate-300 mb-1">Full Name</label>
                <div className="relative">
                  <User className="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
                  <input
                    type="text"
                    value={fullName}
                    onChange={(e) => setFullName(e.target.value)}
                    placeholder="Jordan Lee"
                    className="w-full bg-[#111C3A] text-slate-100 placeholder-slate-500 text-xs rounded-xl pl-9 pr-3 py-2.5 border border-slate-800 focus:outline-none focus:border-blue-500"
                    required
                  />
                </div>
              </div>
            )}

            <div>
              <label className="block text-xs font-medium text-slate-300 mb-1">Email Address</label>
              <div className="relative">
                <Mail className="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="admin@smartcampus.local"
                  className="w-full bg-[#111C3A] text-slate-100 placeholder-slate-500 text-xs rounded-xl pl-9 pr-3 py-2.5 border border-slate-800 focus:outline-none focus:border-blue-500"
                  required
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-medium text-slate-300 mb-1">Password</label>
              <div className="relative">
                <Lock className="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  className="w-full bg-[#111C3A] text-slate-100 placeholder-slate-500 text-xs rounded-xl pl-9 pr-3 py-2.5 border border-slate-800 focus:outline-none focus:border-blue-500"
                  required
                />
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white text-xs font-semibold shadow-lg shadow-blue-600/30 transition flex items-center justify-center gap-2"
            >
              {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <ArrowRight className="w-4 h-4" />}
              <span>{isRegister ? 'Create Student Account' : 'Sign In to Operations'}</span>
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
