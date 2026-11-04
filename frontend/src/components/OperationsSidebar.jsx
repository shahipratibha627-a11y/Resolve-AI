import React from 'react';
import { 
  LayoutDashboard, TicketCheck, CheckSquare, AlertCircle, 
  AlertTriangle, GitCommit, BookOpen, BarChart3, FileText, Settings 
} from 'lucide-react';
import { useLocation, useNavigate } from 'react-router-dom';

export default function OperationsSidebar() {
  const location = useLocation();
  const navigate = useNavigate();

  const menuItems = [
    { name: 'Overview', icon: LayoutDashboard, path: '/overview' },
    { name: 'Tickets', icon: TicketCheck, path: '/tickets' },
    { name: 'Tasks', icon: CheckSquare, path: '/tasks' },
    { name: 'Incidents', icon: AlertCircle, path: '/incidents' },
    { name: 'Problems', icon: AlertTriangle, path: '/problems' },
    { name: 'Changes', icon: GitCommit, path: '/changes' },
    { name: 'Knowledge', icon: BookOpen, path: '/knowledge' },
    { name: 'Analytics', icon: BarChart3, path: '/analytics' },
    { name: 'Reports', icon: FileText, path: '/reports' },
    { name: 'Settings', icon: Settings, path: '/settings' },
  ];

  return (
    <aside className="w-56 bg-[#080F25] border-r border-slate-800/80 min-h-[calc(100vh-4rem)] p-3 flex flex-col justify-between shrink-0">
      <div className="space-y-1">
        {menuItems.map((item) => {
          const Icon = item.icon;
          const isActive = location.pathname === item.path || (item.path === '/overview' && location.pathname === '/');
          return (
            <button
              key={item.name}
              onClick={() => navigate(item.path)}
              className={`w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all ${
                isActive
                  ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-semibold'
                  : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/50'
              }`}
            >
              <Icon className={`w-4 h-4 ${isActive ? 'text-white' : 'text-slate-400'}`} />
              <span>{item.name}</span>
            </button>
          );
        })}
      </div>

      {/* Footer Info Pill */}
      <div className="p-3 rounded-xl bg-[#0D1532] border border-slate-800/80 text-[11px] text-slate-400">
        <p className="font-semibold text-slate-300">ResolveAI Core</p>
        <p className="text-[10px] text-slate-500 mt-0.5">v2.0 • PHP + React</p>
      </div>
    </aside>
  );
}
