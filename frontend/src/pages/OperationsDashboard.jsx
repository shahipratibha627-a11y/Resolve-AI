import React, { useState, useEffect } from 'react';
import { 
  ArrowUpRight, ArrowDownRight, Clock, CheckCircle2, AlertCircle, 
  HelpCircle, BookOpen, Layers, MoreVertical, Sparkles, ExternalLink 
} from 'lucide-react';
import { 
  PieChart, Pie, Cell, ResponsiveContainer, AreaChart, 
  Area, LineChart, Line, XAxis, YAxis, Tooltip, Legend 
} from 'recharts';
import { analyticsAPI } from '../services/api';
import { useNavigate } from 'react-router-dom';

export default function OperationsDashboard({ searchTerm }) {
  const navigate = useNavigate();
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    analyticsAPI.getOperationsDashboard()
      .then(res => {
        if (res.success && res.data) {
          setData(res.data);
        }
      })
      .catch(err => console.error("Error loading dashboard data:", err))
      .finally(() => setLoading(false));
  }, []);

  if (loading || !data) {
    return (
      <div className="flex-1 p-6 flex items-center justify-center min-h-[500px]">
        <div className="w-8 h-8 border-3 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
      </div>
    );
  }

  const { kpis, ticket_queue, ai_triage, sla_status, tickets_over_time, sentiment_trend, knowledge_recommendations } = data;

  return (
    <div className="p-4 sm:p-6 space-y-5 max-w-[1600px] mx-auto">
      {/* TOP ROW: 6 KPI CARDS */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5">
        {/* 1. Open Tickets */}
        <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-4 shadow-sm hover:border-slate-700 transition">
          <p className="text-[11px] font-medium text-slate-400">Open Tickets</p>
          <div className="flex items-baseline space-x-2 mt-1.5">
            <h3 className="text-xl font-bold text-white tracking-tight">{kpis.open_tickets.value.toLocaleString()}</h3>
            <span className="text-[11px] font-medium text-emerald-400 flex items-center">
              <ArrowUpRight className="w-3 h-3 mr-0.5" /> 12%
            </span>
          </div>
          <p className="text-[10px] text-slate-500 mt-1">vs last 7 days</p>
        </div>

        {/* 2. Pending */}
        <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-4 shadow-sm hover:border-slate-700 transition">
          <p className="text-[11px] font-medium text-slate-400">Pending</p>
          <div className="flex items-baseline space-x-2 mt-1.5">
            <h3 className="text-xl font-bold text-white tracking-tight">{kpis.pending.value}</h3>
            <span className="text-[11px] font-medium text-emerald-400 flex items-center">
              <ArrowUpRight className="w-3 h-3 mr-0.5" /> 8%
            </span>
          </div>
          <p className="text-[10px] text-slate-500 mt-1">vs last 7 days</p>
        </div>

        {/* 3. Due Today */}
        <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-4 shadow-sm hover:border-slate-700 transition">
          <p className="text-[11px] font-medium text-slate-400">Due Today</p>
          <div className="flex items-baseline space-x-2 mt-1.5">
            <h3 className="text-xl font-bold text-white tracking-tight">{kpis.due_today.value}</h3>
            <span className="text-[11px] font-medium text-emerald-400 flex items-center">
              <ArrowUpRight className="w-3 h-3 mr-0.5" /> 15%
            </span>
          </div>
          <p className="text-[10px] text-slate-500 mt-1">vs last 7 days</p>
        </div>

        {/* 4. AI Triage */}
        <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-4 shadow-sm hover:border-slate-700 transition">
          <p className="text-[11px] font-medium text-slate-400">AI Triage (Auto-Resolved)</p>
          <div className="flex items-baseline space-x-2 mt-1.5">
            <h3 className="text-xl font-bold text-white tracking-tight">{kpis.ai_triage_auto_resolved.value}</h3>
            <span className="text-[11px] font-medium text-emerald-400 flex items-center">
              <ArrowUpRight className="w-3 h-3 mr-0.5" /> 24%
            </span>
          </div>
          <p className="text-[10px] text-slate-500 mt-1">vs last 7 days</p>
        </div>

        {/* 5. Avg Response Time */}
        <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-4 shadow-sm hover:border-slate-700 transition">
          <p className="text-[11px] font-medium text-slate-400">Avg. Response Time</p>
          <div className="flex items-baseline space-x-2 mt-1.5">
            <h3 className="text-xl font-bold text-white tracking-tight">{kpis.avg_response_time.value}</h3>
            <span className="text-[11px] font-medium text-emerald-400 flex items-center">
              <ArrowDownRight className="w-3 h-3 mr-0.5" /> 20%
            </span>
          </div>
          <p className="text-[10px] text-slate-500 mt-1">vs last 7 days</p>
        </div>

        {/* 6. Resolution Rate */}
        <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-4 shadow-sm hover:border-slate-700 transition">
          <p className="text-[11px] font-medium text-slate-400">Resolution Rate</p>
          <div className="flex items-baseline space-x-2 mt-1.5">
            <h3 className="text-xl font-bold text-white tracking-tight">{kpis.resolution_rate.value}</h3>
            <span className="text-[11px] font-medium text-emerald-400 flex items-center">
              <ArrowUpRight className="w-3 h-3 mr-0.5" /> 7%
            </span>
          </div>
          <p className="text-[10px] text-slate-500 mt-1">vs last 7 days</p>
        </div>
      </div>

      {/* MIDDLE ROW: 3 CARDS (Queue, AI Triage Donut, SLA Donut) */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {/* CARD 1: Ticket Queue */}
        <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-5 flex flex-col justify-between shadow-sm">
          <div>
            <div className="flex items-center justify-between pb-3 border-b border-slate-800/70">
              <h4 className="text-xs font-semibold text-white">Ticket Queue</h4>
              <MoreVertical className="w-3.5 h-3.5 text-slate-500 cursor-pointer" />
            </div>
            <div className="divide-y divide-slate-800/40 mt-1">
              {ticket_queue.map((item, idx) => (
                <div 
                  key={idx} 
                  onClick={() => navigate(`/tickets?category=${encodeURIComponent(item.name)}`)}
                  className="py-2.5 flex items-center justify-between hover:bg-slate-800/30 px-2 rounded-lg cursor-pointer transition"
                >
                  <span className="text-xs text-slate-300 font-medium">{item.name}</span>
                  <div className="flex items-center space-x-3">
                    <span className="text-xs font-semibold text-white">{item.count}</span>
                    <span className={`text-[11px] font-medium flex items-center ${item.trend.startsWith('+') ? 'text-emerald-400' : 'text-slate-400'}`}>
                      {item.trend.startsWith('+') ? <ArrowUpRight className="w-3 h-3 mr-0.5" /> : <ArrowDownRight className="w-3 h-3 mr-0.5" />}
                      {item.trend.replace('+', '').replace('-', '')}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </div>
          <button 
            onClick={() => navigate('/tickets')}
            className="text-xs font-medium text-blue-400 hover:text-blue-300 pt-3 text-left flex items-center gap-1 transition"
          >
            <span>View all queues</span>
            <span>→</span>
          </button>
        </div>

        {/* CARD 2: AI Triage Overview */}
        <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-5 flex flex-col justify-between shadow-sm">
          <div>
            <div className="flex items-center justify-between pb-2 border-b border-slate-800/70">
              <h4 className="text-xs font-semibold text-white">AI Triage Overview</h4>
            </div>

            <div className="relative h-44 my-2 flex items-center justify-center">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={ai_triage.breakdown}
                    cx="50%"
                    cy="50%"
                    innerRadius={55}
                    outerRadius={75}
                    paddingAngle={3}
                    dataKey="value"
                  >
                    {ai_triage.breakdown.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={entry.color} stroke="#0B132B" strokeWidth={2} />
                    ))}
                  </Pie>
                </PieChart>
              </ResponsiveContainer>
              {/* Center value */}
              <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                <span className="text-2xl font-bold text-white tracking-tight">{ai_triage.total_auto_resolved}</span>
                <span className="text-[10px] text-slate-400 font-medium">Auto-Resolved</span>
              </div>
            </div>

            {/* Legend */}
            <div className="space-y-1.5 px-2">
              {ai_triage.breakdown.map((item, idx) => (
                <div key={idx} className="flex items-center justify-between text-xs">
                  <div className="flex items-center space-x-2">
                    <span className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: item.color }}></span>
                    <span className="text-slate-300">{item.name}</span>
                  </div>
                  <span className="text-slate-400 font-medium">{item.value} ({item.pct}%)</span>
                </div>
              ))}
            </div>
          </div>

          <div className="pt-3 border-t border-slate-800/70 flex items-center justify-between text-[11px] text-slate-400 mt-3">
            <span>Confidence Score (Avg.)</span>
            <span className="font-semibold text-emerald-400">{ai_triage.avg_confidence}% {ai_triage.confidence_trend}</span>
          </div>
        </div>

        {/* CARD 3: SLA Status */}
        <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-5 flex flex-col justify-between shadow-sm">
          <div>
            <div className="flex items-center justify-between pb-2 border-b border-slate-800/70">
              <h4 className="text-xs font-semibold text-white">SLA Status</h4>
              <MoreVertical className="w-3.5 h-3.5 text-slate-500 cursor-pointer" />
            </div>

            <div className="relative h-44 my-2 flex items-center justify-center">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={sla_status.breakdown}
                    cx="50%"
                    cy="50%"
                    innerRadius={55}
                    outerRadius={75}
                    paddingAngle={3}
                    dataKey="value"
                  >
                    {sla_status.breakdown.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={entry.color} stroke="#0B132B" strokeWidth={2} />
                    ))}
                  </Pie>
                </PieChart>
              </ResponsiveContainer>
              {/* Center value */}
              <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                <span className="text-2xl font-bold text-white tracking-tight">{sla_status.compliance_pct}%</span>
                <span className="text-[10px] text-slate-400 font-medium">Within SLA</span>
              </div>
            </div>

            {/* Legend */}
            <div className="space-y-1.5 px-2">
              {sla_status.breakdown.map((item, idx) => (
                <div key={idx} className="flex items-center justify-between text-xs">
                  <div className="flex items-center space-x-2">
                    <span className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: item.color }}></span>
                    <span className="text-slate-300">{item.name}</span>
                  </div>
                  <span className="text-slate-400 font-medium">{item.value.toLocaleString()} ({item.pct}%)</span>
                </div>
              ))}
            </div>
          </div>

          <div className="pt-3 border-t border-slate-800/70 flex items-center justify-between text-[11px] text-slate-400 mt-3">
            <span>SLA Compliance</span>
            <span className="font-semibold text-emerald-400">{sla_status.compliance_pct}% {sla_status.compliance_trend}</span>
          </div>
        </div>
      </div>

      {/* BOTTOM ROW: 3 CARDS (Tickets Over Time, Sentiment Trend, Top Knowledge Recommendations) */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {/* CARD 1: Tickets Over Time */}
        <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-5 shadow-sm">
          <div className="flex items-center justify-between pb-3">
            <h4 className="text-xs font-semibold text-white">Tickets Over Time</h4>
            <div className="flex items-center space-x-3 text-[11px] text-slate-400">
              <span className="flex items-center gap-1.5">
                <span className="w-2 h-2 rounded-full bg-blue-500"></span> Created
              </span>
              <span className="flex items-center gap-1.5">
                <span className="w-2 h-2 rounded-full bg-emerald-500"></span> Resolved
              </span>
            </div>
          </div>

          <div className="h-56 mt-2">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={tickets_over_time} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                <defs>
                  <linearGradient id="gradCreated" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#3B82F6" stopOpacity={0.4}/>
                    <stop offset="95%" stopColor="#3B82F6" stopOpacity={0.0}/>
                  </linearGradient>
                  <linearGradient id="gradResolved" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#10B981" stopOpacity={0.4}/>
                    <stop offset="95%" stopColor="#10B981" stopOpacity={0.0}/>
                  </linearGradient>
                </defs>
                <XAxis dataKey="date" stroke="#64748B" fontSize={10} tickLine={false} />
                <YAxis stroke="#64748B" fontSize={10} tickLine={false} tickFormatter={(val) => `${val >= 1000 ? (val/1000).toFixed(0)+'K' : val}`} />
                <Tooltip 
                  contentStyle={{ backgroundColor: '#0F172A', borderColor: '#334155', borderRadius: '12px', fontSize: '11px' }} 
                  itemStyle={{ color: '#E2E8F0' }}
                />
                <Area type="monotone" dataKey="created" stroke="#3B82F6" strokeWidth={2} fillOpacity={1} fill="url(#gradCreated)" />
                <Area type="monotone" dataKey="resolved" stroke="#10B981" strokeWidth={2} fillOpacity={1} fill="url(#gradResolved)" />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* CARD 2: Sentiment Trend */}
        <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-5 shadow-sm">
          <div className="flex items-center justify-between pb-3">
            <h4 className="text-xs font-semibold text-white">Sentiment Trend</h4>
            <MoreVertical className="w-3.5 h-3.5 text-slate-500 cursor-pointer" />
          </div>

          <div className="h-56 mt-2">
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={sentiment_trend} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                <XAxis dataKey="date" stroke="#64748B" fontSize={10} tickLine={false} />
                <YAxis stroke="#64748B" fontSize={10} tickLine={false} tickFormatter={(val) => `${val}%`} domain={[0, 100]} />
                <Tooltip 
                  contentStyle={{ backgroundColor: '#0F172A', borderColor: '#334155', borderRadius: '12px', fontSize: '11px' }} 
                  itemStyle={{ color: '#E2E8F0' }}
                />
                <Line type="monotone" dataKey="positive" stroke="#10B981" strokeWidth={2} dot={{ r: 3, fill: '#10B981' }} />
                <Line type="monotone" dataKey="neutral" stroke="#3B82F6" strokeWidth={2} dot={{ r: 3, fill: '#3B82F6' }} />
                <Line type="monotone" dataKey="negative" stroke="#EF4444" strokeWidth={2} dot={{ r: 3, fill: '#EF4444' }} />
              </LineChart>
            </ResponsiveContainer>
          </div>

          {/* Sentiment legend at bottom matching screenshot */}
          <div className="flex items-center justify-center space-x-4 text-[10px] text-slate-400 mt-2">
            <span className="flex items-center gap-1.5"><span className="w-2 h-2 rounded-full bg-emerald-500"></span> Positive</span>
            <span className="flex items-center gap-1.5"><span className="w-2 h-2 rounded-full bg-blue-500"></span> Neutral</span>
            <span className="flex items-center gap-1.5"><span className="w-2 h-2 rounded-full bg-rose-500"></span> Negative</span>
          </div>
        </div>

        {/* CARD 3: Top Knowledge Recommendations (Resolution Intelligence!) */}
        <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-5 flex flex-col justify-between shadow-sm">
          <div>
            <div className="flex items-center justify-between pb-3 border-b border-slate-800/70">
              <h4 className="text-xs font-semibold text-white">Top Knowledge Recommendations</h4>
              <span className="text-[10px] text-slate-500">Usefulness</span>
            </div>

            <div className="divide-y divide-slate-800/40 mt-1">
              {knowledge_recommendations.map((art) => (
                <div 
                  key={art.id} 
                  onClick={() => navigate(`/knowledge?article=${art.id}`)}
                  className="py-3 flex items-start justify-between hover:bg-slate-800/30 px-2 rounded-xl cursor-pointer transition"
                >
                  <div className="flex items-start space-x-2.5 pr-2">
                    <div className="w-6 h-6 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center mt-0.5 shrink-0">
                      <BookOpen className="w-3.5 h-3.5" />
                    </div>
                    <div>
                      <p className="text-xs text-slate-200 font-medium line-clamp-1">{art.title}</p>
                      <p className="text-[10px] text-slate-500 mt-0.5">Article • Updated recently</p>
                    </div>
                  </div>
                  <span className="text-xs font-semibold text-emerald-400 shrink-0">{art.usefulness_pct}%</span>
                </div>
              ))}
            </div>
          </div>

          <button 
            onClick={() => navigate('/knowledge')}
            className="text-xs font-medium text-blue-400 hover:text-blue-300 pt-3 text-left flex items-center gap-1 transition"
          >
            <span>View all articles</span>
            <span>→</span>
          </button>
        </div>
      </div>
    </div>
  );
}
