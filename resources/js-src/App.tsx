import { BrowserRouter, Route, Routes } from 'react-router-dom';
import { Toaster } from 'sonner';
import { Catalogue } from './pages/Catalogue';
import { Taker } from './pages/Taker';
import { Leaderboard } from './pages/Leaderboard';
import { Results } from './pages/Results';
import { Manager } from './pages/admin/Manager';
import { Invitations } from './pages/admin/Invitations';

export function App() {
    return (
        <BrowserRouter basename={window.QuizConfig.basePath}>
            <Toaster position="top-right" richColors />
            <Routes>
                <Route path="/" element={<Catalogue />} />
                <Route path="/admin" element={<Manager />} />
                <Route path="/admin/quizzes/:quizId/invitations" element={<Invitations />} />
                <Route path="/:quizId/leaderboard" element={<Leaderboard />} />
                <Route path="/:quizId/results" element={<Results />} />
                <Route path="/:quizId" element={<Taker />} />
            </Routes>
        </BrowserRouter>
    );
}
