import React, { useState, useEffect } from 'react';
import { BookOpen, Search, ThumbsUp, CheckCircle, ExternalLink, Lightbulb } from 'lucide-react';
import { knowledgeAPI } from '../services/api';
import { useSearchParams } from 'react-router-dom';

export default function KnowledgePage() {
  const [searchParams] = useSearchParams();
  const [articles, setArticles] = useState([]);
  const [selectedArticle, setSelectedArticle] = useState(null);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    knowledgeAPI.getArticles({ search })
      .then(res => {
        if (res.success && res.data) {
          setArticles(res.data);
          const articleId = searchParams.get('article');
          if (articleId) {
            const found = res.data.find(a => a.id === parseInt(articleId));
            if (found) setSelectedArticle(found);
          } else if (res.data.length > 0 && !selectedArticle) {
            setSelectedArticle(res.data[0]);
          }
        }
      })
      .catch(err => console.error(err))
      .finally(() => setLoading(false));
  }, [search]);

  return (
    <div className="p-4 sm:p-6 space-y-6 max-w-[1600px] mx-auto">
      <div>
        <h2 className="text-xl font-bold text-white tracking-tight">Resolution Intelligence & Knowledge Base</h2>
        <p className="text-xs text-slate-400 mt-1">
          Verified solution articles and automated resolution runbooks curated from resolved campus tickets.
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left: Article list */}
        <div className="space-y-3">
          <div className="relative">
            <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Search verified solutions..."
              className="w-full bg-[#0B132B] text-slate-100 placeholder-slate-500 text-xs rounded-xl pl-9 pr-3 py-2.5 border border-slate-800 focus:outline-none focus:border-blue-500"
            />
          </div>

          <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl divide-y divide-slate-800/50 overflow-hidden shadow-sm">
            {articles.map((art) => (
              <div
                key={art.id}
                onClick={() => setSelectedArticle(art)}
                className={`p-3.5 cursor-pointer transition ${
                  selectedArticle?.id === art.id ? 'bg-blue-600/10 border-l-2 border-blue-500' : 'hover:bg-slate-800/30'
                }`}
              >
                <div className="flex items-start justify-between">
                  <h4 className="text-xs font-semibold text-white line-clamp-1">{art.title}</h4>
                  <span className="text-xs font-bold text-emerald-400 shrink-0 ml-2">{art.usefulness_pct}%</span>
                </div>
                <p className="text-[11px] text-slate-400 line-clamp-2 mt-1">{art.summary}</p>
                <div className="flex items-center space-x-2 mt-2 text-[10px] text-slate-500">
                  <span className="px-2 py-0.5 rounded bg-slate-800 text-slate-300 font-medium">{art.category}</span>
                  <span>• {art.deflection_count} successful auto-resolutions</span>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Right: Article Details */}
        <div className="lg:col-span-2">
          {selectedArticle ? (
            <div className="bg-[#0B132B] border border-slate-800/80 rounded-2xl p-6 space-y-5 shadow-sm">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-800">
                <div>
                  <span className="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20">
                    {selectedArticle.category} • {selectedArticle.department_code || 'General'}
                  </span>
                  <h3 className="text-base font-bold text-white mt-2">{selectedArticle.title}</h3>
                </div>
                <div className="flex items-center space-x-2">
                  <span className="text-xs font-semibold text-emerald-400 bg-emerald-500/10 px-3 py-1.5 rounded-xl border border-emerald-500/20">
                    {selectedArticle.usefulness_pct}% Verified Usefulness
                  </span>
                </div>
              </div>

              <div>
                <h4 className="text-xs font-semibold text-slate-300 mb-1">Summary</h4>
                <p className="text-xs text-slate-400 leading-relaxed">{selectedArticle.summary}</p>
              </div>

              <div className="p-4 rounded-2xl bg-[#091C28]/60 border border-emerald-900/30 space-y-3">
                <h4 className="text-xs font-semibold text-emerald-300 flex items-center gap-1.5">
                  <Lightbulb className="w-4 h-4 text-emerald-400" />
                  <span>Standard Operating Resolution Steps</span>
                </h4>
                <div className="space-y-2 text-xs text-slate-200">
                  {selectedArticle.solution_steps.split('\n').map((step, idx) => (
                    <div key={idx} className="flex items-start space-x-2">
                      <span className="text-emerald-400 font-semibold">{idx + 1}.</span>
                      <p className="text-slate-300">{step.replace(/^\d+\.\s*/, '')}</p>
                    </div>
                  ))}
                </div>
              </div>

              <div className="pt-3 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                <span>Tags: {selectedArticle.tags}</span>
                <span className="text-slate-500">Updated from verified tickets</span>
              </div>
            </div>
          ) : (
            <div className="bg-[#0B132B] border border-slate-800 rounded-2xl p-10 text-center text-slate-400 text-xs">
              Select an article to review resolution steps.
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
