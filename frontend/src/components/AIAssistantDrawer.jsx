import React, { useState } from 'react';
import { Sparkles, X, Send, Bot, ChevronRight, Loader2, RefreshCw } from 'lucide-react';
import { aiAssistantAPI } from '../services/api';
import { useAuth } from '../context/AuthContext';

export default function AIAssistantDrawer({ isOpen, onClose }) {
  const { user } = useAuth();
  const [messages, setMessages] = useState([
    {
      role: 'assistant',
      summary: "Here's a quick summary for you.",
      insights: [
        "SLA breaches are up 15% due to Network and Access related delays.",
        "VPN and MFA articles have the highest impact and low deflection rate.",
        "Negative sentiment increased 8% on password reset issues."
      ],
      action: "Review CSE and Network access point firmware, and publish updated self-service guides for student MFA."
    }
  ]);
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);

  const quickChips = [
    "Summarize ticket trends",
    "Why are SLA breaches increasing?",
    "Show me top knowledge gaps",
    "Suggest actions to improve CSAT"
  ];

  const handleSend = async (promptText) => {
    const text = promptText || input;
    if (!text.trim() || loading) return;

    const userMessage = { role: 'user', content: text };
    setMessages(prev => [...prev, userMessage]);
    setInput('');
    setLoading(true);

    try {
      const res = await aiAssistantAPI.chat(text);
      if (res.success && res.data) {
        const aiMsg = {
          role: 'assistant',
          summary: res.data.summary,
          insights: res.data.key_insights || [],
          action: res.data.action_recommendation
        };
        setMessages(prev => [...prev, aiMsg]);
      }
    } catch (err) {
      console.error("AI assistant error:", err);
      setMessages(prev => [...prev, {
        role: 'assistant',
        summary: "Notice:",
        insights: ["Unable to contact AI inference engine. Please check backend connection."],
        action: null
      }]);
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  const firstName = user?.full_name ? user.full_name.split(' ')[0] : 'Alex';

  return (
    <aside className="w-80 md:w-96 bg-[#0B132B] border-l border-slate-800/80 flex flex-col h-[calc(100vh-4rem)] shrink-0 z-20">
      {/* Header */}
      <div className="p-4 border-b border-slate-800/80 flex items-center justify-between bg-[#0F172A]/70">
        <div className="flex items-center space-x-2.5">
          <div className="w-8 h-8 rounded-xl bg-blue-600/20 text-blue-400 border border-blue-500/30 flex items-center justify-center">
            <Sparkles className="w-4 h-4" />
          </div>
          <div>
            <h3 className="text-xs font-semibold text-white">AI Assistant</h3>
            <p className="text-[11px] text-slate-400">Always here to help</p>
          </div>
        </div>
        {onClose && (
          <button 
            onClick={onClose}
            className="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
          >
            <X className="w-4 h-4" />
          </button>
        )}
      </div>

      {/* Chat & Insights Body */}
      <div className="flex-1 overflow-y-auto p-4 space-y-4">
        {/* Greeting */}
        <div className="p-3.5 rounded-2xl bg-[#111C3A] border border-slate-800/80">
          <p className="text-xs text-white font-medium">
            Hi {firstName}! 👋
          </p>
          <p className="text-xs text-slate-400 mt-0.5">
            How can I assist with today?
          </p>
        </div>

        {/* Quick Action Chips */}
        <div className="space-y-1.5">
          {quickChips.map((chip, idx) => (
            <button
              key={idx}
              onClick={() => handleSend(chip)}
              className="w-full text-left text-xs px-3 py-2 rounded-xl bg-[#131F42] hover:bg-blue-600/20 text-slate-300 hover:text-blue-300 border border-slate-800 hover:border-blue-500/40 transition flex items-center justify-between group"
            >
              <span className="truncate">{chip}</span>
              <ChevronRight className="w-3.5 h-3.5 text-slate-500 group-hover:text-blue-400 transition" />
            </button>
          ))}
        </div>

        {/* Conversation Stream */}
        {messages.map((msg, index) => (
          <div key={index} className="space-y-2">
            {msg.role === 'user' ? (
              <div className="flex justify-end">
                <div className="max-w-[85%] bg-blue-600 text-white text-xs px-3 py-2 rounded-2xl rounded-tr-sm">
                  {msg.content}
                </div>
              </div>
            ) : (
              <div className="space-y-2">
                <p className="text-[11px] text-slate-400 font-medium px-1">
                  {msg.summary}
                </p>

                {msg.insights && msg.insights.length > 0 && (
                  <div className="p-3.5 rounded-2xl bg-[#0F1D40]/80 border border-blue-900/40 space-y-2.5">
                    <p className="text-xs font-semibold text-blue-300 flex items-center gap-1.5">
                      <Bot className="w-3.5 h-3.5 text-blue-400" />
                      Key Insights
                    </p>
                    <ul className="space-y-2">
                      {msg.insights.map((insight, i) => (
                        <li key={i} className="text-xs text-slate-300 flex items-start space-x-2">
                          <span className="w-1.5 h-1.5 rounded-full bg-blue-400 mt-1.5 shrink-0"></span>
                          <span>{insight}</span>
                        </li>
                      ))}
                    </ul>
                    {msg.action && (
                      <div className="mt-2 pt-2 border-t border-blue-900/40 text-[11px] text-slate-300">
                        <span className="font-semibold text-blue-400">Action: </span>
                        {msg.action}
                      </div>
                    )}
                    <button className="w-full mt-2 py-1.5 text-center text-xs font-medium rounded-xl bg-blue-600/30 hover:bg-blue-600/50 text-blue-200 border border-blue-500/30 transition">
                      View full report
                    </button>
                  </div>
                )}
              </div>
            )}
          </div>
        ))}

        {loading && (
          <div className="flex items-center space-x-2 text-xs text-slate-400 p-3 rounded-2xl bg-[#111C3A] border border-slate-800">
            <Loader2 className="w-3.5 h-3.5 animate-spin text-blue-400" />
            <span>AI analyzing telemetry and knowledge base...</span>
          </div>
        )}
      </div>

      {/* Input Area */}
      <div className="p-3 border-t border-slate-800/80 bg-[#0F172A]/70">
        <form 
          onSubmit={(e) => { e.preventDefault(); handleSend(); }}
          className="relative"
        >
          <input
            type="text"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            placeholder="Ask anything..."
            disabled={loading}
            className="w-full bg-[#111C3A] text-slate-100 placeholder-slate-500 text-xs rounded-xl pl-3.5 pr-10 py-2.5 border border-slate-800 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition shadow-inner"
          />
          <button
            type="submit"
            disabled={loading || !input.trim()}
            className="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-blue-400 hover:text-white hover:bg-blue-600 disabled:opacity-40 rounded-lg transition"
          >
            <Send className="w-3.5 h-3.5" />
          </button>
        </form>
      </div>
    </aside>
  );
}
