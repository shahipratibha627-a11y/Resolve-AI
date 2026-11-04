import React, { useState, useEffect } from 'react';
import { 
  Sparkles, CheckCircle, AlertTriangle, ArrowRight, 
  HelpCircle, Lightbulb, Shield, Loader2, Send 
} from 'lucide-react';
import { incidentAPI, locationAPI, departmentAPI } from '../services/api';
import { useNavigate } from 'react-router-dom';

export default function ReportIncident() {
  const navigate = useNavigate();
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [locationName, setLocationName] = useState('CSE Block');
  const [roomLab, setRoomLab] = useState('');
  const [category, setCategory] = useState('');
  const [priority, setPriority] = useState('MEDIUM');
  const [departmentId, setDepartmentId] = useState('');
  const [isAnonymous, setIsAnonymous] = useState(false);

  const [departments, setDepartments] = useState([]);
  const [locations, setLocations] = useState([]);
  const [categories, setCategories] = useState([]);

  const [aiLoading, setAiLoading] = useState(false);
  const [aiPreview, setAiPreview] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    departmentAPI.getDepartments().then(res => res.success && setDepartments(res.data));
    locationAPI.getLocations().then(res => res.success && setLocations(res.data));
    locationAPI.getCategories().then(res => res.success && setCategories(res.data));
  }, []);

  const handleAIAnalyze = async () => {
    if (!title.trim() || !description.trim()) {
      setError('Please provide a title and detailed problem description for AI classification.');
      return;
    }
    setError('');
    setAiLoading(true);

    try {
      const res = await incidentAPI.aiPreview({
        title,
        description,
        location_name: locationName
      });

      if (res.success && res.data) {
        setAiPreview(res.data);
      }
    } catch (err) {
      setError(err.message || 'AI analysis failed');
    } finally {
      setAiLoading(false);
    }
  };

  const applyAIPrediction = () => {
    if (!aiPreview) return;
    setCategory(aiPreview.suggested_category);
    setPriority(aiPreview.suggested_priority);
    const matchedDept = departments.find(d => d.code === aiPreview.suggested_department_code);
    if (matchedDept) {
      setDepartmentId(matchedDept.id);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!title.trim() || !description.trim()) {
      setError('Title and description are required.');
      return;
    }
    setSubmitting(true);
    setError('');

    try {
      const res = await incidentAPI.createIncident({
        title,
        description,
        location_name: locationName,
        room_lab: roomLab,
        category: category || (aiPreview ? aiPreview.suggested_category : 'Maintenance'),
        priority: priority || (aiPreview ? aiPreview.suggested_priority : 'MEDIUM'),
        department_id: departmentId || null,
        is_anonymous: isAnonymous
      });

      if (res.success && res.data) {
        navigate(`/incidents/${res.data.id}`);
      }
    } catch (err) {
      setError(err.message || 'Failed to submit incident');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="max-w-4xl mx-auto p-4 sm:p-6 space-y-6">
      <div>
        <h2 className="text-xl font-bold text-white tracking-tight">Report an Issue</h2>
        <p className="text-xs text-slate-400 mt-1">
          Submit campus infrastructure, IT, equipment, or facility faults. ResolveAI will triage and route the ticket automatically.
        </p>
      </div>

      {error && (
        <div className="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2">
          <AlertTriangle className="w-4 h-4 shrink-0" />
          <span>{error}</span>
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Form Column */}
        <div className="lg:col-span-2 space-y-4">
          <form onSubmit={handleSubmit} className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-5 space-y-4 shadow-sm">
            <div>
              <label className="block text-xs font-medium text-slate-300 mb-1.5">Issue Title *</label>
              <input 
                type="text"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                placeholder="e.g. Wi-Fi connection down in CSE Block Lab 3"
                className="w-full bg-[#111C3A] text-slate-100 placeholder-slate-500 text-xs rounded-xl px-3.5 py-2.5 border border-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                required
              />
            </div>

            <div>
              <label className="block text-xs font-medium text-slate-300 mb-1.5">Detailed Description *</label>
              <textarea 
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                rows={4}
                placeholder="Describe the issue, symptoms, affected equipment, and severity..."
                className="w-full bg-[#111C3A] text-slate-100 placeholder-slate-500 text-xs rounded-xl px-3.5 py-2.5 border border-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                required
              />
            </div>

            {/* AI Analyze Trigger Bar */}
            <div className="p-3 rounded-xl bg-[#131F42] border border-blue-900/30 flex items-center justify-between">
              <div className="flex items-center space-x-2">
                <Sparkles className="w-4 h-4 text-blue-400" />
                <span className="text-xs text-blue-200 font-medium">Let AI Classify This Issue</span>
              </div>
              <button
                type="button"
                onClick={handleAIAnalyze}
                disabled={aiLoading || !title.trim()}
                className="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 disabled:opacity-40 text-white text-xs font-medium shadow transition flex items-center gap-1.5"
              >
                {aiLoading ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Sparkles className="w-3.5 h-3.5" />}
                <span>Analyze</span>
              </button>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
              <div>
                <label className="block text-xs font-medium text-slate-300 mb-1.5">Campus Location / Block</label>
                <select 
                  value={locationName}
                  onChange={(e) => setLocationName(e.target.value)}
                  className="w-full bg-[#111C3A] text-slate-100 text-xs rounded-xl px-3 py-2.5 border border-slate-800 focus:outline-none focus:border-blue-500"
                >
                  {locations.map(loc => (
                    <option key={loc.id} value={loc.name}>{loc.name}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-medium text-slate-300 mb-1.5">Room / Lab (Optional)</label>
                <input 
                  type="text"
                  value={roomLab}
                  onChange={(e) => setRoomLab(e.target.value)}
                  placeholder="e.g. Lab 2, Room 304"
                  className="w-full bg-[#111C3A] text-slate-100 placeholder-slate-500 text-xs rounded-xl px-3.5 py-2.5 border border-slate-800 focus:outline-none focus:border-blue-500"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
              <div>
                <label className="block text-xs font-medium text-slate-300 mb-1.5">Category</label>
                <select 
                  value={category}
                  onChange={(e) => setCategory(e.target.value)}
                  className="w-full bg-[#111C3A] text-slate-100 text-xs rounded-xl px-3 py-2.5 border border-slate-800 focus:outline-none focus:border-blue-500"
                >
                  <option value="">Auto (AI Recommended)</option>
                  {categories.map((c, i) => (
                    <option key={i} value={c}>{c}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-medium text-slate-300 mb-1.5">Priority</label>
                <select 
                  value={priority}
                  onChange={(e) => setPriority(e.target.value)}
                  className="w-full bg-[#111C3A] text-slate-100 text-xs rounded-xl px-3 py-2.5 border border-slate-800 focus:outline-none focus:border-blue-500"
                >
                  <option value="LOW">LOW</option>
                  <option value="MEDIUM">MEDIUM</option>
                  <option value="HIGH">HIGH</option>
                  <option value="CRITICAL">CRITICAL</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-medium text-slate-300 mb-1.5">Department</label>
                <select 
                  value={departmentId}
                  onChange={(e) => setDepartmentId(e.target.value)}
                  className="w-full bg-[#111C3A] text-slate-100 text-xs rounded-xl px-3 py-2.5 border border-slate-800 focus:outline-none focus:border-blue-500"
                >
                  <option value="">Auto (AI Routed)</option>
                  {departments.map(d => (
                    <option key={d.id} value={d.id}>{d.code} - {d.name}</option>
                  ))}
                </select>
              </div>
            </div>

            <div className="pt-2 flex items-center justify-between">
              <label className="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                <input 
                  type="checkbox"
                  checked={isAnonymous}
                  onChange={(e) => setIsAnonymous(e.target.checked)}
                  className="rounded border-slate-700 text-blue-600 focus:ring-0 bg-slate-800"
                />
                <span>Submit anonymously (identity hidden from staff)</span>
              </label>

              <button
                type="submit"
                disabled={submitting}
                className="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 disabled:opacity-40 text-white text-xs font-semibold shadow-lg shadow-blue-600/30 transition flex items-center gap-1.5"
              >
                {submitting ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Send className="w-3.5 h-3.5" />}
                <span>Submit Ticket</span>
              </button>
            </div>
          </form>
        </div>

        {/* AI Preview & Resolution Intelligence Column */}
        <div className="space-y-4">
          {aiPreview ? (
            <div className="bg-[#0B132B] border border-blue-500/30 rounded-2xl p-4 space-y-3.5 shadow-lg">
              <div className="flex items-center justify-between pb-2 border-b border-slate-800">
                <div className="flex items-center space-x-1.5">
                  <Sparkles className="w-4 h-4 text-blue-400" />
                  <span className="text-xs font-semibold text-white">AI Classification</span>
                </div>
                <span className="text-xs font-bold text-emerald-400">
                  {aiPreview.confidence_score}% Confidence
                </span>
              </div>

              <div className="space-y-2 text-xs">
                <div className="flex justify-between py-1 border-b border-slate-800/60">
                  <span className="text-slate-400">Category:</span>
                  <span className="font-semibold text-white">{aiPreview.suggested_category}</span>
                </div>
                <div className="flex justify-between py-1 border-b border-slate-800/60">
                  <span className="text-slate-400">Responsible Dept:</span>
                  <span className="font-semibold text-blue-400">{aiPreview.suggested_department_code}</span>
                </div>
                <div className="flex justify-between py-1 border-b border-slate-800/60">
                  <span className="text-slate-400">Priority:</span>
                  <span className={`font-bold px-2 py-0.5 rounded text-[10px] ${
                    aiPreview.suggested_priority === 'CRITICAL' ? 'bg-rose-500/20 text-rose-400' :
                    aiPreview.suggested_priority === 'HIGH' ? 'bg-amber-500/20 text-amber-400' :
                    'bg-blue-500/20 text-blue-400'
                  }`}>
                    {aiPreview.suggested_priority}
                  </span>
                </div>
              </div>

              <p className="text-[11px] text-slate-300 italic bg-[#111C3A] p-2.5 rounded-xl border border-slate-800">
                "{aiPreview.reasoning}"
              </p>

              {aiPreview.is_duplicate && (
                <div className="p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs space-y-1">
                  <div className="flex items-center gap-1.5 font-semibold">
                    <AlertTriangle className="w-3.5 h-3.5 text-amber-400" />
                    <span>Potential Duplicate Detected ({aiPreview.duplicate_similarity}%)</span>
                  </div>
                  <p className="text-[11px] text-amber-200/80">
                    Similar ticket logged: <span className="underline font-mono">{aiPreview.duplicate_incident_number}</span>
                  </p>
                </div>
              )}

              <button
                type="button"
                onClick={applyAIPrediction}
                className="w-full py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-xl transition shadow"
              >
                Accept AI Analysis
              </button>

              {/* Resolution Intelligence: Instant Troubleshooting Recommendation */}
              {aiPreview.recommended_solutions && aiPreview.recommended_solutions.length > 0 && (
                <div className="pt-3 border-t border-slate-800 space-y-2">
                  <div className="flex items-center gap-1.5 text-xs font-semibold text-emerald-400">
                    <Lightbulb className="w-3.5 h-3.5 text-emerald-400" />
                    <span>Instant Suggested Solution:</span>
                  </div>
                  <div className="p-3 rounded-xl bg-emerald-950/20 border border-emerald-900/30 text-xs text-slate-200 space-y-1.5">
                    <p className="font-medium text-white">{aiPreview.recommended_solutions[0].title}</p>
                    <ul className="list-disc list-inside text-[11px] text-slate-300 space-y-1">
                      {aiPreview.recommended_solutions[0].solution_steps.slice(0, 3).map((st, idx) => (
                        <li key={idx}>{st}</li>
                      ))}
                    </ul>
                    <p className="text-[10px] text-emerald-400 pt-1 font-semibold">
                      ✓ {aiPreview.recommended_solutions[0].usefulness_pct}% solved similar issues without technician dispatch
                    </p>
                  </div>
                </div>
              )}
            </div>
          ) : (
            <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-5 text-center space-y-3">
              <div className="w-10 h-10 rounded-2xl bg-blue-500/10 text-blue-400 border border-blue-500/20 mx-auto flex items-center justify-center">
                <Sparkles className="w-5 h-5" />
              </div>
              <h4 className="text-xs font-semibold text-white">AI Real-Time Triage</h4>
              <p className="text-xs text-slate-400">
                Click "Analyze" to see automated category detection, confidence score, department routing, and instant verified troubleshooting steps.
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
