import React, { useState } from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate, Outlet } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';

// Components
import OperationsHeader from './components/OperationsHeader';
import OperationsSidebar from './components/OperationsSidebar';
import AIAssistantDrawer from './components/AIAssistantDrawer';

// Pages
import Login from './pages/Login';
import OperationsDashboard from './pages/OperationsDashboard';
import TicketsPage from './pages/TicketsPage';
import ReportIncident from './pages/ReportIncident';
import IncidentDetails from './pages/IncidentDetails';
import KnowledgePage from './pages/KnowledgePage';
import ProblemsPage from './pages/ProblemsPage';

const AppLayout = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [isAiDrawerOpen, setIsAiDrawerOpen] = useState(true);

  return (
    <div className="min-h-screen bg-[#070D1E] flex flex-col font-sans">
      <OperationsHeader 
        searchTerm={searchTerm} 
        onSearchChange={setSearchTerm} 
      />
      <div className="flex-1 flex overflow-hidden">
        <OperationsSidebar />
        <main className="flex-1 overflow-y-auto bg-[#070D1E]">
          <Outlet context={{ searchTerm }} />
        </main>
        <AIAssistantDrawer 
          isOpen={isAiDrawerOpen} 
          onClose={() => setIsAiDrawerOpen(false)} 
        />
      </div>
    </div>
  );
};

const ProtectedRoute = ({ children }) => {
  const { user, loading } = useAuth();

  if (loading) {
    return (
      <div className="min-h-screen bg-[#070D1E] flex items-center justify-center">
        <div className="w-8 h-8 border-3 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
      </div>
    );
  }

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  return children;
};

export default function App() {
  return (
    <AuthProvider>
      <Router>
        <Routes>
          <Route path="/login" element={<Login />} />

          <Route 
            element={
              <ProtectedRoute>
                <AppLayout />
              </ProtectedRoute>
            }
          >
            <Route path="/" element={<OperationsDashboard />} />
            <Route path="/overview" element={<OperationsDashboard />} />
            <Route path="/tickets" element={<TicketsPage />} />
            <Route path="/tasks" element={<TicketsPage />} />
            <Route path="/incidents" element={<TicketsPage />} />
            <Route path="/incidents/:id" element={<IncidentDetails />} />
            <Route path="/report" element={<ReportIncident />} />
            <Route path="/problems" element={<ProblemsPage />} />
            <Route path="/knowledge" element={<KnowledgePage />} />
            <Route path="/analytics" element={<OperationsDashboard />} />
            <Route path="/reports" element={<OperationsDashboard />} />
            <Route path="/changes" element={<ProblemsPage />} />
            <Route path="/settings" element={<ProblemsPage />} />
          </Route>

          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </Router>
    </AuthProvider>
  );
}
