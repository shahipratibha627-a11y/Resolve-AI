import React, { useState, useEffect } from 'react';
import { 
  Search, Bell, AlertTriangle, ShieldCheck, Sparkles, 
  User, LogOut, ChevronDown, CheckCircle, ExternalLink, Plus 
} from 'lucide-react';
import { useAuth } from '../context/AuthContext';
import { notificationAPI } from '../services/api';
import { useNavigate } from 'react-router-dom';

export default function OperationsHeader({ onSearchChange, searchTerm, onNewTicket }) {
  const { user, logout, login } = useAuth();
  const navigate = useNavigate();
  const [showNotifications, setShowNotifications] = useState(false);
  const [notifications, setNotifications] = useState([]);
  const [showUserMenu, setShowUserMenu] = useState(false);

  useEffect(() => {
    if (user) {
      notificationAPI.getNotifications()
        .then(res => {
          if (res.success && res.data) {
            setNotifications(res.data);
          }
        })
        .catch(err => console.error("Error loading notifications:", err));
    }
  }, [user]);

  const unreadCount = notifications.filter(n => !n.is_read).length;

  const handleDemoSwitch = async (role, deptCode = 'cse') => {
    const code = deptCode.toLowerCase();
    const creds = {
      admin: ['admin@smartcampus.local', 'Admin@123'],
      staff: [`${code}.faculty@smartcampus.local`, 'Staff@123'],
      student: [`${code}.student@smartcampus.local`, 'Student@123']
    };
    if (creds[role]) {
      try {
        await login(creds[role][0], creds[role][1]);
        setShowUserMenu(false);
      } catch (err) {
        // Fallback to .staff if .faculty not matched
        try {
          await login(`${code}.staff@smartcampus.local`, 'Staff@123');
          setShowUserMenu(false);
        } catch (e2) {
          console.error("Demo switch failed:", err);
        }
      }
    }
  };

  return (
    <header className="h-16 bg-[#0B132B]/90 backdrop-blur-md border-b border-slate-800/80 px-4 sm:px-6 flex items-center justify-between sticky top-0 z-30">
      {/* Left: Brand & Title */}
      <div className="flex items-center space-x-3.5">
        <div className="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
          <ShieldCheck className="w-5 h-5 text-white" />
        </div>
        <div>
          <div className="flex items-center space-x-2">
            <h1 className="text-sm font-semibold text-white tracking-wide">Service Desk & Operations</h1>
            <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20 flex items-center gap-1">
              <Sparkles className="w-2.5 h-2.5 text-blue-400" /> AI-Powered
            </span>
          </div>
          <p className="text-[11px] text-slate-400 font-medium">ResolveAI Enterprise Platform</p>
        </div>
      </div>

      {/* Center: Search Bar */}
      <div className="flex-1 max-w-xl mx-6 hidden md:block">
        <div className="relative">
          <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input 
            type="text"
            value={searchTerm || ''}
            onChange={(e) => onSearchChange && onSearchChange(e.target.value)}
            placeholder="Search tickets, users, articles..."
            className="w-full bg-[#111C3A] text-slate-200 placeholder-slate-500 text-xs rounded-xl pl-10 pr-12 py-2.5 border border-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all shadow-inner"
          />
          <kbd className="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] bg-slate-800/80 text-slate-400 px-1.5 py-0.5 rounded border border-slate-700 font-mono">
            /
          </kbd>
        </div>
      </div>

      {/* Right Actions & Status */}
      <div className="flex items-center space-x-3">
        {/* New Ticket Quick Button */}
        <button 
          onClick={onNewTicket || (() => navigate('/report'))}
          className="hidden sm:flex items-center space-x-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-medium px-3 py-1.5 rounded-lg shadow-sm shadow-blue-600/30 transition-colors"
        >
          <Plus className="w-3.5 h-3.5" />
          <span>New Ticket</span>
        </button>

        {/* Live Status indicator */}
        <div className="flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-medium">
          <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <span className="text-[11px]">Online</span>
        </div>

        {/* Notification Bell */}
        <div className="relative">
          <button 
            onClick={() => setShowNotifications(!showNotifications)}
            className="p-2 rounded-xl bg-[#111C3A] text-slate-300 hover:text-white border border-slate-800 hover:border-slate-700 transition relative"
            title="Notifications"
          >
            <Bell className="w-4 h-4" />
            {unreadCount > 0 && (
              <span className="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-amber-500 text-slate-950 text-[10px] font-bold flex items-center justify-center shadow">
                {unreadCount > 9 ? '9+' : unreadCount}
              </span>
            )}
          </button>

          {showNotifications && (
            <div className="absolute right-0 mt-2 w-80 bg-[#0F172A] border border-slate-800 rounded-2xl shadow-2xl p-4 z-50">
              <div className="flex items-center justify-between pb-3 border-b border-slate-800">
                <span className="text-xs font-semibold text-white">Notifications ({unreadCount})</span>
                <button 
                  onClick={() => notificationAPI.markAllRead().then(() => setNotifications(prev => prev.map(n => ({...n, is_read: 1}))))}
                  className="text-[11px] text-blue-400 hover:underline"
                >
                  Mark all read
                </button>
              </div>
              <div className="mt-3 space-y-2 max-h-64 overflow-y-auto pr-1">
                {notifications.length === 0 ? (
                  <p className="text-xs text-slate-500 text-center py-4">No new alerts</p>
                ) : (
                  notifications.map((n) => (
                    <div key={n.id} className={`p-2.5 rounded-xl border text-xs ${n.is_read ? 'bg-slate-900/40 border-slate-800/60 text-slate-400' : 'bg-blue-950/30 border-blue-800/40 text-slate-200'}`}>
                      <p className="font-medium text-white">{n.title}</p>
                      <p className="text-[11px] text-slate-400 mt-0.5 line-clamp-2">{n.message}</p>
                    </div>
                  ))
                )}
              </div>
            </div>
          )}
        </div>

        {/* Warning Indicator (Matching screenshot) */}
        <div className="p-2 rounded-xl bg-[#111C3A] text-slate-400 border border-slate-800 relative">
          <AlertTriangle className="w-4 h-4 text-slate-400" />
          <span className="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-slate-800 text-slate-400 text-[10px] font-bold flex items-center justify-center">
            0
          </span>
        </div>

        {/* User Avatar & Dropdown */}
        <div className="relative">
          <button 
            onClick={() => setShowUserMenu(!showUserMenu)}
            className="flex items-center space-x-2 p-1.5 rounded-xl bg-[#111C3A] hover:bg-[#162347] border border-slate-800 transition"
          >
            <div className="w-7 h-7 rounded-lg bg-gradient-to-tr from-purple-600 to-blue-500 text-white font-bold text-xs flex items-center justify-center">
              {user?.full_name ? user.full_name.charAt(0).toUpperCase() : 'A'}
            </div>
            <ChevronDown className="w-3.5 h-3.5 text-slate-400" />
          </button>

          {showUserMenu && (
            <div className="absolute right-0 mt-2 w-64 bg-[#0F172A] border border-slate-800 rounded-2xl shadow-2xl p-3 z-50">
              <div className="pb-3 border-b border-slate-800">
                <p className="text-xs font-semibold text-white truncate">{user?.full_name || 'Administrator'}</p>
                <p className="text-[11px] text-slate-400 truncate">{user?.email || 'admin@smartcampus.local'}</p>
                <span className="inline-block mt-1 text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded bg-blue-500/20 text-blue-400 border border-blue-500/30">
                  {user?.role || 'admin'}
                </span>
              </div>

              {/* 1-Click Persona Switcher */}
              <div className="py-2 border-b border-slate-800">
                <p className="text-[10px] uppercase font-bold text-slate-500 tracking-wider mb-1.5">Switch Persona</p>
                <div className="grid grid-cols-3 gap-1">
                  <button 
                    onClick={() => handleDemoSwitch('admin')}
                    className="text-[11px] py-1 px-2 rounded-lg bg-slate-800 hover:bg-blue-600 text-slate-300 hover:text-white transition font-medium text-center"
                  >
                    Admin
                  </button>
                  <button 
                    onClick={() => handleDemoSwitch('staff')}
                    className="text-[11px] py-1 px-2 rounded-lg bg-slate-800 hover:bg-blue-600 text-slate-300 hover:text-white transition font-medium text-center"
                  >
                    Staff
                  </button>
                  <button 
                    onClick={() => handleDemoSwitch('student')}
                    className="text-[11px] py-1 px-2 rounded-lg bg-slate-800 hover:bg-blue-600 text-slate-300 hover:text-white transition font-medium text-center"
                  >
                    Student
                  </button>
                </div>
              </div>

              <div className="pt-2">
                <button 
                  onClick={logout}
                  className="w-full flex items-center space-x-2 text-xs text-rose-400 hover:bg-rose-500/10 p-2 rounded-xl transition"
                >
                  <LogOut className="w-3.5 h-3.5" />
                  <span>Sign Out</span>
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </header>
  );
}
