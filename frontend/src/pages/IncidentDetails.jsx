import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { 
  ArrowLeft, CheckCircle2, Clock, AlertTriangle, Lightbulb, 
  ThumbsUp, ThumbsDown, MessageSquare, Shield, Sparkles, Send, 
  User, CheckCircle, HelpCircle, Star 
} from 'lucide-react';
import { incidentAPI } from '../services/api';
import { useAuth } from '../context/AuthContext';

export default function IncidentDetails() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();

  const [incident, setIncident] = useState(null);
  const [loading, setLoading] = useState(true);
  const [commentText, setCommentText] = useState('');
  const [solutionFeedbackStatus, setSolutionFeedbackStatus] = useState(null);
  const [statusUpdating, setStatusUpdating] = useState(false);
  const [resolutionNotes, setResolutionNotes] = useState('');
  const [selectedStatus, setSelectedStatus] = useState('');

  // 5-Star rating modal
  const [rating, setRating] = useState(5);
  const [feedbackComment, setFeedbackComment] = useState('');
  const [feedbackSubmitted, setFeedbackSubmitted] = useState(false);

  useEffect(() => {
    fetchIncident();
  }, [id]);

  const fetchIncident = async () => {
    try {
      const res = await incidentAPI.getIncident(id);
      if (res.success && res.data) {
        setIncident(res.data);
        setSelectedStatus(res.data.status);
      }
    } catch (err) {
      console.error("Error loading incident:", err);
    } finally {
      setLoading(false);
    }
  };

  const handleSolutionFeedback = async (worked) => {
    try {
      await incidentAPI.solutionFeedback(id, { worked });
      setSolutionFeedbackStatus(worked ? 'WORKED' : 'NOT_WORKED');
      fetchIncident();
    } catch (err) {
      console.error("Failed to record solution feedback:", err);
    }
  };

  const handleStatusUpdate = async (e) => {
    e.preventDefault();
    if (!selectedStatus) return;
    setStatusUpdating(true);
    try {
      await incidentAPI.updateStatus(id, {
        status: selectedStatus,
        resolution_notes: resolutionNotes || undefined
      });
      fetchIncident();
    } catch (err) {
      console.error("Failed to update status:", err);
    } finally {
      setStatusUpdating(false);
    }
  };

  const handleAddComment = async (e) => {
    e.preventDefault();
    if (!commentText.trim()) return;
    try {
      await incidentAPI.addComment(id, { comment: commentText });
      setCommentText('');
      fetchIncident();
    } catch (err) {
      console.error("Failed to add comment:", err);
    }
  };

  const handleFeedbackSubmit = async (e) => {
    e.preventDefault();
    try {
      await incidentAPI.submitFeedback(id, { rating, comments: feedbackComment });
      setFeedbackSubmitted(true);
      fetchIncident();
    } catch (err) {
      console.error("Failed to submit feedback:", err);
    }
  };

  if (loading || !incident) {
    return (
      <div className="flex-1 p-6 flex items-center justify-center min-h-[500px]">
        <div className="w-8 h-8 border-3 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
      </div>
    );
  }

  const isStaffOrAdmin = user?.role === 'staff' || user?.role === 'admin';
  const statuses = ['REPORTED', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'];
  const currentStep = statuses.indexOf(incident.status);

  return (
    <div className="max-w-5xl mx-auto p-4 sm:p-6 space-y-6">
      {/* Back button and title */}
      <div className="flex items-center justify-between">
        <button 
          onClick={() => navigate(-1)}
          className="flex items-center space-x-1.5 text-xs text-slate-400 hover:text-white transition"
        >
          <ArrowLeft className="w-4 h-4" />
          <span>Back</span>
        </button>
        <span className="font-mono text-xs px-2.5 py-1 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20 font-semibold">
          {incident.incident_number}
        </span>
      </div>

      {/* Main Header Card */}
      <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-5 space-y-4 shadow-sm">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h2 className="text-lg font-bold text-white">{incident.title}</h2>
            <p className="text-xs text-slate-400 mt-1">
              Reported in <span className="text-slate-200 font-medium">{incident.location_name}</span> 
              {incident.room_lab && ` • ${incident.room_lab}`} by{' '}
              <span className="text-slate-200 font-medium">{incident.student_name}</span> on{' '}
              {new Date(incident.created_at).toLocaleDateString()}
            </p>
          </div>
          <div className="flex items-center space-x-2">
            <span className={`text-[11px] font-bold px-2.5 py-1 rounded-lg border ${
              incident.priority === 'CRITICAL' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' :
              incident.priority === 'HIGH' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' :
              'bg-blue-500/10 text-blue-400 border-blue-500/20'
            }`}>
              {incident.priority} PRIORITY
            </span>
            <span className="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300 border border-slate-700">
              {incident.category}
            </span>
          </div>
        </div>

        {/* Multi-step Status Tracker */}
        <div className="pt-2">
          <p className="text-[11px] text-slate-400 font-medium mb-3">Resolution Lifecycle Progress:</p>
          <div className="grid grid-cols-5 gap-2">
            {statuses.map((st, idx) => (
              <div 
                key={st}
                className={`py-2 px-1 text-center rounded-xl text-[10px] font-bold tracking-wider transition ${
                  idx <= currentStep 
                    ? 'bg-blue-600 text-white shadow-sm shadow-blue-600/30' 
                    : 'bg-[#111C3A] text-slate-500 border border-slate-800'
                }`}
              >
                {st}
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left Column: Description & Resolution Intelligence */}
        <div className="lg:col-span-2 space-y-5">
          {/* Problem Description */}
          <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-5 space-y-2.5">
            <h4 className="text-xs font-semibold text-white">Problem Description</h4>
            <p className="text-xs text-slate-300 leading-relaxed whitespace-pre-line">
              {incident.description}
            </p>
          </div>

          {/* PILLAR 3: Resolution Intelligence — AI Recommended Solutions */}
          {incident.recommended_solutions && incident.recommended_solutions.length > 0 && (
            <div className="bg-[#0B132B] border border-emerald-500/30 rounded-2xl p-5 space-y-4 shadow-lg shadow-emerald-950/20">
              <div className="flex items-center justify-between pb-3 border-b border-slate-800">
                <div className="flex items-center space-x-2">
                  <div className="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <Lightbulb className="w-4 h-4" />
                  </div>
                  <div>
                    <h4 className="text-xs font-semibold text-white">AI Recommended Solution</h4>
                    <p className="text-[10px] text-slate-400">Derived from verified past cases & knowledge articles</p>
                  </div>
                </div>
                <span className="text-xs font-bold text-emerald-400">
                  {incident.recommended_solutions[0].confidence}% Match
                </span>
              </div>

              <div className="p-3.5 rounded-xl bg-[#091C28]/80 border border-emerald-900/40 space-y-2">
                <p className="text-xs font-semibold text-white">
                  {incident.recommended_solutions[0].title}
                </p>
                <div className="space-y-1.5 text-xs text-slate-200">
                  {incident.recommended_solutions[0].solution_steps.map((step, i) => (
                    <div key={i} className="flex items-start space-x-2">
                      <span className="font-semibold text-emerald-400">{i + 1}.</span>
                      <p className="text-slate-300 text-[11px]">{step}</p>
                    </div>
                  ))}
                </div>
                <p className="text-[10px] text-slate-400 italic pt-1">
                  Source: {incident.recommended_solutions[0].recommendation_summary}
                </p>
              </div>

              {/* Solution Effectiveness Feedback Loop */}
              <div className="p-3.5 rounded-xl bg-[#111C3A] border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div>
                  <p className="text-xs font-semibold text-slate-200">Did this solution solve the problem?</p>
                  <p className="text-[10px] text-slate-400">Your feedback trains the resolution intelligence model.</p>
                </div>
                <div className="flex items-center space-x-2">
                  <button
                    onClick={() => handleSolutionFeedback(true)}
                    className={`flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition ${
                      incident.resolution_worked === 1 || solutionFeedbackStatus === 'WORKED'
                        ? 'bg-emerald-600 text-white font-bold'
                        : 'bg-emerald-950/40 text-emerald-400 hover:bg-emerald-900/60 border border-emerald-800/40'
                    }`}
                  >
                    <ThumbsUp className="w-3.5 h-3.5" />
                    <span>Yes, Worked!</span>
                  </button>
                  <button
                    onClick={() => handleSolutionFeedback(false)}
                    className={`flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition ${
                      incident.resolution_worked === 0 || solutionFeedbackStatus === 'NOT_WORKED'
                        ? 'bg-rose-600 text-white font-bold'
                        : 'bg-rose-950/40 text-rose-400 hover:bg-rose-900/60 border border-rose-800/40'
                    }`}
                  >
                    <ThumbsDown className="w-3.5 h-3.5" />
                    <span>Need Technician</span>
                  </button>
                </div>
              </div>
            </div>
          )}

          {/* Resolution Notes If Resolved */}
          {incident.resolution_notes && (
            <div className="bg-[#0B132B] border border-emerald-500/20 rounded-2xl p-5 space-y-2">
              <h4 className="text-xs font-semibold text-emerald-400 flex items-center gap-1.5">
                <CheckCircle2 className="w-4 h-4 text-emerald-400" />
                <span>Verified Diagnostic & Resolution Notes</span>
              </h4>
              <p className="text-xs text-slate-200 whitespace-pre-line bg-[#0B2135]/50 p-3 rounded-xl border border-emerald-900/30">
                {incident.resolution_notes}
              </p>
            </div>
          )}

          {/* Student 5-Star Feedback Section */}
          {incident.status === 'RESOLVED' && !incident.feedback && (
            <div className="bg-[#0B132B] border border-blue-500/30 rounded-2xl p-5 space-y-3">
              <h4 className="text-xs font-semibold text-white">Rate Resolution Satisfaction</h4>
              <form onSubmit={handleFeedbackSubmit} className="space-y-3">
                <div className="flex items-center space-x-1">
                  {[1, 2, 3, 4, 5].map((star) => (
                    <button
                      type="button"
                      key={star}
                      onClick={() => setRating(star)}
                      className="p-1 text-amber-400 hover:scale-110 transition"
                    >
                      <Star className={`w-5 h-5 ${star <= rating ? 'fill-amber-400' : 'text-slate-600'}`} />
                    </button>
                  ))}
                  <span className="text-xs font-semibold text-slate-300 ml-2">{rating} / 5 Stars</span>
                </div>
                <input
                  type="text"
                  value={feedbackComment}
                  onChange={(e) => setFeedbackComment(e.target.value)}
                  placeholder="Leave feedback on resolution speed and quality..."
                  className="w-full bg-[#111C3A] text-slate-100 placeholder-slate-500 text-xs rounded-xl px-3.5 py-2 border border-slate-800 focus:outline-none focus:border-blue-500"
                />
                <button
                  type="submit"
                  className="px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-medium"
                >
                  Submit Review
                </button>
              </form>
            </div>
          )}

          {incident.feedback && (
            <div className="bg-[#0B132B] border border-slate-800 rounded-2xl p-4 space-y-2">
              <div className="flex items-center justify-between">
                <span className="text-xs font-semibold text-white">Student Review</span>
                <div className="flex items-center text-amber-400">
                  <Star className="w-3.5 h-3.5 fill-amber-400 mr-1" />
                  <span className="text-xs font-bold">{incident.feedback.rating} / 5</span>
                </div>
              </div>
              <p className="text-xs text-slate-300 italic">"{incident.feedback.comments}"</p>
            </div>
          )}

          {/* Audit Timeline Updates */}
          <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-5 space-y-3">
            <h4 className="text-xs font-semibold text-white">Incident Audit Trail & Comments</h4>
            <div className="divide-y divide-slate-800/50">
              {incident.updates && incident.updates.map((up) => (
                <div key={up.id} className="py-2.5 space-y-1">
                  <div className="flex items-center justify-between text-[11px]">
                    <span className="font-semibold text-blue-400">{up.update_type}</span>
                    <span className="text-slate-500">{new Date(up.created_at).toLocaleString()}</span>
                  </div>
                  <p className="text-xs text-slate-300">{up.notes}</p>
                </div>
              ))}
            </div>

            <form onSubmit={handleAddComment} className="pt-2 flex gap-2">
              <input
                type="text"
                value={commentText}
                onChange={(e) => setCommentText(e.target.value)}
                placeholder="Add comment or technical update..."
                className="flex-1 bg-[#111C3A] text-slate-100 placeholder-slate-500 text-xs rounded-xl px-3.5 py-2 border border-slate-800 focus:outline-none focus:border-blue-500"
              />
              <button
                type="submit"
                disabled={!commentText.trim()}
                className="px-3 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 disabled:opacity-40 text-white text-xs font-medium"
              >
                <Send className="w-3.5 h-3.5" />
              </button>
            </form>
          </div>
        </div>

        {/* Right Column: AI Triage & Management Actions */}
        <div className="space-y-5">
          {/* AI Analysis Summary */}
          {incident.ai_analysis && (
            <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-4 space-y-3">
              <div className="flex items-center justify-between pb-2 border-b border-slate-800">
                <span className="text-xs font-semibold text-white flex items-center gap-1.5">
                  <Sparkles className="w-3.5 h-3.5 text-blue-400" />
                  AI Intelligence
                </span>
                <span className="text-xs font-bold text-emerald-400">
                  {incident.ai_analysis.confidence_score}%
                </span>
              </div>
              <p className="text-xs text-slate-300">
                {incident.ai_analysis.summary}
              </p>
              <div className="p-2.5 rounded-xl bg-[#111C3A] border border-slate-800 text-[11px] text-slate-400 space-y-1">
                <p><span className="font-semibold text-slate-300">Assigned Dept: </span>{incident.department_code || incident.ai_analysis.suggested_department_code}</p>
                <p><span className="font-semibold text-slate-300">Routing Rationale: </span>{incident.ai_analysis.reasoning}</p>
              </div>
            </div>
          )}

          {/* Staff/Admin Incident Management Card */}
          {isStaffOrAdmin && (
            <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-4 space-y-3">
              <h4 className="text-xs font-semibold text-white">Coordinator Actions</h4>
              <form onSubmit={handleStatusUpdate} className="space-y-3">
                <div>
                  <label className="block text-[11px] text-slate-400 mb-1">Update Status</label>
                  <select 
                    value={selectedStatus}
                    onChange={(e) => setSelectedStatus(e.target.value)}
                    className="w-full bg-[#111C3A] text-slate-100 text-xs rounded-xl px-3 py-2 border border-slate-800 focus:outline-none focus:border-blue-500"
                  >
                    {statuses.map(st => (
                      <option key={st} value={st}>{st}</option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-[11px] text-slate-400 mb-1">Resolution Diagnostic Notes</label>
                  <textarea
                    value={resolutionNotes}
                    onChange={(e) => setResolutionNotes(e.target.value)}
                    rows={3}
                    placeholder="Describe diagnostic steps taken, replaced components, or verification tests..."
                    className="w-full bg-[#111C3A] text-slate-100 placeholder-slate-500 text-xs rounded-xl px-3 py-2 border border-slate-800 focus:outline-none focus:border-blue-500"
                  />
                </div>

                <button
                  type="submit"
                  disabled={statusUpdating}
                  className="w-full py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold shadow transition"
                >
                  {statusUpdating ? 'Updating...' : 'Save Incident Status'}
                </button>
              </form>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
