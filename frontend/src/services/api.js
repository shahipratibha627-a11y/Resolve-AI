import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api';

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request Interceptor: Attach JWT token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response Interceptor: Uniform error handling & session expiration
api.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response && error.response.status === 401) {
      if (!window.location.pathname.includes('/login')) {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '/login?expired=1';
      }
    }
    const message = error.response?.data?.message || error.message || 'An unexpected error occurred';
    return Promise.reject(new Error(message));
  }
);

// Authentication APIs
export const authAPI = {
  login: (email, password) => api.post('/auth/login', { email, password }),
  register: (userData) => api.post('/auth/register', userData),
  getMe: () => api.get('/auth/me'),
  getDemoAccounts: () => api.get('/auth/demo-accounts'),
};

// Incidents APIs
export const incidentAPI = {
  getIncidents: (params) => api.get('/incidents', { params }),
  getIncident: (id) => api.get(`/incidents/${id}`),
  createIncident: (payload) => api.post('/incidents', payload),
  aiPreview: (payload) => api.post('/incidents/ai-preview', payload),
  updateStatus: (id, payload) => api.patch(`/incidents/${id}/status`, payload),
  reassign: (id, payload) => api.patch(`/incidents/${id}/assign`, payload),
  addComment: (id, payload) => api.post(`/incidents/${id}/comments`, payload),
  submitFeedback: (id, payload) => api.post(`/incidents/${id}/feedback`, payload),
  confirmDuplicate: (id, payload) => api.post(`/incidents/${id}/confirm-duplicate`, payload),
  solutionFeedback: (id, payload) => api.post(`/incidents/${id}/solution-feedback`, payload),
};

// Knowledge Base APIs (Resolution Intelligence)
export const knowledgeAPI = {
  getArticles: (params) => api.get('/knowledge', { params }),
  getArticle: (id) => api.get(`/knowledge/${id}`),
};

// AI Assistant Chat APIs
export const aiAssistantAPI = {
  chat: (prompt) => api.post('/ai/assistant', { prompt }),
};

// Department APIs
export const departmentAPI = {
  getDepartments: (params) => api.get('/departments', { params }),
  getDepartment: (id) => api.get(`/departments/${id}`),
  getMyDepartmentStats: () => api.get('/departments/my-department/stats'),
};

// Campus Locations & Categories APIs
export const locationAPI = {
  getLocations: () => api.get('/locations'),
  getCategories: () => api.get('/locations/categories'),
};

// Analytics & AI Insights APIs
export const analyticsAPI = {
  getOperationsDashboard: () => api.get('/analytics/operations-dashboard'),
  getCampusOverview: () => api.get('/analytics/campus-overview'),
  getDepartmentComparison: () => api.get('/analytics/department-comparison'),
  getHeatmap: () => api.get('/analytics/heatmap'),
  getCharts: () => api.get('/analytics/charts'),
  getAIInsights: () => api.get('/analytics/ai-insights'),
};

// Notifications APIs
export const notificationAPI = {
  getNotifications: () => api.get('/notifications'),
  markRead: (id) => api.patch(`/notifications/${id}/read`),
  markAllRead: () => api.post('/notifications/read-all'),
};

// User Management APIs
export const userAPI = {
  getStaff: () => api.get('/users/staff'),
  getStudents: () => api.get('/users/students'),
};

export default api;
