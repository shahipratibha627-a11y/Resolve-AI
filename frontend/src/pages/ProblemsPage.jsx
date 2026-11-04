import React, { useState, useEffect } from 'react';
import { AlertTriangle, Layers, Copy, CheckCircle, ShieldAlert, Sparkles, ArrowRight } from 'lucide-react';
import { analyticsAPI, incidentAPI } from '../services/api';
import { useNavigate } from 'react-router-dom';

export default function ProblemsPage() {
  const navigate = useNavigate();
  const [insights, setInsights] = useState([]);
  const [duplicates, setDuplicates] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([
      analyticsAPI.getAIInsights(),
      incidentAPI.getIncidents({ is_duplicate: 1 })
    ]).then(([aiRes, dupRes]) => {
      if (aiRes.success && aiRes.data?.recurring_hotspots) {
        setInsights(aiRes.data.recurring_hotspots);
      }
      if (dupRes.success && dupRes.data) {
        setDuplicates(dupRes.data);
      }
    }).catch(err => console.error("Error loading problem insights:", err))
      .finally(() => setLoading(false));
  }, []);

  return (
    <div className="p-4 sm:p-6 space-y-6 max-w-[1600px] mx-auto">
      <div>
        <h2 className="text-xl font-bold text-white tracking-tight">Recurring Problems & Duplicate Detection</h2>
        <p className="text-xs text-slate-400 mt-1">
          ResolveAI clusters historical tickets by location and domain to detect systemic root causes and recurring failure points.
        </p>
      </div>

      {/* Hotspots Grid */}
      <div className="space-y-4">
        <h3 className="text-sm font-semibold text-white flex items-center gap-2">
          <Sparkles className="w-4 h-4 text-blue-400" />
          <span>Systemic Hotspot Clusters ({insights.length})</span>
        </h3>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {insights.map((hotspot, idx) => (
            <div key={idx} className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-5 space-y-3.5 shadow-sm">
              <div className="flex items-center justify-between pb-2 border-b border-slate-800">
                <span className="text-xs font-bold text-white">{hotspot.location}</span>
                <span className={`text-[10px] font-bold px-2 py-0.5 rounded ${
                  hotspot.severity === 'CRITICAL' ? 'bg-rose-500/20 text-rose-400' :
                  hotspot.severity === 'HIGH' ? 'bg-amber-500/20 text-amber-400' :
                  'bg-blue-500/20 text-blue-400'
                }`}>
                  {hotspot.severity}
                </span>
              </div>

              <div className="flex items-center justify-between text-xs">
                <span className="text-slate-400">Category:</span>
                <span className="font-semibold text-slate-200">{hotspot.category}</span>
              </div>

              <div className="grid grid-cols-3 gap-2 text-center py-2 bg-[#111C3A] rounded-xl border border-slate-800">
                <div>
                  <p className="text-xs font-bold text-white">{hotspot.total_incidents}</p>
                  <p className="text-[10px] text-slate-400">Total</p>
                </div>
                <div>
                  <p className="text-xs font-bold text-amber-400">{hotspot.active_incidents}</p>
                  <p className="text-[10px] text-slate-400">Active</p>
                </div>
                <div>
                  <p className="text-xs font-bold text-emerald-400">{hotspot.resolved_incidents}</p>
                  <p className="text-[10px] text-slate-400">Resolved</p>
                </div>
              </div>

              <div className="p-3 rounded-xl bg-blue-950/20 border border-blue-900/30 text-xs text-blue-200">
                <p className="font-semibold text-blue-300 mb-1">Preventative Advisory:</p>
                <p className="text-[11px] text-slate-300">{hotspot.recommendation}</p>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Duplicate Incident Management */}
      <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-5 space-y-4">
        <h3 className="text-sm font-semibold text-white flex items-center gap-2">
          <Copy className="w-4 h-4 text-amber-400" />
          <span>Semantic Duplicate Clusters Detected</span>
        </h3>
        <p className="text-xs text-slate-400">
          The NLP engine flags duplicate or parallel student reports using token Jaccard & Cosine similarity (threshold &gt; 75%).
        </p>

        <div className="p-4 rounded-xl bg-[#111C3A] border border-slate-800 text-xs space-y-2">
          <div className="flex items-center justify-between">
            <span className="font-semibold text-white">Sample Detected Duplicate:</span>
            <span className="text-xs font-bold text-amber-400">88% Semantic Similarity</span>
          </div>
          <p className="text-slate-300">
            <span className="text-slate-400">Primary Ticket: </span>
            <span className="font-mono text-blue-400">INC-1001</span>: "Wi-Fi connection down in CSE Block Lab 3"
          </p>
          <p className="text-slate-300">
            <span className="text-slate-400">Parallel Report: </span>
            <span className="font-mono text-amber-400">INC-1011</span>: "Campus Wi-Fi connectivity issues in CSE Block 2nd floor"
          </p>
          <p className="text-[11px] text-emerald-400 pt-1">
            ✓ Auto-linked and consolidated under core CSE network queue to prevent redundant technician dispatches.
          </p>
        </div>
      </div>
    </div>
  );
}
